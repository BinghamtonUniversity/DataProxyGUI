<script setup lang="ts">
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, ChevronDown, Plus, Trash2, Code, Pencil } from 'lucide-vue-next'
import { h, ref, computed, watch } from 'vue'
import { getCsrfToken, valueUpdater } from '@/lib/utils'

import { type ApiData, ModelData, Api } from '@/types'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
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
}

const props = defineProps<Props>()

// Toaster
const { success, error, warning, info } = useToaster();

// --- Dialog State and Handlers ---
const newModelDialogOpen = ref(false)
const contentEditorDialogOpen = ref(false)
const classMethodsEditorDialogOpen = ref(false)

const newModelForm = ref({
  name: '',
  content: '',
  inheritance: 'models.Model',
  class_meta: [] as { name: string; value: string }[],
  class_methods: [] as { name: string; params: string; content: string }[]
})

// Temporary state for editors
const tempContent = ref('')
const tempClassMethods = ref<{ name: string; params: string[]; content: string }[]>([])
const editingMethodIndex = ref<number | null>(null)

const newModelLoading = ref(false)
const newModelError = ref('')
const isEditMode = ref(false)
const editingModelIndex = ref<number | null>(null)
// const saveLoading = ref(false)
// const saveError = ref('')

// Code Editor Dialog State
const codeEditorDialogOpen = ref(false)
const selectedModel = ref<ModelData | null>(null)
const selectedSection = ref<'content' | { type: 'method', index: number } | null>(null)
const currentCode = ref('')
const isSavingCode = ref(false)
const saveCodeError = ref<string | null>(null)
const saveCodeSuccess = ref(false)

