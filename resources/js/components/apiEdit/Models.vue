<script setup lang="ts">
import { ArrowUpDown, ChevronDown, Plus, Trash2, Code, Pencil } from 'lucide-vue-next'
import { h, ref, computed, watch, onUnmounted } from 'vue'
import { getCsrfToken, valueUpdater } from '@/lib/utils'

import { type ApiData, ModelData, Api } from '@/types'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'


import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import Editor from '@/pages/Editor.vue'
import DataGrid from '@/components/datagrid/DataGrid.vue'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';

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
}

const props = defineProps<Props>()

// Toaster
const { success, error, warning, info } = useToaster();

// --- Dialog State and Handlers ---
const newModelDialogOpen = ref(false)

const newModelForm = ref({
  name: '',
  content: '',
  inheritance: 'models.Model',
  class_meta: [] as { name: string; value: string }[],
  class_methods: [] as { name: string; params: string[]; content: string }[]
})

const newModelLoading = ref(false)
const newModelError = ref('')
const isEditMode = ref(false)
const editingModelIndex = ref<number | null>(null)
const isSaving = ref(false)

const saveSuccessTimeout = ref<ReturnType<typeof setTimeout> | null>(null)
const isSavingTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

// Code Editor Dialog State
const codeEditorDialogOpen = ref(false)
const selectedModel = ref<ModelData | null>(null)
const selectedSection = ref<'properties' | 'content' | { type: 'method', index: number } | null>(null)
const currentCode = ref('')
const isSavingCode = ref(false)
const saveCodeError = ref<string | null>(null)
const saveCodeSuccess = ref(false)

const isNewMethodDialogOpen = ref(false)
const newMethod = ref<{
  name: string
  params: string[]
  content: string
}>({
  name: '',
  params: [],
  content: ''
})

// const paramsInput = computed({
//   get: () => newMethod.value.params.join(', '),
//   set: (val: string) => {
//   }
// })

// Add a temporary storage for the raw input
// const paramsInputValue = ref('')
const isCreatingMethod = ref(false)
const createMethodError = ref<string | null>(null)

//Edit method name state
const methodBeingEdited = ref<{name: string, params: string[], content:string }| null>(null)
const isEditingMethod = ref(false)

const openNewModelDialog = () => {
  newModelForm.value = {
    name: '',
    content: '',
    inheritance: 'models.Model',
    class_meta: [
      { name: 'db_table', value: '' } 
    ],
    class_methods: []
  }
  newModelError.value = ''
  isEditMode.value = false
  editingModelIndex.value = null
  newModelDialogOpen.value = true
}

const closeNewModelDialog = () => {
  newModelDialogOpen.value = false
  newModelError.value = ''
  isEditMode.value = false
  editingModelIndex.value = null
}

const openEditor = (model: ModelData, index: number) => {
  selectedModel.value = model
  // Default to content section
  selectedSection.value = 'content'
  currentCode.value = model.content || ''
  codeEditorDialogOpen.value = true
}

const resetNewMethodDialog = () => {
    newMethod.value.name = ''
    newMethod.value.params = []
    newMethod.value.content = ''
    createMethodError.value = null
    isCreatingMethod.value = false
    isEditingMethod.value = false
    methodBeingEdited.value = null
}

const selectSection = (section: 'properties' | 'content' | { type: 'method', index: number }) => {
  if (!selectedModel.value) return
  
  selectedSection.value = section
  
  if (section === 'content') {
    currentCode.value = selectedModel.value.content || ''
  } else if (typeof section === 'object' && section.type === 'method') {
    const method = selectedModel.value.class_methods?.[section.index]
    currentCode.value = method?.content || ''
  }
}

watch(selectedModel, (newModel, oldModel) => {
  // Only reset to properties if it's a different model (not just an update)
  if (newModel && (!oldModel || newModel.name !== oldModel?.name) && !isSaving.value) {
    selectedSection.value = 'properties';
  }
});

