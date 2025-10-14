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
import { ArrowUpDown, ChevronDown, Plus, Trash2 } from 'lucide-vue-next'
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
    apiData: ApiData | null
    api: Api | null
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
const tempClassMethods = ref<{ name: string; params: string; content: string }[]>([])
const editingMethodIndex = ref<number | null>(null)

const newModelLoading = ref(false)
const newModelError = ref('')
const isEditMode = ref(false)
const editingModelIndex = ref<number | null>(null)
// const saveLoading = ref(false)
// const saveError = ref('')


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

// Content Editor Handlers
const openContentEditor = () => {
  tempContent.value = newModelForm.value.content
  contentEditorDialogOpen.value = true
}

const saveContentEditor = () => {
  newModelForm.value.content = tempContent.value
  contentEditorDialogOpen.value = false
}

const cancelContentEditor = () => {
  contentEditorDialogOpen.value = false
}

// Class Methods Editor Handlers
const openClassMethodsEditor = () => {
  tempClassMethods.value = JSON.parse(JSON.stringify(newModelForm.value.class_methods))
  classMethodsEditorDialogOpen.value = true
}

const saveClassMethodsEditor = () => {
  newModelForm.value.class_methods = JSON.parse(JSON.stringify(tempClassMethods.value))
  classMethodsEditorDialogOpen.value = false
}

const cancelClassMethodsEditor = () => {
  classMethodsEditorDialogOpen.value = false
}

const addClassMethod = () => {
  tempClassMethods.value.push({ name: '', params: 'self', content: '' })
}

const removeClassMethod = (index: number) => {
  tempClassMethods.value.splice(index, 1)
}

// Meta properties handlers
const addNewModelMetaProperty = () => {
  newModelForm.value.class_meta.push({ name: '', value: '' })
}

const removeNewModelMetaProperty = (index: number) => {
  newModelForm.value.class_meta.splice(index, 1)
}

const addNewModelClassMethods = () => {
  newModelForm.value.class_methods.push({ name: '', params: '', content: '' })
}

const removeNewModelClassMethod = (index: number) => {
  newModelForm.value.class_methods.splice(index, 1)
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

  if (!newModelForm.value.content.trim()) {
    newModelError.value = 'Model Content is required.'
    newModelLoading.value = false
    return
  }
  
  try {
    const newModel = {
      name: newModelForm.value.name,
      content: newModelForm.value.content,
      inheritance: newModelForm.value.inheritance,
      class_meta: newModelForm.value.class_meta.filter(meta => meta.name.trim() && meta.value.trim()),
      class_methods: newModelForm.value.class_methods ?? []
    }

    // Duplicate Model name check
    const existingModels = props.apiData.version_models || []
    const duplicate = existingModels.some((model, index) => {
        const sameName = model.name.trim() === newModel.name.trim()
        const isSameModel = isEditMode.value && index === editingModelIndex.value
        return sameName && !isSameModel
    })

    if (duplicate) {
        newModelError.value = `A model with name "${newModel.name}" already exists.`
        newModelLoading.value = false
        return
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

    // const response = await fetch(`/ajax/apis/${props.api_id}/code`, {
    //         method: 'PUT',
    //         headers: {
    //             'Content-Type': 'application/json',
    //             'Accept': 'application/json',
    //             'X-CSRF-TOKEN': getCsrfToken() || '',
    //         },
    //         body: JSON.stringify(updatedApiData)
    // })

    // if (!response.ok) {
    //   const errorData = await response.json().catch(() => ({}))
    //   throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
    // }

    // const responseData = await response.json()
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
        // console.log('Sending updatedApiData:', JSON.stringify(updatedApiData, null, 2))

        const response = await fetch(`/ajax/apis/${props.api_id}/code`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            body: JSON.stringify(updatedApiData)
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }

        const responseData = await response.json()
        props.updateApiData(responseData || updatedApiData)
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
    class_methods: model.class_methods ? JSON.parse(JSON.stringify(model.class_methods)) : []
  }
  newModelDialogOpen.value = true
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
            const route = row.original
            return h('div', { 'data-actions-cell': true }, [
                h(Button, {
                    variant: 'ghost',
                    size: 'sm',
                    onClick: (e: MouseEvent) => {
                        e.stopPropagation()
                        handleDelete(route)
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

// const formConfig = {
//     label: 'API Users',
//     description: 'A list of API users with their credentials and environment settings.',
//     name: "api-users-form",
//     files: false,
//     fields: [
//     {
//       "name": "name",
//       "label": "Model Name",
//       "type": "text"
//     },
//     {
//       "name": "inheritance",
//       "label": "Inheritance",
//       "type": "text",
//       "options":[]
//     },
//     {
//       "name": "class_meta",
//       "label": "Class ",
//       "type": "text",
      
//     },
//     {
//       "name": "content_preview",
//       "label": "Content",
//       "type": "text"
//     }
//   ]
// }

// const handleCustomAction = (actionData: { action: string; selectedRows: any[]; selectedData: any[] }) => {
//     console.log('Custom action triggered:', actionData);
    
//     switch (actionData.action) {
//         case 'create':
//             openNewModelDialog();
//             break;
//         case 'edit':
//             // Export functionality
//             // openEditModelDialog(actionData.selectedData[0]);
//             break;
        
//         default:
//             // info(Please implement the ${actionData.action} function, 'Action Not Implemented');
//     }
// };

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
                              <Input id="new-model-inheritance" v-model="newModelForm.inheritance" placeholder="models.Model" class="col-span-3" />
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
                                  />
                                  <Input 
                                    v-model="meta.value" 
                                    placeholder="Value" 
                                    class="col-span-4"
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

                            <!-- Content Section with Button -->
                            <div class="flex flex-col gap-4 border-t pt-4">
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

                            <!-- Class Methods Section with Button -->
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

                    <!-- Content Editor Dialog -->
                    <Dialog v-model:open="contentEditorDialogOpen">
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
                    </Dialog>

                    <!-- Class Methods Editor Dialog -->
                    <Dialog v-model:open="classMethodsEditorDialogOpen">
                      <DialogContent class="sm:max-w-6xl max-h-[90vh]">
                        <DialogHeader>
                          <DialogTitle aria-describedby="edit-class-methods">Edit Class Methods</DialogTitle>
                        </DialogHeader>
                        <div class="py-4 max-h-[70vh] overflow-y-auto space-y-4">
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
                                <Trash2 class="h-4 w-4" />
                              </Button>
                            </div>
                            <div>
                              <Label class="text-sm mb-2 block">Method Content</Label>
                              <Editor 
                                v-model:code="method.content" 
                                :language="'python'"
                                class="min-h-[200px] border rounded-md"
                              />
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