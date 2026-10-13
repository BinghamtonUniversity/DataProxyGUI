<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { Button } from '@/components/ui/button'
import Editor from '@/pages/Editor.vue'
import { Api, type ApiData, type ApiVersionFunction, type RouteData } from '@/types'
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose
} from '@/components/ui/dialog'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger
} from '@/components/ui/tooltip'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getCsrfToken } from '@/lib/utils'
import { Trash2, Pencil, ChevronDown, Plus, Search, X, GripVertical, List, FolderTree } from 'lucide-vue-next'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';


interface Props {
    api_id: string
    api_type: string
    api: Api | null
    apiData: ApiData | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
    handleSave?: () => Promise<void>
    highlightQuery?: string
    highlightTarget?: string
    onValidationError?: (componentId: string, errorCount: number) => void
}

const props = defineProps<Props>()

const selectedFunction = ref<ApiVersionFunction | null>(null)
const isSaving = ref(false)
const saveError = ref<string | null>(null)
const saveSuccess = ref(false)
const editorRef = ref<any>(null)
const editorContainerRef = ref<HTMLElement | null>(null)
const leftCollapsed = ref(false)


const validationErrors = ref<number>(0)
const validationWarnings = ref<number>(0)

// Toaster
const { success, error, warning, info } = useToaster();

// Delete confirmation dialog
const showDeleteModal = ref(false)
const pendingDeleteFunction = ref<ApiVersionFunction | null>(null)
const deleting = ref(false)

// New view dialog state
const isNewViewDialogOpen = ref(false)
const newViewName = ref('')
const isCreatingView = ref(false)
const createViewError = ref<string | null>(null)

// Edit function name state
const viewBeingEdited = ref<ApiVersionFunction | null>(null)
const isEditingView = ref(false)

// Computed property to get the code for the currently selected function
// This checks the cache first, then falls back to the original content
const currentFunctionCode = computed(() => {
    return selectedFunction.value?.content ?? ''
})

// Sidebar filter + view mode
const functionFilter = ref('')
const sidebarView = ref<'list' | 'by_route'>('list')

const filteredFunctions = computed(() => {
    const views = props.apiData?.version_views ?? []
    const q = functionFilter.value.trim().toLowerCase()
    if (!q) return views
    return views.filter(func => func.name.toLowerCase().includes(q))
})

type RouteTreeRoute = { verb: string; view_name: string }
type RouteTreeNode = {
    path: string
    label: string
    routes: RouteTreeRoute[]
    children: RouteTreeNode[]
}

type RouteTreeFlatItem =
    | { kind: 'path'; path: string; label: string; depth: number; isGroup: boolean }
    | { kind: 'route'; verb: string; view_name: string; depth: number; key: string }
    | { kind: 'no_route_header'; depth: number }
    | { kind: 'no_route_fn'; name: string; depth: number }

const normalizeRoutePath = (path: string): string => {
    let p = (path || '').trim()
    if (!p.startsWith('/')) p = `/${p}`
    if (p.length > 1 && p.endsWith('/')) p = p.slice(0, -1)
    return p
}

const pathDepth = (path: string): number => {
    if (path === '/') return 0
    return path.split('/').filter(Boolean).length
}

/** All ancestor prefixes for /a/b/c → [/a, /a/b] (segment-safe nesting). */
const ancestorPaths = (path: string): string[] => {
    if (path === '/') return []
    const parts = path.split('/').filter(Boolean)
    const ancestors: string[] = []
    for (let i = 1; i < parts.length; i++) {
        ancestors.push(`/${parts.slice(0, i).join('/')}`)
    }
    return ancestors
}

const isStrictPathPrefix = (parent: string, child: string): boolean => {
    if (parent === '/') return child !== '/' && child.startsWith('/')
    return child.startsWith(`${parent}/`)
}

const relativePathLabel = (fullPath: string, parentPath: string | null): string => {
    if (!parentPath || parentPath === '/') {
        // Root label: single segment when possible (/systems), else full path
        const parts = fullPath.split('/').filter(Boolean)
        if (parts.length === 1) return `/${parts[0]}`
        return fullPath
    }
    if (fullPath.startsWith(`${parentPath}/`)) {
        const rest = fullPath.slice(parentPath.length + 1)
        // Prefer one segment under parent (/google not /google/user)
        const first = rest.split('/')[0]
        return `/${first}`
    }
    return fullPath
}

const buildRouteFunctionTree = (
    urls: RouteData[],
    views: ApiVersionFunction[],
): { roots: RouteTreeNode[]; noRoute: string[] } => {
    const routedNames = new Set<string>()
    const byPath = new Map<string, RouteTreeRoute[]>()

    for (const url of urls) {
        const path = normalizeRoutePath(url.path || '')
        const verb = (url.verb || '').toUpperCase()
        const view_name = url.view_name || ''
        if (view_name) routedNames.add(view_name)
        if (!byPath.has(path)) byPath.set(path, [])
        byPath.get(path)!.push({ verb, view_name })
    }

    // Synthesize intermediate segments so /systems/google/user nests under /systems → /google
    for (const path of [...byPath.keys()]) {
        for (const ancestor of ancestorPaths(path)) {
            if (!byPath.has(ancestor)) {
                byPath.set(ancestor, [])
            }
        }
    }

    const paths = [...byPath.keys()].sort((a, b) => {
        const depthA = pathDepth(a)
        const depthB = pathDepth(b)
        if (depthA !== depthB) return depthA - depthB
        return a.localeCompare(b)
    })

    const nodeMap = new Map<string, RouteTreeNode>()
    const roots: RouteTreeNode[] = []

    for (const path of paths) {
        let parentPath: string | null = null
        let parentLen = -1
        for (const existing of nodeMap.keys()) {
            if (isStrictPathPrefix(existing, path) && existing.length > parentLen) {
                parentPath = existing
                parentLen = existing.length
            }
        }
        const node: RouteTreeNode = {
            path,
            label: relativePathLabel(path, parentPath),
            routes: byPath.get(path) ?? [],
            children: [],
        }
        nodeMap.set(path, node)
        if (parentPath && nodeMap.has(parentPath)) {
            nodeMap.get(parentPath)!.children.push(node)
        } else {
            roots.push(node)
        }
    }

    const noRoute = views.map((v) => v.name).filter((name) => !routedNames.has(name))
    return { roots, noRoute }
}