const handleCodeSave = async (updatedCode: string) => {
  if (!selectedModel.value || !props.apiData) {
    saveCodeError.value = 'No model selected or API data not available'
    return
  }
  // Clear any existing timeouts
  if (saveSuccessTimeout.value) {
    clearTimeout(saveSuccessTimeout.value)
    saveSuccessTimeout.value = null
  }
  if (isSavingTimeout.value) {
    clearTimeout(isSavingTimeout.value)
    isSavingTimeout.value = null
  }

  isSaving.value = true
  isSavingCode.value = true
  saveCodeError.value = null
  saveCodeSuccess.value = false

  try {
    const updatedModel = { ...selectedModel.value }
    
    if (selectedSection.value === 'content') {
      updatedModel.content = updatedCode
    } else if (typeof selectedSection.value === 'object' && selectedSection.value?.type === 'method') {
      if (
        Array.isArray(updatedModel.class_methods) &&
        updatedModel.class_methods.length > 0 &&
        selectedSection.value?.index != null &&
        updatedModel.class_methods[selectedSection.value.index]
      ) {
        updatedModel.class_methods[selectedSection.value.index].content = updatedCode
      }
    }

    const updatedApiData = {
      ...props.apiData,
      version_models: props.apiData.version_models?.map(model => 
        model.name === selectedModel.value?.name ? updatedModel : model
      ) || []
    }

    props.updateApiData(updatedApiData)
    selectedModel.value = updatedModel
    currentCode.value = updatedCode
    
    saveCodeSuccess.value = true
    saveSuccessTimeout.value = setTimeout(() => {
      saveCodeSuccess.value = false
      saveSuccessTimeout.value = null
    }, 3000)

    // success('Code saved successfully', 'Success')
  } catch (error) {
    console.error('Save error:', error)
    saveCodeError.value = error instanceof Error ? error.message : 'Failed to save changes'
  } finally {
    isSavingCode.value = false
    isSavingTimeout.value = setTimeout(() => {
      isSaving.value = false
      isSavingTimeout.value = null
    }, 100)
  }
}

// Meta properties handlers
const addNewModelMetaProperty = () => {
  newModelForm.value.class_meta.push({ name: '', value: '' })
}

const removeNewModelMetaProperty = (index: number) => {
  newModelForm.value.class_meta.splice(index, 1)
}

// Paramaters handlers for new model
const addMethodParam = () => {
  newMethod.value.params.push('')
}

const removeMethodParam = (index: number) => {
  newMethod.value.params.splice(index, 1)
}

const submitNewModel = async (e: Event) => {
  e.preventDefault()
  newModelLoading.value = true
  newModelError.value = ''
  
  if (!props.apiData) {
    newModelError.value = 'API data not available'
    newModelLoading.value = false
    return
  }
  
  try {
    const rawName = newModelForm.value.name.trim();
    const validNamePattern = /^[A-Z][A-Za-z0-9_]*$/;
    if (!validNamePattern.test(rawName)) {
      newModelError.value =
        'Invalid model name. Start with a capital letter and use letters, numbers, or underscores only (e.g. "UserProfile").';
      newModelLoading.value = false;
      return;
    }

    // Duplicate Model name check
    const existingModels = props.apiData.version_models || [];
    const duplicate = existingModels.some((model, index) => {
      const sameName =
        model.name.trim().toLowerCase() === rawName.toLowerCase();
      const isSameModel =
        isEditMode.value && index === editingModelIndex.value;
      return sameName && !isSameModel;
    });

    if (duplicate) {
      newModelError.value = `A model with name "${rawName}" already exists.`;
      newModelLoading.value = false;
      return;
    }

    const isEditing = isEditMode.value && editingModelIndex.value !== null

    const newModel = {
      name: rawName,
      content: isEditing ? selectedModel.value?.content || '' : '',
      inheritance: newModelForm.value.inheritance,
      class_meta: newModelForm.value.class_meta.filter(meta => meta.name.trim() && meta.value.trim()),
      class_methods: isEditing ? selectedModel.value?.class_methods || [] : []
    }
    
    let updatedApiData

    if (isEditing) {
      // Edit existing model
      updatedApiData = {
        ...props.apiData,
        version_models: props.apiData.version_models?.map((model, index) => 
          index === editingModelIndex.value 
            ? { ...model, ...newModel }
            : model
        ) || []
      }
    } else {
      // Add new model
      updatedApiData = {
        ...props.apiData,
        version_models: [...(props.apiData.version_models || []), newModel]
      }
    }

    props.updateApiData(updatedApiData)
    if (selectedModel.value) {
      selectedModel.value = newModel
    }
    if(isEditMode.value) {
      success('Updated successfully', 'Model Updated');
    } else {
      success('Created successfully', 'Model Created');
    }
    closeNewModelDialog()
  } catch (err: any) {
    console.error('Error saving model:', err)
    newModelError.value = err.message || 'Error saving model'
    error(newModelError.value, 'Error')

  } finally {
    newModelLoading.value = false
  }
}

