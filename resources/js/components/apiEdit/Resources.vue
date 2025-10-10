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
import { h, ref, computed, onMounted, onUnmounted } from 'vue'
import { getCsrfToken, valueUpdater } from '@/lib/utils'

import { type ApiData, type ResourceData } from '@/types'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
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
  DialogContent,
  DialogTrigger,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose,
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';

interface Props {
    api_id: string
    api_type: string
    apiData: ApiData | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
    highlightQuery?: string
    highlightTarget?: string
}

const props = defineProps<Props>()

const newResourceDialogOpen = ref(false)
const newResourceForm = ref({
  name: '',
  type: '',
  model_name: ''
})
const newResourceLoading = ref(false)
const newResourceError = ref('')
const isEditMode = ref(false)
const editingResourceIndex = ref<number | null>(null)

// Toaster
const { success, error, warning, info } = useToaster();

// New Resource Dialog handlers
const openNewResourceDialog = () => {
  newResourceForm.value = {
    name: '',
    type: '',
    model_name: ''
  }
  newResourceError.value = ''
  isEditMode.value = false
  editingResourceIndex.value = null
  newResourceDialogOpen.value = true
}

const closeNewResourceDialog = () => {
  newResourceDialogOpen.value = false
  newResourceError.value = ''
  isEditMode.value = false
  editingResourceIndex.value = null
}

const submitNewResource = async (e: Event) => {
  e.preventDefault()
  newResourceLoading.value = true
  newResourceError.value = ''
  
  if (!props.apiData) {
    newResourceError.value = 'API data not available'
    newResourceLoading.value = false
    return
  }
  
  try {
    const newResource = {
      name: newResourceForm.value.name,
      type: newResourceForm.value.type,
      model_name: newResourceForm.value.model_name
    }
    
    let updatedApiData

    if (isEditMode.value && editingResourceIndex.value !== null) {
      // Edit existing resource
      updatedApiData = {
        ...props.apiData,
        resources: props.apiData.resources?.map((resource, index) => 
          index === editingResourceIndex.value 
            ? { ...resource, ...newResource }
            : resource
        ) || []
      }
    } else {
      // Add new resource
      updatedApiData = {
        ...props.apiData,
        resources: [...(props.apiData.resources || []), newResource]
      }
    }
    // console.log('Updated API Data:', updatedApiData)
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
    if(isEditMode.value) {
      success('Updated successfully', 'Resource Updated');
    } else {
      success('Created successfully', 'Resource Created');
    }
    closeNewResourceDialog()
  } catch (err: any) {
    // console.error('Error saving resource:', err)
    newResourceError.value = err.message || 'Error saving resource'
    error(newResourceError.value, 'Error')
  } finally {
    newResourceLoading.value = false
  }
}

const handleDelete = async (resource: ResourceData) => {
    if (!confirm(`Are you sure you want to delete the resource "${resource.name}"?`)) {
        return
    }

    if (!props.apiData) {
        console.error('API data not available')
        return
    }
    
    try {
        const updatedApiData = {
            ...props.apiData,
            resources: props.apiData.resources?.filter(res => !(res.name === resource.name)) || []
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
        success(`Resource "${resource.name}" deleted successfully`, 'Resource Deleted');
    } catch (err: any) {
        console.error('Error deleting route:', err)
        error(err.message || 'Error deleting resource', 'Error');
    }
}

// Edit resource handler
const openEditResourceDialog = (resource: any, index: number) => {
  isEditMode.value = true
  editingResourceIndex.value = index
  newResourceForm.value = {
    name: resource.name || '',
    type: resource.type || 'Model',
    model_name: resource.model_name || ''
  }
  newResourceDialogOpen.value = true
}


// Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

// Define table columns
const columns: ColumnDef<ResourceData>[] = [
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
      }, () => ['Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { 
      class: 'font-medium text-blue-600',
      innerHTML: highlightText(row.getValue('name'), props.highlightQuery || '')
    }),
  },
  {
    accessorKey: 'type',
    header: 'Type',
    cell: ({ row }) => {
      const type = row.getValue('type') as string
      return h('div', { 
        class: 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-300',
        innerHTML: highlightText(type, props.highlightQuery || '')
      })
    },
  },
  {
    accessorKey: 'model_name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Model Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { 
      class: 'font-medium',
      innerHTML: highlightText(row.getValue('model_name'), props.highlightQuery || '')
    }),
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
    },
]