const filterRouteTreeNode = (node: RouteTreeNode, q: string): RouteTreeNode | null => {
    const pathMatches =
        node.path.toLowerCase().includes(q) || node.label.toLowerCase().includes(q)
    const children = node.children
        .map((child) => filterRouteTreeNode(child, q))
        .filter((child): child is RouteTreeNode => child !== null)
    const routes = pathMatches
        ? node.routes
        : node.routes.filter(
              (r) =>
                  r.view_name.toLowerCase().includes(q) ||
                  r.verb.toLowerCase().includes(q),
          )
    if (routes.length === 0 && children.length === 0) return null
    return { ...node, routes, children }
}

/**
 * Flatten for display with parent grouping:
 *   /systems
 *     /google
 *       /user
 *         GET ...
 * Folder-only parents (no verbs) still get a header so siblings share a group.
 * Labels are relative (`/google`); full path is kept for tooltip.
 */
const flattenRouteTree = (nodes: RouteTreeNode[], depth = 0): RouteTreeFlatItem[] => {
    const out: RouteTreeFlatItem[] = []
    for (const node of nodes) {
        const hasRoutes = node.routes.length > 0
        const hasChildren = node.children.length > 0
        // Skip empty shell nodes (no routes, no children) — shouldn't happen after filter
        if (!hasRoutes && !hasChildren) continue

        out.push({
            kind: 'path',
            path: node.path,
            label: node.label,
            depth,
            isGroup: !hasRoutes && hasChildren,
        })
        for (const route of node.routes) {
            out.push({
                kind: 'route',
                verb: route.verb,
                view_name: route.view_name,
                depth: depth + 1,
                key: `${node.path}|${route.verb}|${route.view_name}`,
            })
        }
        out.push(...flattenRouteTree(node.children, depth + 1))
    }
    return out
}

const routeFunctionTree = computed(() => {
    const urls = props.apiData?.version_urls ?? []
    const views = props.apiData?.version_views ?? []
    return buildRouteFunctionTree(urls, views)
})

const filteredRouteTreeItems = computed((): RouteTreeFlatItem[] => {
    const q = functionFilter.value.trim().toLowerCase()
    const { roots, noRoute } = routeFunctionTree.value
    const filteredRoots = q
        ? roots
              .map((node) => filterRouteTreeNode(node, q))
              .filter((node): node is RouteTreeNode => node !== null)
        : roots
    const filteredNoRoute = q
        ? noRoute.filter((name) => name.toLowerCase().includes(q))
        : noRoute

    const items = flattenRouteTree(filteredRoots)
    if (filteredNoRoute.length > 0) {
        items.push({ kind: 'no_route_header', depth: 0 })
        for (const name of filteredNoRoute) {
            items.push({ kind: 'no_route_fn', name, depth: 1 })
        }
    }
    return items
})

const hasFilteredRouteResults = computed(() => filteredRouteTreeItems.value.length > 0)

const setSidebarView = (view: 'list' | 'by_route') => {
    sidebarView.value = view
    if (view !== 'list') clearDragState()
}

// Drag-and-drop reorder (list view only; disabled while filter is active)
const canReorder = computed(
    () => sidebarView.value === 'list' && functionFilter.value.trim() === '',
)
const dragFromIndex = ref<number | null>(null)
/** Insertion slot in the list: 0 = before first, length = after last */
const dropInsertIndex = ref<number | null>(null)
const draggingFunctionName = ref<string | null>(null)
let dragGhostEl: HTMLElement | null = null

const isNoOpInsert = computed(() => {
    if (dragFromIndex.value === null || dropInsertIndex.value === null) return true
    return (
        dropInsertIndex.value === dragFromIndex.value ||
        dropInsertIndex.value === dragFromIndex.value + 1
    )
})

/** Neighbor names for the active insertion gap (makes "between X and Y" obvious). */
const dropGapLabel = computed(() => {
    if (dropInsertIndex.value === null || isNoOpInsert.value) return null
    const views = filteredFunctions.value
    const i = dropInsertIndex.value
    const above = i > 0 ? views[i - 1]?.name : null
    const below = i < views.length ? views[i]?.name : null
    if (above && below) return `Between ${above} and ${below}`
    if (below) return `Before ${below}`
    if (above) return `After ${above}`
    return null
})

const showDropGapAt = (slotIndex: number) => {
    return (
        dragFromIndex.value !== null &&
        dropInsertIndex.value === slotIndex &&
        !isNoOpInsert.value
    )
}