const isNewMethodDialogOpen = ref(false)
const newMethod = ref({
  name: '',
  params: '',
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
const methodBeingEdited = ref<{name: string, params: string, content:string }| null>(null)
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
    newMethod.value.params = ''
    newMethod.value.content = ''
    createMethodError.value = null
    isCreatingMethod.value = false
    isEditingMethod.value = false
    methodBeingEdited.value = null
}

const selectSection = (section: 'content' | { type: 'method', index: number }) => {
  if (!selectedModel.value) return
  
  selectedSection.value = section
  
  if (section === 'content') {
    currentCode.value = selectedModel.value.content || ''
  } else if (typeof section === 'object' && section.type === 'method') {
    const method = selectedModel.value.class_methods?.[section.index]
    currentCode.value = method?.content || ''
  }
}

const handleCodeSave = async (updatedCode: string) => {
  if (!selectedModel.value || !props.apiData) {
    saveCodeError.value = 'No model selected or API data not available'
    return
  }

  isSavingCode.value = true
  saveCodeError.value = null
  saveCodeSuccess.value = false

  try {
    const updatedModel = { ...selectedModel.value }
    
    if (selectedSection.value === 'content') {
      updatedModel.content = updatedCode
    } else if (typeof selectedSection.value === 'object' && selectedSection.value?.type === 'method') {
      if (updatedModel.class_methods) {
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
    setTimeout(() => {
      saveCodeSuccess.value = false
    }, 3000)

    success('Code saved successfully', 'Success')
  } catch (error) {
    console.error('Save error:', error)
    saveCodeError.value = error instanceof Error ? error.message : 'Failed to save changes'
  } finally {
    isSavingCode.value = false
  }
}

// Content Editor Handlers
// const openContentEditor = () => {
//   tempContent.value = newModelForm.value.content
//   contentEditorDialogOpen.value = true
// }

// const saveContentEditor = () => {
//   newModelForm.value.content = tempContent.value
//   contentEditorDialogOpen.value = false
// }

// const cancelContentEditor = () => {
//   contentEditorDialogOpen.value = false
// }

// // Class Methods Editor Handlers
// const openClassMethodsEditor = () => {
//   tempClassMethods.value = JSON.parse(JSON.stringify(newModelForm.value.class_methods))
//   classMethodsEditorDialogOpen.value = true
// }

// const saveClassMethodsEditor = () => {
//   newModelForm.value.class_methods = JSON.parse(JSON.stringify(tempClassMethods.value))
//   classMethodsEditorDialogOpen.value = false
// }

// const cancelClassMethodsEditor = () => {
//   classMethodsEditorDialogOpen.value = false
// }

// const addClassMethod = () => {
//   tempClassMethods.value.push({ name: '', params: [], content: '' })
// }

// const removeClassMethod = (index: number) => {
//   tempClassMethods.value.splice(index, 1)
// }

// Meta properties handlers
const addNewModelMetaProperty = () => {
  newModelForm.value.class_meta.push({ name: '', value: '' })
}

const removeNewModelMetaProperty = (index: number) => {
  newModelForm.value.class_meta.splice(index, 1)
}

// const addNewModelClassMethods = () => {
//   newModelForm.value.class_methods.push({ name: '', params: '', content: '' })
// }

// const removeNewModelClassMethod = (index: number) => {
//   newModelForm.value.class_methods.splice(index, 1)
// }

const submitNewModel = async (e: Event) => {
  e.preventDefault()
  newModelLoading.value = true
  newModelError.value = ''
  
  if (!props.apiData) {
    newModelError.value = 'API data not available'
    newModelLoading.value = false
    return
  }

  // if (!newModelForm.value.content.trim()) {
  //   newModelError.value = 'Model Content is required.'
  //   newModelLoading.value = false
  //   return
  // }
  
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

    const newModel = {
      name: rawName,
      content: newModelForm.value.content,
      inheritance: newModelForm.value.inheritance,
      class_meta: newModelForm.value.class_meta.filter(meta => meta.name.trim() && meta.value.trim()),
      class_methods: []
    }
    
    let updatedApiData

    if (isEditMode.value && editingModelIndex.value !== null) {
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
    console.log('Updated API Data:', updatedApiData)
    props.updateApiData(updatedApiData)
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
    class_methods: []
  }
  newModelDialogOpen.value = true
}

const editMethodName = (method: { name: string; params: string; content: string }) => {
    isEditingMethod.value = true
    methodBeingEdited.value = method
    newMethod.value.name = method.name
    newMethod.value.params = method.params
    // newMethod.value.content = method.content
    createMethodError.value = null
    isNewMethodDialogOpen.value = true
}

const handleCreateNewMethod = async () => {
    const name = newMethod.value.name.trim();

    if (!name || !props.apiData) {
        createMethodError.value = 'Please enter a valid method name'
        return
    }

    // Validate function name syntax
    const validNamePattern = /^[a-zA-Z_][a-zA-Z0-9_]*$/;
    if (!validNamePattern.test(name)) {
        createMethodError.value = 'Invalid method name. Use letters, numbers, and underscores only, and do not start with a number.'
        return;
    }

    // Check if function name already exists
    const existingMethod = selectedModel.value?.class_methods.find(m => m.name === name)
    if (existingMethod) {
        createMethodError.value = 'A method with this name already exists'
        return
    }

    // Validate comma-separated parameters
    const rawParams = newMethod.value.params.trim()

    // If it contains spaces but no commas
    if (rawParams.includes(' ') && !rawParams.includes(',')) {
        createMethodError.value = 'Parameters must be comma-separated (e.g. self, param1)'
        return
    }

    const paramsArray = rawParams
        .split(',')
        .map(p => p.trim())
        .filter(p => p)

    // Handle empty case
    if (paramsArray.length === 0) {
        createMethodError.value = 'Please enter at least one parameter'
        return
    }

    isCreatingMethod.value = true
    createMethodError.value = null

    try {
        const methodToCreate = {
            name,
            params: paramsArray.join(', '),
            content: ``
        }
        
        if (!selectedModel.value) {
          createMethodError.value = 'No model selected.'
          error(createMethodError.value, 'Error');
          return
        }
        selectedModel.value?.class_methods?.push
        ? selectedModel.value.class_methods.push(methodToCreate)
        : selectedModel.value.class_methods = [methodToCreate]
        
        
        
        // Update the local state through parent
        // props.updateApiData(updatedApiData)
        
        
        // Reset dialog state
        newMethod.value.name = ''
        newMethod.value.params = ''
        newMethod.value.content = ''
        // paramsInputValue.value = ''
        isNewMethodDialogOpen.value = false

    } catch (e) {
        console.error('Create view error:', e)
        createMethodError.value = e instanceof Error ? e.message : 'Failed to create new method'
        error(createMethodError.value, 'Error');
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

  // Check if function name already exists
    const existingMethod = selectedModel.value?.class_methods.find(m => m.name === trimmedName && m !== methodBeingEdited.value)
    if (existingMethod) {
        createMethodError.value = 'A method with this name already exists'
        return
    }

    // Validate comma-separated parameters
    const rawParams = newMethod.value.params.trim()

    // If it contains spaces but no commas
    if (rawParams.includes(' ') && !rawParams.includes(',')) {
        createMethodError.value = 'Parameters must be comma-separated (e.g. self, param1)'
        return
    }

    const paramsArray = rawParams
        .split(',')
        .map(p => p.trim())
        .filter(p => p)

    // Handle empty case
    if (paramsArray.length === 0) {
        createMethodError.value = 'Please enter at least one parameter'
        return
    }

  isCreatingMethod.value = true
  createMethodError.value = null

  try {
    // Update method in selected model
    if (selectedModel.value && selectedModel.value.class_methods) {
      const methodIndex = selectedModel.value.class_methods.findIndex(m => m === methodBeingEdited.value)
      if (methodIndex !== -1) {
        selectedModel.value.class_methods[methodIndex].name = trimmedName
        selectedModel.value.class_methods[methodIndex].params = paramsArray.join(', ')
        // selectedModel.value.class_methods[methodIndex].content = newMethod.value.content
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

const handleDeleteMethod = (method: { name: string; params: string; content: string }) => {
    if (!selectedModel.value || !selectedModel.value.class_methods) {
        return
    }

    if (!confirm(`Are you sure you want to delete the method "${method.name}"?`)) {
        return
    }

    selectedModel.value.class_methods = selectedModel.value.class_methods.filter(m => m.name !== method.name)
}

// --- Table Definition ---
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

// Define table columns
const columns: ColumnDef<ModelData>[] = [
  {
    id: 'select',
    header: ({ table }) => h(Checkbox, {
      'modelValue': table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
      'onUpdate:modelValue': value => table.toggleAllPageRowsSelected(!!value),
      'ariaLabel': 'Select all',
    }),
    cell: ({ row }) => h('div', { onClick: e => e.stopPropagation() }, [
        h(Checkbox, {
            'modelValue': row.getIsSelected(),
            'onUpdate:modelValue': value => row.toggleSelected(!!value),
            'ariaLabel': 'Select row',
        })
    ]),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Model Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { class: 'font-medium text-blue-600' }, row.getValue('name')),
  },
  {
    accessorKey: 'inheritance',
    header: 'Inheritance',
    cell: ({ row }) => {
      const inheritance = row.getValue('inheritance') as string
      return h('div', { 
        class: 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-300' 
      }, inheritance)
    },
  },
  {
    id: 'class_meta',
    header: 'Meta Properties',
    cell: ({ row }) => {
      const classMeta = row.original.class_meta
      if (!classMeta || classMeta.length === 0) {
        return h('div', { class: 'text-gray-500 text-sm' }, 'No meta')
      }
      return h('div', { class: 'flex flex-wrap gap-1' }, 
        classMeta.map((meta, index) => 
          h('span', { 
            key: index,
            class: 'inline-flex items-center rounded px-2 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
          }, `${meta.name}: ${meta.value}`)
        )
      )
    },
  },
  {
    id: 'content_preview',
    header: 'Content',
    cell: ({ row }) => {
            const content = row.original.content
            if (!content) {
                return h('div', { class: 'text-xs text-gray-500' }, 'No content')
            }
                
            const preview = content.substring(0, 50) + (content.length > 50 ? '...' : '')
            
            return h('div', { 
            class: 'max-w-xs text-xs text-gray-600 dark:text-gray-400 truncate font-mono' 
            }, preview)
        },
    },
    {
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
            const model = row.original
            const index = row.index
            return h('div', { 'data-actions-cell': true }, [
                h(Button, {
                  variant: 'ghost',
                  size: 'sm',
                  onClick: (e: MouseEvent) => {
                  e.stopPropagation()
                  openEditor(model, index)
                  },
                  class: 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'
              }, {
                  default: () => [h(Code, { class: 'h-4 w-4' })]
              }),
                h(Button, {
                    variant: 'ghost',
                    size: 'sm',
                    onClick: (e: MouseEvent) => {
                        e.stopPropagation()
                        handleDelete(model)
                    },
                    class: 'text-red-600 hover:text-red-800 hover:bg-red-50 dark:hover:bg-red-900/20'
                }, {
                    default: () => [h(Trash2, { class: 'h-4 w-4' })]
                })
            ])
        },
    }
]

const table = computed(() => {
  if (!props.apiData?.version_models) {
    return null
  }

  return useVueTable({
    data: props.apiData.version_models,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
    state: {
      get sorting() { return sorting.value },
      get columnFilters() { return columnFilters.value },
      get columnVisibility() { return columnVisibility.value },
      get rowSelection() { return rowSelection.value },
    },
  })
})


// Computed properties
const headerGroups = computed(() => table.value?.getHeaderGroups() || [])
const tableRows = computed(() => table.value?.getRowModel().rows || [])
const hidableColumns = computed(() => table.value?.getAllColumns().filter(column => column.getCanHide()) || [])
const nameFilterValue = computed({
  get: () => table.value?.getColumn('name')?.getFilterValue() as string || '',
  set: (value: string) => table.value?.getColumn('name')?.setFilterValue(value)
})
const selectedRowsCount = computed(() => table.value?.getFilteredSelectedRowModel().rows.length || 0)
const totalRowsCount = computed(() => table.value?.getFilteredRowModel().rows.length || 0)
const canPreviousPage = computed(() => table.value?.getCanPreviousPage() || false)
const canNextPage = computed(() => table.value?.getCanNextPage() || false)

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
            

            <!-- Data Table -->
            <template v-else-if="apiData?.version_models && table">
                <!-- Table Controls -->
                <div class="flex items-center py-4">
                    <Input
                        class="max-w-sm"
                        placeholder="Filter by model name..."
                        v-model="nameFilterValue"
                    />
                    
                    <!-- Main Model Dialog -->
                    <Dialog v-model:open="newModelDialogOpen">
                      <DialogTrigger as-child>
                        <Button class="ml-4 text-green-600" variant="outline" @click="openNewModelDialog">
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

                            <!-- <div class="flex flex-col gap-4 border-t pt-4">
                              <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium">Model Content</h3>
                                <Button 
                                  type="button" 
                                  variant="outline"
                                  @click="openContentEditor"
                                >
                                  Edit Content
                                </Button>
                              </div>
                              <div class="text-sm text-gray-500 px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800">
                                <span v-if="newModelForm.content">
                                  Content defined ({{ newModelForm.content.split('\n').length }} lines)
                                </span>
                                <span v-else>No content defined</span>
                              </div>
                            </div>
\
                            <div class="flex flex-col gap-4 border-t pt-4">
                              <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium">Class Methods</h3>
                                <Button 
                                  type="button" 
                                  variant="outline"
                                  @click="openClassMethodsEditor"
                                >
                                  Edit Methods
                                </Button>
                              </div>
                              <div class="text-sm text-gray-500 px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800">
                                <span v-if="newModelForm.class_methods.length > 0">
                                  {{ newModelForm.class_methods.length }} method(s) defined
                                </span>
                                <span v-else>No methods defined</span>
                              </div>
                            </div>-->
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

                    <!-- Content Editor Dialog -->
                    <!-- <Dialog v-model:open="contentEditorDialogOpen">
                      <DialogContent class="sm:max-w-5xl max-h-[90vh]">
                        <DialogHeader>
                          <DialogTitle aria-describedby="edit-model-content">Edit Model Content</DialogTitle>
                        </DialogHeader>
                        <div class="py-4 h-[60vh]">
                          <Editor 
                            v-model:code="tempContent" 
                            :language="'python'"
                            class="h-full border rounded-md"
                          />
                        </div>
                        <DialogFooter class="gap-2">
                          <Button variant="secondary" @click="cancelContentEditor">
                            Cancel
                          </Button>
                          <Button variant="default" @click="saveContentEditor">
                            Save Content
                          </Button>
                        </DialogFooter>
                      </DialogContent>
                    </Dialog> -->

                    <!-- Class Methods Editor Dialog -->
                    <!-- <Dialog v-model:open="classMethodsEditorDialogOpen">
                      <DialogContent class="sm:max-w-6xl max-h-[90vh] flex flex-col p-0">
                        <DialogHeader class="px-6 pt-6">
                          <DialogTitle aria-describedby="edit-class-methods">Edit Class Methods</DialogTitle>
                        </DialogHeader>
                        <div class="flex-1 overflow-y-auto px-6 py-4 sapce-y-4 min-h-0">
                          <div v-if="tempClassMethods.length === 0" class="text-sm text-gray-500 px-3 py-2 border rounded-md bg-gray-50 dark:bg-gray-800">
                            No class methods defined.
                          </div>
                          
                          <div v-for="(method, index) in tempClassMethods" :key="index" class="border rounded-lg p-4 space-y-3">
                            <div class="flex items-center gap-2">
                              <div class="flex-1 grid grid-cols-2 gap-2">
                                <div>
                                  <Label class="text-sm">Method Name</Label>
                                  <Input 
                                    v-model="method.name" 
                                    placeholder="method_name" 
                                  />
                                </div>
                                <div>
                                  <Label class="text-sm">Parameters</Label>
                                  <Input 
                                    v-model="method.params" 
                                    placeholder="self, param1, param2" 
                                  /> 
                                </div>
                              </div>
                              <Button 
                                type="button" 
                                variant="destructive" 
                                size="sm"
                                @click="removeClassMethod(index)"
                                class="mt-5"
                              >
                                <Trash2 class="h-2 w-2" />
                              </Button>
                            </div>
                            <div>
                              <Label class="text-sm mb-2 block">Method Content</Label>
                              <div style="height: 200px;">
                                <Editor 
                                  v-model:code="method.content" 
                                  :language="'python'"
                                />
                              </div>
                            </div>
                          </div>

                          <Button 
                            type="button" 
                            variant="outline" 
                            @click="addClassMethod"
                            class="w-full"
                          >
                            <Plus class="mr-2 h-4 w-4" />
                            Add Class Method
                          </Button>
                        </div>
                        <DialogFooter class="gap-2">
                          <Button variant="secondary" @click="cancelClassMethodsEditor">
                            Cancel
                          </Button>
                          <Button variant="default" @click="saveClassMethodsEditor">
                            Save Methods
                          </Button>
                        </DialogFooter>
                      </DialogContent>
                    </Dialog> -->
          
                    <!-- Code Editor Dialog (Functions-like structure) -->
                    <Dialog v-model:open="codeEditorDialogOpen">
                      <DialogContent class="sm:max-w-[90vw] max-h-[90vh] flex flex-col p-0">
                        <DialogHeader class="px-6 pt-6 pb-4">
                          <DialogTitle aria-describedby="code-editor-dialog">
                            Edit Model Content: {{ selectedModel?.name }}
                          </DialogTitle>
                        </DialogHeader>
                        
                        <div class="flex-1 overflow-hidden px-6 pb-6">
                          <div class="flex space-y-8 md:space-y-0 lg:space-y-0 lg:space-x-8 h-full">
                            <!-- Sidebar with sections -->
                            <aside class="max-w-xs lg:w-48 lg:min-w-48 lg:flex-shrink-0">
                              <!-- New View Button -->
                              <div class="mb-4">
                                  <Dialog v-model:open="isNewMethodDialogOpen" @update:open="resetNewMethodDialog">
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
                                                  <Input
                                                      id="method-params"
                                                      v-model="newMethod.params"
                                                      placeholder="Enter parameters comma-separated (e.g. self, param1)"
                                                      :disabled="isCreatingMethod"
                                                  />
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
                                                      @click="isEditingMethod ? handleUpdateMethodName() :handleCreateNewMethod()"
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
                                      {{ method.name || `Method ${index + 1}` }}
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

                            <!-- Editor Area -->
                            <div class="flex-1 min-w-0 border rounded-lg">
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
                          </div>
                        </div>
                      </DialogContent>
                    </Dialog>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="ml-auto">
                                Columns <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuCheckboxItem
                                v-for="column in hidableColumns"
                                :key="column.id"
                                class="capitalize"
                                :model-value="column.getIsVisible()"
                                @update:model-value="(value) => column.toggleVisibility(!!value)"
                            >
                                {{ column.id.replace('_', ' ') }}
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <!-- Data Table -->
                <div class="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow v-for="headerGroup in headerGroups" :key="headerGroup.id">
                                <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                    <FlexRender 
                                        v-if="!header.isPlaceholder" 
                                        :render="header.column.columnDef.header" 
                                        :props="header.getContext()" 
                                    />
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-if="tableRows.length">
                                <TableRow 
                                    v-for="(row, index) in tableRows" 
                                    :key="row.id" 
                                    :data-state="row.getIsSelected() && 'selected'"
                                    class="cursor-pointer hover:bg-muted/50"
                                    @click="openEditModelDialog(row.original, index)"
                                >
                                    <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                        <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                    </TableCell>
                                </TableRow>                                                    
                            </template>

                            <TableRow v-else>
                                <TableCell :colspan="columns.length" class="h-24 text-center">
                                    No models found.
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div class="flex items-center justify-end space-x-2 py-4">
                    <div class="flex-1 text-sm text-muted-foreground">
                        {{ selectedRowsCount }} of {{ totalRowsCount }} row(s) selected.
                    </div>
                    <div class="space-x-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!canPreviousPage"
                            @click="table?.previousPage()"
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!canNextPage"
                            @click="table?.nextPage()"
                        >
                            Next
                        </Button>
                    </div>
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