// Create table instance
const table = computed(() => {
  if (!props.apiData?.resources || !Array.isArray(props.apiData.resources)) {
    return null
  }

  return useVueTable({
    data: props.apiData.resources as ResourceData[],
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

// Function to highlight text in UI elements
const highlightText = (text: string, query: string) => {
    if (!query || !text) return text
    
    const regex = new RegExp(`(${query})`, 'gi')
    return text.replace(regex, '<mark class="search-highlight">$1</mark>')
}
</script>

<style>
.search-highlight {
    background-color: #ffeb3b !important;
    color: #000 !important;
    border-radius: 2px;
    padding: 1px 2px;
}

.search-highlight-item {
    background-color: #ffeb3b !important;
    border-radius: 4px;
    transition: background-color 0.3s ease;
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
                        <p class="mt-2">Loading resources...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading resources: {{ apiError }}</p>
                    </div>
                </div>
            </template>

            <!-- Data Table -->
            <template v-else-if="apiData?.resources && Array.isArray(apiData.resources) && table">
                <div class="w-full">
                    <!-- Table Controls -->
                    <div class="flex items-center py-4">
                        <Input
                            class="max-w-sm"
                            placeholder="Filter by name..."
                            v-model="nameFilterValue"
                        />
                        
                        <Dialog v-model:open="newResourceDialogOpen">
                            <DialogTrigger as-child>
                                <Button class="ml-4 text-green-600" variant="outline" @click="openNewResourceDialog">
                                <Plus class="mr-2 h-4 w-4" />
                                New Resource
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-md">
                                <form @submit="submitNewResource" class="space-y-6">
                                <DialogHeader>
                                    <DialogTitle>{{ isEditMode ? 'Edit Resource' : 'Create New Resource' }}</DialogTitle>
                                </DialogHeader>
                                <div class="grid gap-4">
                                    <div>
                                    <Label for="resource-name" class="mb-1">Name</Label>
                                    <Input id="resource-name" v-model="newResourceForm.name" required placeholder="Resource name" />
                                    </div>
                                    <div>
                                    <Label for="resource-type" class="mb-1">Type</Label>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="w-full justify-between"
                                        >
                                            {{ newResourceForm.type || 'Select type' }}
                                            <ChevronDown class="ml-1 h-4 w-4" />
                                        </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="start" class="w-full">
                                        <DropdownMenuItem
                                            v-for="type in ['Model', 'Password', 'Other']"
                                            :key="type"
                                            @click="newResourceForm.type = type"
                                            :class="['w-full', {'font-semibold text-blue-600': newResourceForm.type === type }]"
                                        >
                                            {{ type }}
                                        </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                    </div>
                                    <div v-if="newResourceForm.type === 'Model'">
                                        <Label for="model-name" class="mb-1">Model Name</Label>
                                        <Input id="model-name" v-model="newResourceForm.model_name" placeholder="Model name" />
                                    </div>
                                    <div v-if="newResourceError" class="text-red-600 text-sm">{{ newResourceError }}</div>
                                </div>
                                <DialogFooter class="gap-2">
                                    <DialogClose as-child>
                                    <Button variant="secondary" type="button" @click="closeNewResourceDialog">Cancel</Button>
                                    </DialogClose>
                                    <Button type="submit" variant="default" :disabled="newResourceLoading">
                                    <span v-if="newResourceLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
                                    <span v-else>{{ isEditMode ? 'Save' : 'Create' }}</span>
                                    </Button>
                                </DialogFooter>
                                </form>
                            </DialogContent>
                            </Dialog>
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
                                        v-for="(row,index) in tableRows" 
                                        :key="row.id" 
                                        :data-state="row.getIsSelected() && 'selected'"
                                        :data-resource-name="row.original.name"
                                        class="cursor-pointer hover:bg-muted/50"
                                        @click="openEditResourceDialog(row.original, index)"
                                    >
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>
                                </template>
                                <TableRow v-else>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No resources found.
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
                </div>
            </template>

            <!-- No Data State -->
            <template v-else>
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <p>No resources available for this API version.</p>
                    </div>
                </div>
            </template>
        </div>        
    </div>
    <Toaster />
</template>