/** Soft-highlight the two neighbors around the active insertion gap. */
const isDropNeighbor = (index: number) => {
    if (dropInsertIndex.value === null || isNoOpInsert.value) return false
    return index === dropInsertIndex.value - 1 || index === dropInsertIndex.value
}

const reorderVersionViews = (fromIndex: number, insertIndex: number) => {
    if (!props.apiData) return
    if (insertIndex === fromIndex || insertIndex === fromIndex + 1) return
    const views = [...props.apiData.version_views]
    if (fromIndex < 0 || fromIndex >= views.length) return
    if (insertIndex < 0 || insertIndex > views.length) return
    const [moved] = views.splice(fromIndex, 1)
    const to = fromIndex < insertIndex ? insertIndex - 1 : insertIndex
    views.splice(to, 0, moved)
    props.updateApiData({
        ...props.apiData,
        version_views: views,
    })
}

const clearDragState = () => {
    dragFromIndex.value = null
    dropInsertIndex.value = null
    draggingFunctionName.value = null
    if (dragGhostEl?.parentNode) {
        dragGhostEl.parentNode.removeChild(dragGhostEl)
    }
    dragGhostEl = null
}

const onFunctionDragStart = (event: DragEvent, index: number) => {
    if (!canReorder.value || !props.apiData) {
        event.preventDefault()
        return
    }
    const name = props.apiData.version_views[index]?.name ?? ''
    dragFromIndex.value = index
    draggingFunctionName.value = name

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        event.dataTransfer.setData('text/plain', name)

        // Named drag preview that follows the cursor (inline colors — setDragImage ignores CSS vars)
        const ghost = document.createElement('div')
        ghost.textContent = name
        ghost.style.cssText = [
            'position: fixed',
            'top: -1000px',
            'left: -1000px',
            'z-index: 9999',
            'pointer-events: none',
            'padding: 6px 12px',
            'border-radius: 6px',
            'font-size: 12px',
            'font-weight: 600',
            'font-family: ui-sans-serif, system-ui, sans-serif',
            'white-space: nowrap',
            'max-width: 220px',
            'overflow: hidden',
            'text-overflow: ellipsis',
            'background: #2563eb',
            'color: #ffffff',
            'border: 1px solid #1d4ed8',
            'box-shadow: 0 4px 12px rgba(0,0,0,0.3)',
        ].join(';')
        document.body.appendChild(ghost)
        dragGhostEl = ghost
        event.dataTransfer.setDragImage(ghost, 12, 16)
    }
}

const onFunctionDragOver = (event: DragEvent, index: number) => {
    if (!canReorder.value || dragFromIndex.value === null) return
    event.preventDefault()
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move'
    }
    const target = event.currentTarget as HTMLElement
    const rect = target.getBoundingClientRect()
    const insertBefore = event.clientY < rect.top + rect.height / 2
    dropInsertIndex.value = insertBefore ? index : index + 1
}

const onFunctionDrop = (event: DragEvent) => {
    event.preventDefault()
    if (!canReorder.value || dragFromIndex.value === null || dropInsertIndex.value === null) {
        clearDragState()
        return
    }
    reorderVersionViews(dragFromIndex.value, dropInsertIndex.value)
    clearDragState()
}

const onFunctionDragEnd = () => {
    clearDragState()
}

const onListDragOver = (event: DragEvent) => {
    if (!canReorder.value || dragFromIndex.value === null) return
    event.preventDefault()
}

const onEndGapDragOver = (event: DragEvent) => {
    if (!canReorder.value || dragFromIndex.value === null) return
    event.preventDefault()
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move'
    }
    dropInsertIndex.value = filteredFunctions.value.length
}