const handleDelete = async (model: ModelData) => {
    if (!confirm(`Are you sure you want to delete the resource "${model.name}"?`)) {
        return
    }

    if (!props.apiData) {
        console.error('API data not available')
        return
    }
    
    try {
        const updatedApiData = {
            ...props.apiData,
            version_models: props.apiData.version_models?.filter(existingModel => !(existingModel.name === model.name)) || []
        }
       
        props.updateApiData(updatedApiData)
        success(`Model "${model.name}" deleted successfully`, 'Model Deleted');
        
    } catch (err: any) {
        console.error('Error deleting model:', err)
        error(err.message || 'Error deleting model', 'Error');
    }
}

const openEditModelDialog = (model: ModelData, index: number) => {
  isEditMode.value = true
  editingModelIndex.value = index
  newModelForm.value = {
    name: model.name || '',
    content: model.content || '',
    inheritance: model.inheritance || 'models.Model',
    class_meta: model.class_meta ? JSON.parse(JSON.stringify(model.class_meta)) : [],
    class_methods: selectedModel.value?.class_methods || []
  }
  newModelDialogOpen.value = true
}

const editMethodName = (method: { name: string; params: string[]; content: string }) => {
    isEditingMethod.value = true
    methodBeingEdited.value = method
    newMethod.value.name = method.name
    newMethod.value.params = method.params ?? []
    // newMethod.value.content = method.content
    createMethodError.value = null
    isNewMethodDialogOpen.value = true
}

// Validation Functions for method creation and editing
const validateMethodName = (name: string): string | null => {
  if (!name) return 'Please enter a valid method name'

  const validNamePattern = /^[a-zA-Z_][a-zA-Z0-9_]*$/
  if (!validNamePattern.test(name)) {
    return 'Invalid method name. Use letters, numbers, and underscores only, and do not start with a number.'
  }

  return null
}

const validateAndNormalizeParams = (rawParams: string[]): { error?: string, params?: string[] } => {

  //if rawParams includes 'self', error
  if(rawParams.includes('self')) {
    return { error: 'The parameter "self" is not allowed. It is automatically included in instance methods.' }
  }

  return { params: rawParams }
}


const handleCreateNewMethod = async () => {
  const name = newMethod.value.name.trim()

  if (!name || !props.apiData) {
    createMethodError.value = 'Please enter a valid method name'
    return
  }

  // Validate method name
  const nameError = validateMethodName(name)
  if (nameError) {
    createMethodError.value = nameError
    return
  }

  // Duplicate check
  const existingMethod = selectedModel.value?.class_methods?.find(m => m.name === name)
  if (existingMethod) {
    createMethodError.value = 'A method with this name already exists'
    return
  }

  // Validate and normalize parameters
  const { error: paramError, params } = validateAndNormalizeParams(newMethod.value.params)
  if (paramError) {
    createMethodError.value = paramError
    return
  }

  isCreatingMethod.value = true
  createMethodError.value = null

  try {
    const methodToCreate = {
      name,
      params: params ?? [],
      content: ``
    }

    if (!selectedModel.value) {
      createMethodError.value = 'No model selected.'
      error(createMethodError.value, 'Error')
      return
    }

    if (selectedModel.value.class_methods?.push) {
      selectedModel.value.class_methods.push(methodToCreate)
    } else {
      selectedModel.value.class_methods = [methodToCreate]
    }

    // Reset form and close dialog
    newMethod.value.name = ''
    newMethod.value.params = []
    newMethod.value.content = ''
    isNewMethodDialogOpen.value = false
  } catch (e) {
    console.error('Create method error:', e)
    createMethodError.value = e instanceof Error ? e.message : 'Failed to create new method'
    error(createMethodError.value, 'Error')
  } finally {
    isCreatingMethod.value = false
  }
}


