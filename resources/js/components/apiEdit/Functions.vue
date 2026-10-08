<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue'
import { Button } from '@/components/ui/button'
import Editor from '@/pages/Editor.vue'
import { Api, type ApiData, type ApiVersionFunction } from '@/types'
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
import { Trash2, Pencil, ChevronDown, Plus, Search, X} from 'lucide-vue-next'
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

// Sidebar filter
const functionFilter = ref('')

const filteredFunctions = computed(() => {
    const views = props.apiData?.version_views ?? []
    const q = functionFilter.value.trim().toLowerCase()
    if (!q) return views
    return views.filter(func => func.name.toLowerCase().includes(q))
})

// New: central selection handler that also scrolls to the top of the editor
const selectFunction = (item: ApiVersionFunction) => {
    selectedFunction.value = item
    nextTick(() => {
        editorContainerRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    })
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
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
                
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
                    <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-8 h-full">
                        <!-- Function List Sidebar -->
                    <aside :class="[
                            'lg:flex-shrink-0 flex flex-col max-h-[calc(100vh-8rem)] transition-all',
                            leftCollapsed ? 'lg:w-10' : 'w-full lg:w-72 lg:min-w-72'
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
                        <div v-show="!leftCollapsed" class="flex flex-col min-h-0 flex-1">
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

                            <!-- Function List -->
                            <nav class="flex flex-col space-y-1 overflow-y-auto min-h-0 flex-1">
                                <div
                                    v-for="item in filteredFunctions"
                                    :key="item.name"
                                    class="flex items-center gap-1 group"
                                >
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
                                                <!-- Unsaved changes indicator -->
                                                <!-- <span 
                                                    v-if="unsavedEditsCache.has(item.name)" 
                                                    class="ml-2 h-2 w-2 rounded-full bg-orange-500"
                                                    title="Unsaved changes"
                                                ></span> -->
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent side="left">
                                        {{ item.name }}
                                    </TooltipContent>
                                </Tooltip>
                                    <template v-if ="item.name !== 'Constructor'">
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
                                <p
                                    v-if="functionFilter && filteredFunctions.length === 0"
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