// New: central selection handler that also scrolls to the top of the editor
const selectFunction = (item: ApiVersionFunction) => {
    selectedFunction.value = item
    nextTick(() => {
        editorContainerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
}

const findFunctionByName = (name: string): ApiVersionFunction | undefined => {
    return props.apiData?.version_views.find((v) => v.name === name)
}

const selectFunctionByName = (name: string) => {
    const fn = findFunctionByName(name)
    if (fn) selectFunction(fn)
}

const editFunctionByName = (name: string) => {
    const fn = findFunctionByName(name)
    if (fn) editFunctionName(fn)
}

const deleteFunctionByName = (name: string) => {
    const fn = findFunctionByName(name)
    if (fn) handleDeleteFunction(fn)
}

// Check if current function has unsaved changes
// const hasUnsavedChanges = computed(() => {
//     if (!selectedFunction.value) return false
//     return unsavedEditsCache.value.has(selectedFunction.value.name)
// })
// ============================================

const handleValidation = (markers: any) => {
    const errors = markers.filter((m: any) => m.severity >= 8) // Monaco.MarkerSeverity.Error = 8
    const warnings = markers.filter((m: any) => m.severity === 4) // Monaco.MarkerSeverity.Warning = 4
    
    validationErrors.value = errors.length
    validationWarnings.value = warnings.length
    
    // Report validation errors to parent Layout component
    if (props.onValidationError) {
        props.onValidationError('functions', errors.length)
    }
    
    // Provide immediate feedback to user about validation status
    if (errors.length > 0) {
        // Show first error message for immediate feedback
        const firstError = errors[0]
        saveError.value = `Validation Error: ${firstError.message}${errors.length > 1 ? ` (and ${errors.length - 1} more)` : ''}`
    } else if (warnings.length > 0) {
        // Show warning message
        const firstWarning = warnings[0]
        saveError.value = `Warning: ${firstWarning.message}${warnings.length > 1 ? ` (and ${warnings.length - 1} more)` : ''}`
    } else {
        // Clear any previous validation messages
        if (saveError.value && (saveError.value.includes('Validation Error') || saveError.value.includes('Warning'))) {
            saveError.value = null
        }
    }
}

const handleUpdateCode = (updatedCode: string) => {
    if (!selectedFunction.value || !props.apiData) {
        saveError.value = 'No function selected or API data not available'
        return
    }

    // Update the local state immediately
    try {
        const updatedApiData = {
            ...props.apiData,
            version_views: props.apiData.version_views.map(func => 
                func.name === selectedFunction.value?.name 
                    ? { ...func, content: updatedCode }
                    : func
            )
        }
        
        // Update the local state through parent
        props.updateApiData(updatedApiData)
        
        if (selectedFunction.value) {
            selectedFunction.value.content = updatedCode
        }
        
    } catch (error) {
        console.error('Update error:', error)
        saveError.value = error instanceof Error ? error.message : 'Failed to update code'
    }
}

const handleSave = async (updatedCode: string) => {
    if (props.handleSave) {
    await props.handleSave()
    }
}

const handleCreateNewView = async () => {
    const name = newViewName.value.trim();

    if (!name || !props.apiData) {
        createViewError.value = 'Please enter a valid function name'
        return
    }

    // Validate function name syntax
    const validNamePattern = /^[a-zA-Z_][a-zA-Z0-9_]*$/;
    if (!validNamePattern.test(name)) {
        createViewError.value = 'Invalid function name. Use letters, numbers, and underscores only, and do not start with a number.'
        return;
    }

    // Extra rule for Python: disallow double underscores "__"
    if (props.api?.api_type === 'python' && name.includes('__')) {
        createViewError.value = 'Python view names cannot contain double underscores "__".';
        return;
    }

    // Check if function name already exists
    const existingFunction = props.apiData.version_views.find(func => func.name === name)
    if (existingFunction) {
        createViewError.value = 'A function with this name already exists'
        return
    }

    isCreatingView.value = true
    createViewError.value = null

    try {
        // TO-DO:: PHP function template
        const newFunction: ApiVersionFunction = {
            name,
            content: ``,
        }

        const updatedApiData = {
            ...props.apiData,
            version_views: [...props.apiData.version_views, newFunction]
        }
        // Update the local state through parent
        props.updateApiData(updatedApiData)
        
        // Select the newly created function
        selectedFunction.value = newFunction
        
        // Reset dialog state
        newViewName.value = ''
        isNewViewDialogOpen.value = false

    } catch (e) {
        console.error('Create view error:', e)
        createViewError.value = e instanceof Error ? e.message : 'Failed to create new function'
        error(createViewError.value, 'Error');
    } finally {
        isCreatingView.value = false
    }
}

const handleDeleteFunction = (view: ApiVersionFunction) => {
    pendingDeleteFunction.value = view
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deleting.value) return
    showDeleteModal.value = false
    pendingDeleteFunction.value = null
}

const confirmDelete = async () => {
    const view = pendingDeleteFunction.value
    if (!view) {
        closeDeleteModal()
        return
    }

    if (!props.apiData) {
        console.error('API data not available')
        closeDeleteModal()
        return
    }

    deleting.value = true
    try {
        const updatedApiData = {
            ...props.apiData,
            version_views: props.apiData.version_views?.filter(existingView => !(existingView.name === view.name)) || []
        }

        props.updateApiData(updatedApiData)
        selectedFunction.value = null
        success(`Function "${view.name}" deleted successfully`, 'Function Deleted')
        showDeleteModal.value = false
        pendingDeleteFunction.value = null
    } catch (err: any) {
        console.error('Error deleting route:', err)
        error(err.message || 'Error deleting function', 'Error')
    } finally {
        deleting.value = false
    }
}


const editFunctionName = (view: ApiVersionFunction) => {
  isEditingView.value = true
  viewBeingEdited.value = view
  newViewName.value = view.name
  isNewViewDialogOpen.value = true
}


const resetNewViewDialog = () => {
    newViewName.value = ''
    createViewError.value = null
    isCreatingView.value = false
    isEditingView.value = false
    viewBeingEdited.value = null
}

const handleUpdateFunctionName = async () => {
  if (!props.apiData || !viewBeingEdited.value) {
    createViewError.value = 'No function selected for editing'
    return
  }

  const trimmedName = newViewName.value.trim()
  if (!trimmedName) {
    createViewError.value = 'Function name cannot be empty'
    return
  }

  // Validate function name syntax
  const validNamePattern = /^[a-zA-Z_][a-zA-Z0-9_]*$/;
  if (!validNamePattern.test(trimmedName)) {
    createViewError.value =
      'Invalid function name. Use letters, numbers, and underscores only, and do not start with a number.';
    return;
  }

  // Extra rule for Python: disallow double underscores "__"
    if (props.api_type === 'python' && trimmedName.includes('__')) {
        createViewError.value = 'Python view names cannot contain double underscores "__".';
        return;
    }

  // Prevent duplicates
  const nameExists = props.apiData.version_views.some(
    func => func.name === trimmedName && func !== viewBeingEdited.value
  )
  if (nameExists) {
    createViewError.value = 'A function with this name already exists'
    return
  }

  isCreatingView.value = true
  createViewError.value = null

  try {
    const oldName = viewBeingEdited.value.name
    
    const updatedApiData = {
      ...props.apiData,
      version_views: props.apiData.version_views.map(func =>
        func.name === oldName
          ? { ...func, name: trimmedName }
          : func
      )
    }

    props.updateApiData(updatedApiData)

    // Transfer cached edits to new function name
    // if (unsavedEditsCache.value.has(oldName)) {
    //   const cachedCode = unsavedEditsCache.value.get(oldName)
    //   unsavedEditsCache.value.delete(oldName)
    //   if (cachedCode !== undefined) {
    //     unsavedEditsCache.value.set(trimmedName, cachedCode)
    //   }
    // }

    // If editing currently selected function, update reference
    if (selectedFunction.value?.name === oldName) {
      selectedFunction.value.name = trimmedName
    }

    success(`Function name updated to "${trimmedName}"`, 'Function Updated')
    isNewViewDialogOpen.value = false
  } catch (e: any) {
    console.error('Update function name error:', e)
    createViewError.value = e.message || 'Failed to update function name'
  } finally {
    isCreatingView.value = false
    isEditingView.value = false
    viewBeingEdited.value = null
  }
}

// Handle search result selection
const handleSearchResult = (event: CustomEvent) => {
    const { category, name, data, searchQuery } = event.detail
    
    if (category === 'Functions' && props.apiData?.version_views) {
        // Find the function by name
        const functionToSelect = props.apiData.version_views.find(func => func.name === name)
        if (functionToSelect) {
            selectedFunction.value = functionToSelect
            // Scroll to the function in the list
            setTimeout(() => {
                editorContainerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
                const functionButton = document.querySelector(`[data-function-name="${name}"]`)
                if (functionButton) {
                    functionButton.scrollIntoView({ behavior: 'smooth', block: 'center' })
                }
                
                // If there's a search query, highlight it in the editor
                if (searchQuery && searchQuery.trim()) {
                    highlightTextInEditor(searchQuery.trim())
                }
            }, 200) // Increased delay to ensure editor is loaded
        }
    }
}

// Function to highlight text in the editor
const highlightTextInEditor = (searchText: string) => {
    let attempts = 0
    const maxAttempts = 20 // Try for up to 10 seconds (20 * 500ms)
    
    const tryHighlight = () => {
        attempts++
        
        // Try multiple methods to find the Monaco editor
        let editorInstance = null
        
        // Method 1: Look for VueMonacoEditor component
        const vueMonacoEditor = document.querySelector('vue-monaco-editor')
        if (vueMonacoEditor && (vueMonacoEditor as any).__vueParentComponent) {
            const component = (vueMonacoEditor as any).__vueParentComponent
            if (component.exposed && component.exposed.editor) {
                editorInstance = component.exposed.editor.value
            }
        }
        
        // Method 2: Look for Monaco editor in DOM
        if (!editorInstance) {
            const editorElement = document.querySelector('.monaco-editor')
            if (editorElement) {
                editorInstance = (editorElement as any).__monacoEditor
            }
        }
        
        // Method 3: Look for Monaco editor in window
        if (!editorInstance && window.monaco) {
            const editors = window.monaco.editor.getEditors()
            if (editors.length > 0) {
                // Find the editor that contains our function content
                const currentContent = selectedFunction.value?.content || ''
                editorInstance = editors.find(e => {
                    const model = e.getModel()
                    return model && model.getValue().includes(currentContent)
                })
            }
        }
        
        // Method 4: Try to find any Monaco editor
        if (!editorInstance && window.monaco) {
            const editors = window.monaco.editor.getEditors()
            if (editors.length > 0) {
                editorInstance = editors[0] // Use the first available editor
            }
        }
        
        // Method 5: Try to access through Vue component ref
        if (!editorInstance && editorRef.value) {
            if (editorRef.value.editor) {
                editorInstance = editorRef.value.editor
            }
        }
        
        if (editorInstance) {
            try {
                const model = editorInstance.getModel()
                if (model && model.getValue().length > 0) {
                    // Find all matches of the search text
                    const matches = model.findMatches(searchText, false, false, false, null, false)
                    
                    if (matches.length > 0) {
                        // Try to get the range from the match object
                        const firstMatch = matches[0]
                        const range = firstMatch.range || firstMatch
                        
                        try {
                            // Set selection to the first match
                            editorInstance.setSelection(range)
                            editorInstance.revealRangeInCenter(range)
                            editorInstance.focus()
                        } catch (selectionError) {
                            // Try alternative approach - just focus and scroll to the first match
                            editorInstance.focus()
                        }
                        
                        // Highlight all matches with a bright yellow background
                        const decorations = editorInstance.deltaDecorations([], matches.map((match: any) => ({
                            range: match.range || match,
                            options: {
                                inlineClassName: 'search-highlight',
                                isWholeLine: false
                            }
                        })))
                        
                        // Remove highlights after 5 seconds
                        setTimeout(() => {
                            editorInstance.deltaDecorations(decorations, [])
                        }, 5000)
                        
                        return // Success, stop trying
                    }
                }
            } catch (error) {
                // Continue trying
            }
        }
        
        // If we haven't found the editor yet and haven't exceeded max attempts, try again
        if (attempts < maxAttempts) {
            setTimeout(tryHighlight, 500) // Try again in 500ms
        }
    }
    
    // Start trying after a short delay
    setTimeout(tryHighlight, 200)
}

// When API data (and its functions) change – e.g. after switching versions –
// keep the editor in sync:
// - If the previously selected function still exists (by name), re-select it
//   so its new code is shown.
// - If it no longer exists, clear the selection to close the editor.
watch(
  () => props.apiData?.version_views,
  (newViews) => {
    // No API data or no functions – close the editor
    if (!newViews || newViews.length === 0) {
      if (selectedFunction.value) {
        selectedFunction.value = null
        validationErrors.value = 0
        validationWarnings.value = 0
        if (props.onValidationError) {
          props.onValidationError('functions', 0)
        }
      }
      return
    }

    // If nothing is currently selected, nothing to sync
    if (!selectedFunction.value) {
      return
    }

    const existing = newViews.find(func => func.name === selectedFunction.value?.name)

    if (existing) {
      // Re-bind to the new function object so the editor shows updated code
      selectedFunction.value = existing
    } else {
      // Function no longer exists in this version – close the editor
      selectedFunction.value = null
      validationErrors.value = 0
      validationWarnings.value = 0
      if (props.onValidationError) {
        props.onValidationError('functions', 0)
      }
    }
  }
)

// Add event listener for search results
onMounted(() => {
    window.addEventListener('search-result-selected', handleSearchResult as EventListener)
})

onUnmounted(() => {
    window.removeEventListener('search-result-selected', handleSearchResult as EventListener)
})

// Expose validation state to parent component
defineExpose({
    validationErrors,
    validationWarnings,
    hasValidationErrors: computed(() => validationErrors.value > 0)
})

</script>

<style>
.search-highlight {
    background-color: #ffeb3b !important;
    color: #000 !important;
    border-radius: 2px;
    padding: 1px 2px;
}
</style>

<template>
    <TooltipProvider :delay-duration="300">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
                
                <!-- Loading State -->
                <template v-if="loadingApiData">
                    <div class="flex items-center justify-center h-32">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
                            <p class="mt-2">Loading functions...</p>
                        </div>
                    </div>
                </template>

                <!-- Error State -->
                <template v-else-if="apiError">
                    <div class="flex items-center justify-center h-32">
                        <div class="text-center text-red-600">
                            <p>Error loading functions: {{ apiError }}</p>
                        </div>
                    </div>
                </template>

                <!-- Functions Content -->
                <template v-else-if="apiData?.version_views">
                    <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:items-stretch lg:space-y-0 lg:space-x-8">
                        <!-- Function List Sidebar: explicit height so the list fills the visible viewport below page chrome -->
                    <aside :class="[
                            'lg:flex-shrink-0 flex flex-col h-[calc(100vh-16rem)] sticky top-4 transition-all',
                            leftCollapsed ? 'lg:w-10' : 'w-full lg:w-80 lg:min-w-80'
                        ]"
                    >
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="mb-2 w-full justify-center shrink-0" 
                            @click="leftCollapsed = !leftCollapsed"
                        >
                            <ChevronDown :class="['h-4 w-4 transition-transform', leftCollapsed ? '-rotate-90' : 'rotate-90']" />
                        </Button>
                        <div v-show="!leftCollapsed" class="flex flex-col min-h-0 flex-1 overflow-hidden">
                            <!-- New View Button -->
                            <div class="mb-4 shrink-0">
                                <Dialog v-model:open="isNewViewDialogOpen" @update:open="resetNewViewDialog">
                                    <DialogTrigger as-child>
                                        <Button variant="outline" class="w-full text-sm text-green-600">
                                             <Plus class="mr-2 h-4 w-4" /> New
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent class="sm:max-w-md">
                                        <DialogHeader>
                                            <DialogTitle>{{ isEditingView ? 'Edit Function Name' : 'Create New Function' }}</DialogTitle>
                                        </DialogHeader>
                                        <div class="space-y-4">
                                            <div class="space-y-2">
                                                <Label for="function-name">Function Name</Label>
                                                <Input
                                                    id="function-name"
                                                    v-model="newViewName"
                                                    placeholder="Enter function name"
                                                    :disabled="isCreatingView"
                                                    @keyup.enter="isEditingView ? handleUpdateFunctionName() : handleCreateNewView()"
                                                />
                                            </div>
                                            
                                            <div v-if="createViewError" class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                                                {{ createViewError }}
                                            </div>
                                            
                                            <div class="flex justify-end space-x-2">
                                                <Button 
                                                    variant="outline" 
                                                    @click="isNewViewDialogOpen = false"
                                                    :disabled="isCreatingView"
                                                >
                                                    Cancel
                                                </Button>
                                                <Button 
                                                    @click=" isEditingView? handleUpdateFunctionName() : handleCreateNewView()"
                                                    :disabled="!newViewName.trim() || isCreatingView"
                                                >
                                                    <div v-if="isCreatingView" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                    {{ isCreatingView 
                                                        ? (isEditingView ? 'Updating...' : 'Creating...') 
                                                        : (isEditingView ? 'Update' : 'Create') 
                                                    }}
                                                </Button>
                                            </div>
                                        </div>
                                    </DialogContent>
                                </Dialog>
                            </div>
                            <!-- View toggle -->
                            <div class="mb-2 flex shrink-0 gap-1 rounded-md border border-border p-0.5">
                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="sidebarView === 'list' ? 'secondary' : 'ghost'"
                                    class="h-7 flex-1 gap-1 px-2 text-xs"
                                    @click="setSidebarView('list')"
                                >
                                    <List class="h-3.5 w-3.5" />
                                    List
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    :variant="sidebarView === 'by_route' ? 'secondary' : 'ghost'"
                                    class="h-7 flex-1 gap-1 px-2 text-xs"
                                    @click="setSidebarView('by_route')"
                                >
                                    <FolderTree class="h-3.5 w-3.5" />
                                    By route
                                </Button>
                            </div>

                            <!-- Filter -->
                            <div class="relative mb-3 shrink-0">
                                <Search class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                                <Input
                                    v-model="functionFilter"
                                    placeholder="Filter functions..."
                                    class="h-8 pl-8 pr-8 text-xs"
                                    @keydown.esc="functionFilter = ''"
                                />
                                <button
                                    v-if="functionFilter"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    @click="functionFilter = ''"
                                >
                                    <X class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Flat function list (reorderable) -->
                            <nav
                                v-if="sidebarView === 'list'"
                                class="flex flex-col overflow-y-auto min-h-0 flex-1"
                                @dragover="onListDragOver"
                                @drop="onFunctionDrop"
                            >
                                <template
                                    v-for="(item, index) in filteredFunctions"
                                    :key="item.name"
                                >
                                    <!-- Insertion gap before this item -->
                                    <div
                                        class="relative mx-1 flex items-center justify-center transition-[min-height] duration-100"
                                        :class="showDropGapAt(index) ? 'min-h-7 my-0.5' : 'min-h-1'"
                                        aria-hidden="true"
                                    >
                                        <div
                                            v-if="showDropGapAt(index)"
                                            class="absolute inset-x-0 flex flex-col items-stretch gap-0.5 pointer-events-none"
                                        >
                                            <div class="h-0.5 rounded-full bg-primary shadow-[0_0_0_1px_hsl(var(--primary)/0.35)]" />
                                            <p
                                                v-if="dropGapLabel"
                                                class="px-1 text-[10px] leading-tight font-semibold text-primary truncate text-center"
                                            >
                                                <span v-if="draggingFunctionName">{{ draggingFunctionName }} → </span>{{ dropGapLabel }}
                                            </p>
                                            <div class="h-0.5 rounded-full bg-primary shadow-[0_0_0_1px_hsl(var(--primary)/0.35)]" />
                                        </div>
                                    </div>

                                    <div
                                        class="flex items-center gap-1 group rounded-md"
                                        :class="[
                                            dragFromIndex === index ? 'opacity-40 ring-1 ring-dashed ring-muted-foreground/40' : '',
                                            isDropNeighbor(index) ? 'bg-primary/10 ring-1 ring-primary/40' : '',
                                        ]"
                                        @dragover="onFunctionDragOver($event, index)"
                                        @drop="onFunctionDrop"
                                        @dragend="onFunctionDragEnd"
                                    >
                                        <span
                                            v-if="canReorder"
                                            draggable="true"
                                            class="flex shrink-0 items-center text-muted-foreground opacity-40 group-hover:opacity-100 pl-0.5 cursor-grab active:cursor-grabbing"
                                            title="Drag to reorder"
                                            @dragstart="onFunctionDragStart($event, index)"
                                            @click.stop
                                        >
                                            <GripVertical class="h-3.5 w-3.5" />
                                        </span>
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    :data-function-name="item.name"
                                                    variant="ghost"
                                                    :class="[
                                                        'justify-start',
                                                        'px-3',
                                                        'py-1',
                                                        'flex-1',
                                                        'text-xs',
                                                        'truncate',
                                                        'min-w-0',
                                                        selectedFunction?.name === item.name ? 'bg-accent' : ''
                                                    ]"
                                                    @click="selectFunction(item)"
                                                >
                                                    {{ item.name }}
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent side="left">
                                                {{ item.name }}
                                            </TooltipContent>
                                        </Tooltip>
                                        <template v-if="item.name !== 'Constructor'">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click.stop="editFunctionName(item)"
                                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-opacity"
                                            >
                                                <Pencil :size="1" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click.stop="handleDeleteFunction(item)"
                                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-opacity"
                                            >
                                                <Trash2 :size="1" />
                                            </Button>
                                        </template>
                                    </div>
                                </template>

                                <!-- Insertion gap after last item -->
                                <div
                                    class="relative mx-1 flex items-center justify-center transition-[min-height] duration-100"
                                    :class="showDropGapAt(filteredFunctions.length) ? 'min-h-7 my-0.5' : 'min-h-2'"
                                    aria-hidden="true"
                                    @dragover="onEndGapDragOver"
                                    @drop="onFunctionDrop"
                                >
                                    <div
                                        v-if="showDropGapAt(filteredFunctions.length)"
                                        class="absolute inset-x-0 flex flex-col items-stretch gap-0.5 pointer-events-none"
                                    >
                                        <div class="h-0.5 rounded-full bg-primary shadow-[0_0_0_1px_hsl(var(--primary)/0.35)]" />
                                        <p
                                            v-if="dropGapLabel"
                                            class="px-1 text-[10px] leading-tight font-semibold text-primary truncate text-center"
                                        >
                                            <span v-if="draggingFunctionName">{{ draggingFunctionName }} → </span>{{ dropGapLabel }}
                                        </p>
                                        <div class="h-0.5 rounded-full bg-primary shadow-[0_0_0_1px_hsl(var(--primary)/0.35)]" />
                                    </div>
                                </div>

                                <p
                                    v-if="functionFilter && filteredFunctions.length === 0"
                                    class="px-3 py-2 text-xs text-muted-foreground"
                                >
                                    No functions match "{{ functionFilter }}"
                                </p>
                            </nav>

                            <!-- By route tree (no reorder) -->
                            <nav
                                v-else
                                class="flex flex-col overflow-y-auto min-h-0 flex-1 space-y-0.5"
                            >
                                <template
                                    v-for="(row, rowIndex) in filteredRouteTreeItems"
                                    :key="row.kind === 'path'
                                        ? `path-${row.path}`
                                        : row.kind === 'route'
                                            ? row.key
                                            : row.kind === 'no_route_header'
                                                ? 'no_route_header'
                                                : `no_route-${row.name}-${rowIndex}`"
                                >
                                    <div
                                        v-if="row.kind === 'path'"
                                        class="mx-1 shrink-0 rounded px-2 py-1.5 font-mono text-xs font-bold leading-snug break-all"
                                        :class="row.isGroup
                                            ? 'mt-2 mb-0.5 border border-sky-500/25 bg-sky-500/5'
                                            : 'mt-1 mb-0.5 border border-sky-500/40 bg-sky-500/15'"
                                        :style="{ marginLeft: `${row.depth * 12}px`, color: row.isGroup ? '#38bdf8' : '#7dd3fc' }"
                                        :title="row.path"
                                    >
                                        {{ row.label }}
                                    </div>
                                    <div
                                        v-else-if="row.kind === 'route'"
                                        class="flex shrink-0 items-center gap-1 group rounded-md"
                                        :style="{ paddingLeft: `${10 + row.depth * 12}px` }"
                                    >
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    :data-function-name="row.view_name"
                                                    variant="ghost"
                                                    :class="[
                                                        'justify-start',
                                                        'px-2',
                                                        'py-1',
                                                        'flex-1',
                                                        'text-xs',
                                                        'truncate',
                                                        'min-w-0',
                                                        'font-normal',
                                                        selectedFunction?.name === row.view_name ? 'bg-accent' : ''
                                                    ]"
                                                    @click="selectFunctionByName(row.view_name)"
                                                >
                                                    <span class="mr-1.5 shrink-0 font-semibold text-muted-foreground">{{ row.verb }}</span>
                                                    <span class="truncate">{{ row.view_name }}</span>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent side="right">
                                                {{ row.verb }} {{ row.view_name }}
                                            </TooltipContent>
                                        </Tooltip>
                                        <template v-if="row.view_name !== 'Constructor' && findFunctionByName(row.view_name)">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click.stop="editFunctionByName(row.view_name)"
                                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-opacity"
                                            >
                                                <Pencil :size="1" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click.stop="deleteFunctionByName(row.view_name)"
                                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-opacity"
                                            >
                                                <Trash2 :size="1" />
                                            </Button>
                                        </template>
                                    </div>
                                    <div
                                        v-else-if="row.kind === 'no_route_header'"
                                        class="mx-1 mt-2 mb-0.5 shrink-0 rounded-md border border-border bg-muted/60 px-2 py-1.5 font-mono text-xs font-semibold leading-snug text-muted-foreground"
                                    >
                                        no_route
                                    </div>
                                    <div
                                        v-else
                                        class="flex shrink-0 items-center gap-1 group rounded-md"
                                        :style="{ paddingLeft: `${8 + row.depth * 12}px` }"
                                    >
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    :data-function-name="row.name"
                                                    variant="ghost"
                                                    :class="[
                                                        'justify-start',
                                                        'px-2',
                                                        'py-1',
                                                        'flex-1',
                                                        'text-xs',
                                                        'truncate',
                                                        'min-w-0',
                                                        selectedFunction?.name === row.name ? 'bg-accent' : ''
                                                    ]"
                                                    @click="selectFunctionByName(row.name)"
                                                >
                                                    {{ row.name }}
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent side="left">
                                                {{ row.name }}
                                            </TooltipContent>
                                        </Tooltip>
                                        <template v-if="row.name !== 'Constructor'">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click.stop="editFunctionByName(row.name)"
                                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-opacity"
                                            >
                                                <Pencil :size="1" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click.stop="deleteFunctionByName(row.name)"
                                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-opacity"
                                            >
                                                <Trash2 :size="1" />
                                            </Button>
                                        </template>
                                    </div>
                                </template>

                                <p
                                    v-if="functionFilter && !hasFilteredRouteResults"
                                    class="px-3 py-2 text-xs text-muted-foreground"
                                >
                                    No functions match "{{ functionFilter }}"
                                </p>
                            </nav>
                        </div>
                    </aside>

                        <!-- Editor Area -->
                        <div class="flex-1 min-w-0" ref="editorContainerRef">
                            <!-- Code Editor -->
                            <Editor 
                                ref="editorRef"
                                v-if="selectedFunction"
                                :key="selectedFunction.name"
                                :code="currentFunctionCode" 
                                :language="props?.api_type === 'python' || props?.api_type === 'php' ? props?.api_type : 'php'"
                                :is-saving="isSaving"
                                :saveError="saveError??''"
                                :saveSuccess="saveSuccess"

                                @save="handleSave"
                                @update:code="handleUpdateCode"
                                @validate="handleValidation"
                            />
                            
                            <!-- No Function Selected State -->
                            <div v-else class="text-muted-foreground text-sm p-4 text-center">
                                Select a function to view its code.
                            </div>
                        </div>
                    </div>
                </template>

                <!-- No Functions Available State -->
                <template v-else>
                    <div class="flex items-center justify-center h-32">
                        <div class="text-center">
                            <p>No functions available for this API version.</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </TooltipProvider>

    <ConfirmDeleteModal
        :isOpen="showDeleteModal"
        :count="pendingDeleteFunction ? 1 : 0"
        :deleting="deleting"
        @confirm="confirmDelete"
        @close="closeDeleteModal"
    />
</template>