const handleUpdateMethodName = async () => {
  if (!props.apiData || !methodBeingEdited.value) {
    createMethodError.value = 'No method selected for editing'
    return
  }

  const trimmedName = newMethod.value.name.trim()
  if (!trimmedName) {
    createMethodError.value = 'Method name cannot be empty'
    return
  }

  // Validate method name
  const nameError = validateMethodName(trimmedName)
  if (nameError) {
    createMethodError.value = nameError
    return
  }

  // Duplicate check
  const existingMethod = selectedModel.value?.class_methods?.find(
    m => m.name === trimmedName && m !== methodBeingEdited.value
  )
  if (existingMethod) {
    createMethodError.value = 'A method with this name already exists'
    return
  }

  // Validate and normalize parameters
  const { error: paramError, params } = validateAndNormalizeParams(newMethod.value.params)
  if (paramError) {
    createMethodError.value = paramError
    return
  }

  isCreatingMethod.value = true
  createMethodError.value = null

  try {
    // Update the method
    if (selectedModel.value?.class_methods) {
      const methodIndex = selectedModel.value.class_methods.findIndex(m => m === methodBeingEdited.value)
      if (methodIndex !== -1) {
        selectedModel.value.class_methods[methodIndex].name = trimmedName
        selectedModel.value.class_methods[methodIndex].params = params || []
      }
    }

    isNewMethodDialogOpen.value = false
  } catch (e: any) {
    console.error('Update method name error:', e)
    createMethodError.value = e.message || 'Failed to update method name'
  } finally {
    isCreatingMethod.value = false
    isEditingMethod.value = false
    methodBeingEdited.value = null
  }
}


const handleDeleteMethod = (method: { name: string; params: string[]; content: string }) => {
    if (!selectedModel.value || !selectedModel.value.class_methods) {
        return
    }

    if (!confirm(`Are you sure you want to delete the method "${method.name}"?`)) {
        return
    }

    selectedModel.value.class_methods = selectedModel.value.class_methods?.filter(m => m.name !== method.name)
}

