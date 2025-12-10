<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { Button } from '@/components/ui/button'
import Editor from '@/pages/Editor.vue'
import { type ApiData, type ApiVersionFunction, Api} from '@/types'
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getCsrfToken } from '@/lib/utils'
import { Trash2, Pencil } from 'lucide-vue-next'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';


interface Props {
    api_id: string
    api_type: string
    api: Api | null
    highlightQuery?: string
    highlightTarget?: string
    apiData: ApiData | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
    handleSave?: () => Promise<void>
}

const props = defineProps<Props>()

const selectedFile = ref<ApiVersionFunction | null>(null)
const isSaving = ref(false)
const saveError = ref<string | null>(null)
const saveSuccess = ref(false)
const editorRef = ref<any>(null)

// New file dialog state
const isNewViewDialogOpen = ref(false)
const newViewName = ref('')
const isCreatingView = ref(false)
const createViewError = ref<string | null>(null)

const viewBeingEdited = ref<ApiVersionFunction | null>(null)
const isEditingView = ref(false)

const validationErrors = ref<number>(0)
const validationWarnings = ref<number>(0)

// Toaster
const { success, error, warning, info } = useToaster();


const handleUpdateCode = (updatedCode: string) => {
    if (!selectedFile.value || !props.apiData) {
        saveError.value = 'No file selected or API data not available'
        return
    }

    // Update the local state immediately
    try {
        const updatedApiData = {
            ...props.apiData,
            version_files: props.apiData.version_files.map(func => 
                func.name === selectedFile.value?.name 
                    ? { ...func, content: updatedCode }
                    : func
            )
        }
        
        // Update the local state through parent
        props.updateApiData(updatedApiData)
        
        if (selectedFile.value) {
            selectedFile.value.content = updatedCode
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

const handleValidation = (markers: any) => {
    const errors = markers.filter((m: any) => m.severity >= 8) // Monaco.MarkerSeverity.Error = 8
    const warnings = markers.filter((m: any) => m.severity === 4) // Monaco.MarkerSeverity.Warning = 4
    
    validationErrors.value = errors.length
    validationWarnings.value = warnings.length
    
    
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


const currentFunctionCode = computed(() => {
    return selectedFile.value?.content ?? ''
})

// const handleSave = async (updatedCode: string) => {
//     if (!selectedFile.value || !props.apiData) {
//         saveError.value = 'No file selected or file data not available'
//         return
//     }

//     isSaving.value = true
//     saveError.value = null
//     saveSuccess.value = false

//     try {
//         const updatedApiData = {
//             ...props.apiData,
//             version_files: props.apiData.version_files.map(func => 
//                 func.name === selectedFile.value?.name 
//                     ? { ...func, content: updatedCode }
//                     : func
//             )
//         }

//         const response = await fetch(`/ajax/apis/${props.api_id}/code`, {
//             method: 'PUT',
//             headers: {
//                 'Content-Type': 'application/json',
//                 'Accept': 'application/json',
//                 'X-CSRF-TOKEN': getCsrfToken() || '',
//             },
//             body: JSON.stringify(updatedApiData)
//         })

//         if (!response.ok) {
//             const errorData = await response.json().catch(() => ({}))
//             throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
//         }

//         const result = await response.json()
        
//         // Update the local state through parent
//         props.updateApiData(result || updatedApiData)
        
//         if (selectedFile.value) {
//             selectedFile.value.content = updatedCode
//         }
        
//         saveSuccess.value = true
//         setTimeout(() => {
//             saveSuccess.value = false
//         }, 3000)

//     } catch (error) {
//         console.error('Save error:', error)
//         saveError.value = error instanceof Error ? error.message : 'Failed to save changes'
//     } finally {
//         isSaving.value = false
//     }
// }

const editFileName = (view: ApiVersionFunction) => {
  isEditingView.value = true
  viewBeingEdited.value = view
  newViewName.value = view.name
  isNewViewDialogOpen.value = true
}

const handleCreateNewFile = async () => {
    const name = newViewName.value.trim();

    if (!name || !props.apiData) {
        createViewError.value = 'Please enter a valid file/function name'
        return
    }

    let baseName = name
  if (props.api_type === 'python' ){
    if( !name.endsWith('.py')) {
        createViewError.value = 'Function name must end with ".py" (e.g., "process_data.py").';
        return
    }else{
        baseName = name.slice(0, -3)
    }
  }

  if (props.api_type === 'php' ){
    if(name.endsWith('.php')) {
        baseName = name.slice(0, -4)
    }
  }

    const validNamePattern = /^[a-zA-Z_][a-zA-Z0-9_]*$/;
    if (!validNamePattern.test(baseName)) {
        createViewError.value =
        'Invalid function name. Use letters, numbers, and underscores only, and do not start with a number';
        return;
    }


    // Check if file name already exists
    const existingFile = props.apiData.version_files.find(func => func.name === name)
    if (existingFile) {
        createViewError.value = 'A function/file with this name already exists'
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
            version_files: [...props.apiData.version_files, newFunction]
        }
        
        // Update the local state through parent
        props.updateApiData(updatedApiData)
        
        // Select the newly created function
        selectedFile.value = newFunction
        
        // Reset dialog state
        newViewName.value = ''
        isNewViewDialogOpen.value = false

    } catch (err: any) {
        console.error('Create view error:', err)
        createViewError.value = err instanceof Error ? err.message : 'Failed to create new function/file'
        error(createViewError.value, 'Error');
    } finally {
        isCreatingView.value = false
    }
}

const handleUpdateFileName = async () => {
  if (!props.apiData || !viewBeingEdited.value) {
    createViewError.value = 'No file selected for editing'
    return
  }

  const trimmedName = newViewName.value.trim()
  if (!trimmedName) {
    createViewError.value = 'File name cannot be empty'
    return
  }

  let baseName = trimmedName
  if (props.api_type === 'python' ){
    if( !trimmedName.endsWith('.py')) {
        createViewError.value = 'Function name must end with ".py" (e.g., "process_data.py").';
        return
    }else{
        baseName = trimmedName.slice(0, -3)
    }
  }

  if (props.api_type === 'php' ){
    if(trimmedName.endsWith('.php')) {
        baseName = trimmedName.slice(0, -4)
    }
  }


    const validNamePattern = /^[a-zA-Z_][a-zA-Z0-9_]*$/;
    if (!validNamePattern.test(baseName)) {
        createViewError.value =
        'Invalid function name. Use letters, numbers, and underscores only, and do not start with a number.';
        return;
    }

  // Prevent duplicates
  const nameExists = props.apiData.version_files.some(
    func => func.name === trimmedName && func !== viewBeingEdited.value
  )
  if (nameExists) {
    createViewError.value = 'A file with this name already exists'
    return
  }

  isCreatingView.value = true
  createViewError.value = null

  try {
    const updatedApiData = {
      ...props.apiData,
      version_files: props.apiData.version_files.map(file =>
        file.name === viewBeingEdited.value?.name
          ? { ...file, name: trimmedName }
          : file
      )
    }

    props.updateApiData(updatedApiData)

    // If editing currently selected function, update reference
    if (selectedFile.value?.name === viewBeingEdited.value.name) {
      selectedFile.value.name = trimmedName
    }

    success(`File name updated to "${trimmedName}"`, 'Function Updated')
    isNewViewDialogOpen.value = false
  } catch (e: any) {
    console.error('Update file name error:', e)
    createViewError.value = e.message || 'Failed to update file name'
  } finally {
    isCreatingView.value = false
    isEditingView.value = false
    viewBeingEdited.value = null
  }
}

const handleDeleteFile = async (file: ApiVersionFunction ) =>{
    if (!confirm(`Are you sure you want to delete the file "${file.name}"?`)) {
        return
    }

    if (!props.apiData) {
        console.error('API data not available')
        return
    }
    
    try {
        const updatedApiData = {
            ...props.apiData,
            version_files: props.apiData.version_files?.filter(existingFile => !(existingFile.name === file.name)) || []
        }

        // const responseData = await response.json()
        props.updateApiData(updatedApiData)
        selectedFile.value = null
        success(`File "${file.name}" deleted successfully`, 'File Deleted');

    } catch (err: any) {
        console.error('Error deleting route:', err)
        error(err.message || 'Error deleting file', 'Error');
    }
}

const resetNewViewDialog = () => {
    newViewName.value = ''
    createViewError.value = null
    isCreatingView.value = false
    isEditingView.value = false
    viewBeingEdited.value = null
}

// Handle search result selection
const handleSearchResult = (event: CustomEvent) => {
    const { category, name, data, searchQuery } = event.detail
    
    if (category === 'Files' && props.apiData?.version_files) {
        // Find the file by name
        const fileToSelect = props.apiData.version_files.find(file => file.name === name)
        if (fileToSelect) {
            selectedFile.value = fileToSelect
            // Scroll to the file in the list
            setTimeout(() => {
                const fileButton = document.querySelector(`[data-file-name="${name}"]`)
                if (fileButton) {
                    fileButton.scrollIntoView({ behavior: 'smooth', block: 'center' })
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
                // Find the editor that contains our file content
                const currentContent = selectedFile.value?.content || ''
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

// Add event listener for search results
onMounted(() => {
    window.addEventListener('search-result-selected', handleSearchResult as EventListener)
})

onUnmounted(() => {
    window.removeEventListener('search-result-selected', handleSearchResult as EventListener)
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
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
            
            <!-- Loading State -->
            <template v-if="loadingApiData">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
                        <p class="mt-2">Loading files...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading files/functions: {{ apiError }}</p>
                    </div>
                </div>
            </template>

            <!-- Files Content -->
            <template v-else-if="apiData?.version_files">
                <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-8 h-full">
                    <!-- Files List Sidebar -->
                   <aside class="max-w-xs lg:w-40 lg:min-w-40 lg:flex-shrink-0">
                        <!-- New View Button -->
                        <div class="mb-4">
                            <Dialog v-model:open="isNewViewDialogOpen" @update:open="resetNewViewDialog">
                                <DialogTrigger as-child>
                                    <Button variant="outline" class="w-full text-xs">
                                        + New File
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>{{ isEditingView ? 'Edit File Name' : 'Create New File' }}</DialogTitle>
                                    </DialogHeader>
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <Label for="function-name">File Name</Label>
                                            <Input
                                                id="function-name"
                                                v-model="newViewName"
                                                placeholder="Enter file name"
                                                :disabled="isCreatingView"
                                                @keyup.enter="isEditingView ? handleUpdateFileName() : handleCreateNewFile()"
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
                                                @click="isEditingView? handleUpdateFileName() : handleCreateNewFile()"
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

                        <!-- Files List -->
                        <nav class="flex flex-col space-y-1">
                            <div
                                v-for="item in apiData.version_files"
                                :key="item.name"
                                class="flex items-center gap-1 group"
                            >
                                <Button
                                    :data-file-name="item.name"
                                    variant="ghost"
                                    :class="[
                                        'justify-start', 
                                        'px-3', 
                                        'py-1', 
                                        'flex-1',
                                        'text-xs',
                                        selectedFile?.name === item.name ? 'bg-accent' : ''
                                    ]" 
                                    @click="selectedFile = item"             
                                >
                                    {{ item.name }}
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click.stop="editFileName(item)"
                                    class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-opacity"
                                >
                                    <Pencil :size="1" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click.stop="handleDeleteFile(item)"
                                    class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-opacity"
                                >
                                    <Trash2 :size="1" />
                                </Button>
                            </div>
                        </nav>
                    </aside>

                    <!-- Editor Area -->
                    <div class="flex-1 min-w-0">
                        <!-- Save status messages -->
                        <!-- <div v-if="saveError" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                            {{ saveError }}
                        </div>
                        <div v-if="saveSuccess" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                            Changes saved successfully!
                        </div> -->
                        
                        <!-- Code Editor -->
                        <Editor 
                            ref="editorRef"
                            v-if="selectedFile" 
                            :key="selectedFile.name"
                            :code="currentFunctionCode" 
                            :language="props.api_type === 'python' || props.api_type === 'php' ? props.api_type : 'php'"
                            :is-saving="isSaving"
                            :saveError="saveError??''"
                            :saveSuccess="saveSuccess"
                            @save="handleSave"
                            @update:code="handleUpdateCode"
                            @validate="handleValidation"
                        />
                        
                        <!-- No File Selected State -->
                        <div v-else class="text-muted-foreground text-sm p-4 text-center">
                            Select a file to view its code.
                        </div>
                    </div>
                </div>
            </template>

            <!-- No Files Available State -->
            <template v-else>
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <p>No files available for this API version.</p>
                    </div>
                </div>
            </template>
        </div>
    </div>
  
</template>