// Clean up timeouts when component unmounts
onUnmounted(() => {
  if (saveSuccessTimeout.value) {
    clearTimeout(saveSuccessTimeout.value)
  }
  if (isSavingTimeout.value) {
    clearTimeout(isSavingTimeout.value)
  }
})
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
            
            <!-- Loading State -->
            <template v-if="loadingApiData">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
                        <p class="mt-2">Loading models...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading models: {{ apiError }}</p>
                    </div>
                </div>
            </template>

            <template v-else-if="apiData?.version_models">
              <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-8 h-full">
                    <!-- Models List Sidebar (Left) -->
                  <aside class="max-w-xs lg:w-48 lg:min-w-48 lg:flex-shrink-0">
                    <div class="mb-4">
                      <!-- Model Dialog -->
                      <Dialog v-model:open="newModelDialogOpen">
                        <DialogTrigger as-child>
                          <Button class="ml-4 text-green-600 text-xs" variant="outline" @click="openNewModelDialog">
                            <Plus class="mr-2 h-4 w-4" />
                            New Model
                          </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-3xl">
                          <form @submit="submitNewModel" class="space-y-6">
                            <DialogHeader>
                              <DialogTitle aria-describedby="dialog-title">{{ isEditMode ? 'Edit Model' : 'Create New Model' }}</DialogTitle>
                            </DialogHeader>
                            <div class="grid gap-6 py-4 max-h-[70vh] overflow-y-auto pr-6">
                              <!-- Basic Model Info -->
                              <div class="grid grid-cols-4 items-center gap-4">
                                <Label for="new-model-name" class="text-right">Name</Label>
                                <Input id="new-model-name" v-model="newModelForm.name" required placeholder="Model name" class="col-span-3" />
                              </div>
                              <div class="grid grid-cols-4 items-center gap-4">
                                <Label for="new-model-inheritance" class="text-right">Inheritance</Label>
                                <Input id="new-model-inheritance" v-model="newModelForm.inheritance" required placeholder="models.Model" class="col-span-3" />
                              </div>
                              
                              <!-- Meta Properties Section -->
                              <div class="flex flex-col gap-4 border-t pt-4">
                                <h3 class="text-lg font-medium">Meta Properties</h3>
                                <div v-if="newModelForm.class_meta.length > 0" class="space-y-3">
                                  <div class="grid grid-cols-9 items-center gap-2">
                                    <Label class="col-span-4 text-sm font-semibold">Name</Label>
                                    <Label class="col-span-4 text-sm font-semibold">Value</Label>
                                  </div>
                                  <div v-for="(meta, index) in newModelForm.class_meta" :key="index" class="grid grid-cols-9 items-center gap-2">
                                    <Input 
                                      v-model="meta.name" 
                                      placeholder="Name" 
                                      class="col-span-3"
                                      required
                                    />
                                    <Input 
                                      v-model="meta.value" 
                                      placeholder="Value" 
                                      class="col-span-4"
                                      required
                                    />
                                    <Button 
                                      type="button" 
                                      variant="destructive" 
                                      size="sm"
                                      @click="removeNewModelMetaProperty(index)"
                                      class="col-span-1"
                                    >
                                      ×
                                    </Button>
                                  </div>
                                </div>
                                <div v-else class="text-sm text-gray-500 px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800">
                                  No meta properties defined.
                                </div>
                                <Button 
                                  type="button" 
                                  variant="outline" 
                                  size="sm"
                                  @click="addNewModelMetaProperty"
                                  class="self-start"
                                >
                                  + Add Meta Property
                                </Button>
                              </div>

                            </div> 
                              
                            <!-- Error Display -->
                            <div v-if="newModelError" class="text-red-600 text-sm">
                              {{ newModelError }}
                            </div>

                            <DialogFooter class="gap-2">
                              <DialogClose as-child>
                                <Button variant="secondary" type="button" @click="closeNewModelDialog" :disabled="newModelLoading">
                                  Cancel
                                </Button>
                              </DialogClose>
                              <Button type="submit" variant="default" :disabled="newModelLoading">
                                <span v-if="newModelLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
                                <span v-else>{{ isEditMode ? 'Save Changes' : 'Create Model' }}</span>
                              </Button>
                            </DialogFooter>
                          </form>
                        </DialogContent>
                      </Dialog>       
                    </div>
                      <nav class="flex flex-col space-y-1">
                        <div
                            v-for="(item,index) in apiData.version_models"
                            :key="item.name"
                            class="flex items-center gap-1 group"
                        >
                            <Button
                                :data-model-name="item.name"
                                variant="ghost"
                                :class="[
                                    'justify-start', 
                                    'px-3', 
                                    'py-1', 
                                    'flex-1',
                                    'text-xs',
                                    selectedModel?.name === item.name ? 'bg-accent' : ''
                                ]" 
                                @click="
                                  selectedModel = item;
                                  selectSection('properties');
                                "             
                            >
                                {{ item.name }}
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="openEditModelDialog(item, index)"
                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-opacity"
                            >
                                <Pencil :size="1" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="handleDelete(item)"
                                class="h-7 w-7 p-0 opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-opacity"
                            >
                                <Trash2 :size="1" />
                            </Button>
                            
                        </div>
                    </nav>
                  </aside>

                  <!-- Main Content Area (Center) -->
                  <div class="flex-1 min-w-0">
                    <template v-if="selectedModel">
                      <div class="border rounded-lg h-full flex flex-col">
                        
                        <div class="flex-1 overflow-auto p-6">
                          <!-- Properties View -->
                          <template v-if="selectedSection === 'properties'">
                            <div class="space-y-6 max-w-3xl">
                              <!-- Inheritance Section -->
                              <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                                <h3 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300">Inheritance</h3>
                                <div class="bg-white dark:bg-gray-900 rounded border p-3">
                                  <code class="text-sm">{{ selectedModel.inheritance }}</code>
                                </div>
                              </div>

                              <!-- Class Meta Section -->
                              <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
                                <h3 class="text-sm font-semibold mb-3 text-gray-700 dark:text-gray-300">Class Meta Properties</h3>
                                <div v-if="selectedModel.class_meta && selectedModel.class_meta.length > 0" class="space-y-2">
                                  <div v-for="(meta, index) in selectedModel.class_meta" :key="index" 
                                       class="bg-white dark:bg-gray-900 rounded border p-3 flex items-start gap-4">
                                    <div class="flex-1">
                                      <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Name</div>
                                      <code class="text-sm font-medium">{{ meta.name }}</code>
                                    </div>
                                    <div class="flex-1">
                                      <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Value</div>
                                      <code class="text-sm">{{ meta.value }}</code>
                                    </div>
                                  </div>
                                </div>
                                <div v-else class="text-sm text-gray-500 bg-white dark:bg-gray-900 rounded border p-3">
                                  No meta properties defined
                                </div>
                              </div>
                            </div>
                          </template>

                          <!-- Editor View -->
                          <template v-else>
                            <div class="h-full">
                              <Editor 
                                v-if="selectedSection"
                                :code="currentCode" 
                                :language="'python'"
                                :is-saving="isSavingCode"
                                :saveError="saveCodeError || ''"
                                :saveSuccess="saveCodeSuccess"
                                @save="handleCodeSave"
                              />
                            </div>
                          </template>
                        </div>
                      </div>
                    </template>
                    <template v-else>
                      <div class="flex items-center justify-center h-full">
                        <div class="text-center text-gray-500">
                          <p>Select a model to view its content</p>
                        </div>
                      </div>
                    </template>
                  </div>

                  <!-- Content & Methods Sidebar (Right) -->
                  <aside class="max-w-xs lg:w-48 lg:min-w-48 lg:flex-shrink-0" v-if="selectedModel">
                    <!-- New Method Button -->
                    <div class="mb-4">
                      <Dialog v-model:open="isNewMethodDialogOpen" @update:open="resetNewMethodDialog" @escapeKeyDown.prevent>
                        <DialogTrigger as-child>
                          <Button variant="outline" class="w-full text-xs">
                            + Add Class Method
                          </Button>
                        </DialogTrigger>
                        <DialogContent class="sm:max-w-md">
                          <DialogHeader>
                            <DialogTitle>{{ isEditingMethod ? 'Edit Method Name' : 'Create New Class Method' }}</DialogTitle>
                          </DialogHeader>
                          <div class="space-y-4">
                            <div class="space-y-2">
                              <Label for="method-name">Method Name</Label>
                              <Input
                                id="method-name"
                                v-model="newMethod.name"
                                placeholder="Enter method name"
                                :disabled="isCreatingMethod"
                              />
                              <Label for="method-params">Method Parameters</Label>
                              <!-- <Input
                                id="method-params"
                                v-model="newMethod.params"
                                placeholder="Enter parameters comma-separated (e.g. param1, param2)"
                                :disabled="isCreatingMethod"
                              /> -->
                              <div class="flex flex-col gap-4 border-t pt-4">
                                <h3 class="text-lg font-medium">Method Parameters</h3>
                                <div v-if="newMethod.params.length > 0" class="space-y-3">
                                  <div class="grid grid-cols-9 items-center gap-2">
                                    <Label class="col-span-4 text-sm font-semibold">Param</Label>
                                  </div>
                                  <div v-for="(param, index) in newMethod.params" :key="index" class="grid grid-cols-9 items-center gap-2">
                                    <Input 
                                      v-model="newMethod.params[index]" 
                                      placeholder="Param" 
                                      class="col-span-3"
                                      required
                                    />
                                    <Button 
                                      type="button" 
                                      variant="destructive" 
                                      size="sm"
                                      @click="removeMethodParam(index)"
                                      class="col-span-1"
                                    >
                                      ×
                                    </Button>
                                  </div>
                                </div>
                                <div v-else class="text-sm text-gray-500 px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800">
                                  No parameters defined.
                                </div>
                                <Button 
                                  type="button" 
                                  variant="outline" 
                                  size="sm"
                                  @click="addMethodParam"
                                  class="self-start"
                                >
                                  <Plus>Add</Plus> 
                                </Button>
                              </div>

                              
                            </div>
                            
                            <div v-if="createMethodError" class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                              {{ createMethodError }}
                            </div>
                            
                            <div class="flex justify-end space-x-2">
                              <Button 
                                variant="outline" 
                                @click="isNewMethodDialogOpen = false"
                                :disabled="isCreatingMethod"
                              >
                                Cancel
                              </Button>
                              <Button 
                                @click="isEditingMethod ? handleUpdateMethodName() : handleCreateNewMethod()"
                                :disabled="!newMethod.name.trim() || isCreatingMethod"
                              >
                                <div v-if="isCreatingMethod" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                {{ isCreatingMethod 
                                  ? (isEditingMethod ? 'Updating...' : 'Creating...') 
                                  : (isEditingMethod ? 'Update' : 'Create') 
                                }}
                              </Button>
                            </div>
                          </div>
                        </DialogContent>
                      </Dialog>
                    </div>

                    <!-- Content and Methods list Section -->
                    <nav class="flex flex-col space-y-1">
                      <Button
                        variant="ghost"
                        :class="[
                          'justify-start px-3 py-2 text-xs',
                          selectedSection === 'content' ? 'bg-accent' : ''
                        ]"
                        @click="selectSection('content')"
                      >
                        Model Content
                      </Button>
                      
                      <!-- Class Methods -->
                      <div v-if="selectedModel?.class_methods && selectedModel.class_methods.length > 0">
                        <div class="px-3 py-2 text-xs font-semibold text-muted-foreground">
                          Class Methods
                        </div>
                        <div
                          v-for="(method, index) in selectedModel.class_methods"
                          :key="method.name"
                          class="flex items-center gap-1 group"
                        >
                          <Button
                            variant="ghost"
                            :class="[
                              'justify-start px-3 py-1 flex-1 text-xs',
                              typeof selectedSection === 'object' && 
                              selectedSection?.type === 'method' && 
                              selectedSection?.index === index ? 'bg-accent' : ''
                            ]"
                            @click="selectSection({ type: 'method', index })"
                          >
                            {{ method.name || `Method ${index + 1}` }} {{ method.params ? `(${method.params.join(',')})` : '()' }}
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            @click.stop="editMethodName(method)"
                            class="h-4 w-4 p-0 opacity-0 group-hover:opacity-100 text-blue-600 hover:text-blue-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-opacity"
                          >
                            <Pencil :size="1" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            @click.stop="handleDeleteMethod(method)"
                            class="h-4 w-4 p-0 opacity-0 group-hover:opacity-100 text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 transition-opacity"
                          >
                            <Trash2 :size="1" />
                          </Button>
                        </div>
                      </div>
                    </nav>
                  </aside>
              </div>
            </template>

            <!-- No Data State -->
            <template v-else>
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <p>No models available for this API version.</p>
                    </div>
                </div>
            </template>
        </div>
    </div>
    <Toaster />  
</template>