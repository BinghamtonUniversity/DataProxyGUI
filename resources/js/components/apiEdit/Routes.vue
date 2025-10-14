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

import { h, ref, computed, onMounted, onUnmounted } from 'vue'
import { getCsrfToken, valueUpdater } from '@/lib/utils'
import { ArrowUpDown, ChevronDown, Plus, Trash2, Settings } from 'lucide-vue-next'
import { type ApiData, RouteData, Api } from '@/types'
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
    highlightQuery?: string
    highlightTarget?: string
}

const props = defineProps<Props>()

// New Route Dialog
const verbDropdownOpen = ref(false)
const viewDropdownOpen = ref(false)
const newRouteDialogOpen = ref(false)
const newRouteForm = ref({
    description: '',
    path: '',
    verb: 'GET',
    view_name: '',
    required: [] as { name: string; example: string; description: string }[],
    optional: [] as { name: string; example: string; description: string }[]
})
const newRouteLoading = ref(false)
const newRouteError = ref('')
const isEditMode = ref(false)
const editingRouteIndex = ref<number | null>(null)

//Params dialog
const paramsDialogOpen = ref(false)
const paramsForm = ref({ required: [] as { name: string; example: string; description: string }[],
                         optional: [] as { name: string; example: string; description: string }[] })
const editingParamsRoute = ref<RouteData | null>(null)
const editingParamsIndex = ref<number | null>(null)

// Toaster
const { success, error, warning, info } = useToaster();

// Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

// console.log('Routes component mounted')

const openNewRouteDialog = () => {
  newRouteForm.value = {
    description: '',
    path: '',
    verb: 'GET',
    view_name: '',
    required: [],
    optional: []
  }
  newRouteError.value = ''
  newRouteDialogOpen.value = true
}

const closeNewRouteDialog = () => {
  newRouteDialogOpen.value = false
  newRouteError.value = ''
  isEditMode.value = false
  editingRouteIndex.value = null
}


const openParamsDialog = (route: RouteData, index: number) => {
  editingParamsRoute.value = route
  editingParamsIndex.value = index
  paramsForm.value = {
    required: route.required ? JSON.parse(JSON.stringify(route.required)) : [],
    optional: route.optional ? JSON.parse(JSON.stringify(route.optional)) : [],
  }
  paramsDialogOpen.value = true
}

const closeParamsDialog = () => {
  paramsDialogOpen.value = false
  editingParamsRoute.value = null
  editingParamsIndex.value = null
  paramsForm.value = { required: [], optional: [] }
}

const addRequiredParam = () => paramsForm.value.required.push({ name: '', example: '', description: '' })
const removeRequiredParam = (index: number) => paramsForm.value.required.splice(index, 1)

const addOptionalParam = () => paramsForm.value.optional.push({ name: '', example: '', description: '' })
const removeOptionalParam = (index: number) => paramsForm.value.optional.splice(index, 1)

const submitParams = async () => {
  if (!props.apiData || editingParamsIndex.value === null) return

  const updatedRoutes = [...props.apiData.version_urls]
  updatedRoutes[editingParamsIndex.value] = {
    ...updatedRoutes[editingParamsIndex.value],
    required: paramsForm.value.required.filter(p => p.name.trim()),
    optional: paramsForm.value.optional.filter(p => p.name.trim()),
  }

  const updatedApiData = { ...props.apiData, version_urls: updatedRoutes }
  props.updateApiData(updatedApiData)
  success('Parameters updated successfully', 'Updated')
  closeParamsDialog()
}

const submitNewRoute = async (e: Event) => {
    e.preventDefault()
    newRouteLoading.value = true
    newRouteError.value = ''
    
    if (!props.apiData) {
        newRouteError.value = 'API data not available'
        newRouteLoading.value = false
        return
    }

    
    try {
        const newRoute = {
            description: newRouteForm.value.description,
            path: newRouteForm.value.path,
            verb: newRouteForm.value.verb,
            view_name: newRouteForm.value.view_name,
            required: newRouteForm.value.required.filter(param => param.name.trim() && param.description.trim() && param.example.trim()),
            optional: newRouteForm.value.optional.filter(param => param.name.trim() && param.description.trim() && param.example.trim()),
        }

        // Duplicate verb + path check
        const existingRoutes = props.apiData.version_urls || []
        const duplicate = existingRoutes.some((route, index) => {
            const samePath = route.path.trim() === newRoute.path
            const sameVerb = route.verb.trim().toUpperCase() === newRoute.verb
            const isSameRoute = isEditMode.value && index === editingRouteIndex.value
            return samePath && sameVerb && !isSameRoute
        })

        if (duplicate) {
            newRouteError.value = `A route with path "${newRoute.path}" and verb "${newRoute.verb}" already exists.`
            newRouteLoading.value = false
            return
        }
        
        let updatedApiData
    
        if (isEditMode.value && editingRouteIndex.value !== null) {
            updatedApiData = {
                ...props.apiData,
                version_urls: props.apiData.version_urls?.map((route, index) => 
                index === editingRouteIndex.value 
                    ? { ...route, ...newRoute }
                    : route
                ) || []
            }
        } else {
            updatedApiData = {
                ...props.apiData,
                version_urls: [...(props.apiData.version_urls || []), newRoute]
            }
        }

        // const response = await fetch(`/ajax/apis/${props.api_id}/code`, {
        //     method: 'PUT',
        //     headers: {
        //         'Content-Type': 'application/json',
        //         'Accept': 'application/json',
        //         'X-CSRF-TOKEN': getCsrfToken() || '',
        //     },
        //     body: JSON.stringify(updatedApiData)
        // })

        // if (!response.ok) {
        //     const errorData = await response.json().catch(() => ({}))
        //     throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        // }

        // const responseData = await response.json()
        props.updateApiData(updatedApiData)
        if(isEditMode.value) {
            success('Updated successfully', 'Route Updated');
        } else {
            success('Created successfully', 'Route Created');
        }

        closeNewRouteDialog()
    } catch (err: any) {
        // console.error('Error saving route:', err)
        newRouteError.value = err.message || 'Error saving route'
        error(newRouteError.value, 'Error')
    } finally {
        newRouteLoading.value = false
    }
}

const handleDelete = async (route: RouteData) => {
    if (!confirm(`Are you sure you want to delete the route "${route.view_name}" (${route.verb} ${route.path})?`)) {
        return
    }

    if (!props.apiData) {
        console.error('API data not available')
        return
    }
    
    try {
        const updatedApiData = {
            ...props.apiData,
            version_urls: props.apiData.version_urls?.filter(existingRoute => 
                !(existingRoute.view_name === route.view_name && 
                  existingRoute.path === route.path && 
                  existingRoute.verb === route.verb)
            ) || []
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
        success(`Path "${route.path}-${route.verb}" deleted successfully`, 'Route Deleted');

    } catch (err: any) {
        console.error('Error deleting route:', err)
        error(err.message || 'Error deleting route', 'Error');
    }
}

const openEditRouteDialog = (route: RouteData, index: number) => {
  isEditMode.value = true
  editingRouteIndex.value = index
  newRouteForm.value = {
    description: route.description || '',
    path: route.path,
    verb: route.verb,
    view_name: route.view_name,
    required: route.required?.map(p => ({
      name: p.name,
      description: p.description || '',
      example: p.example || ''
    })) || [],
    optional: route.required?.map(p => ({
      name: p.name,
      description: p.description || '',
      example: p.example || ''
    })) || []
  }
  newRouteDialogOpen.value = true
}

// Define table columns
const columns: ColumnDef<RouteData>[] = [
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
    accessorKey: 'view_name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['View Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { 
      class: 'font-medium text-blue-600',
      innerHTML: highlightText(row.getValue('view_name'), props.highlightQuery || '')
    }),
  },
  {
    accessorKey: 'path',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Path', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('code', { 
      class: 'bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm font-mono',
      innerHTML: highlightText(row.getValue('path'), props.highlightQuery || '')
    }, row.getValue('path')),
  },
  {
    accessorKey: 'verb',
    header: 'HTTP Method',
    cell: ({ row }) => {
      const verb = row.getValue('verb') as string
      const verbColors: Record<string, string> = {
        'GET': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
        'POST': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
        'PUT': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
        'DELETE': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
        'PATCH': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
      }
      return h('span', { 
        class: `inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${verbColors[verb] || 'bg-gray-100 text-gray-800'}`,
        innerHTML: highlightText(verb, props.highlightQuery || '')
      })
    },},
    {
        accessorKey: 'verb',
        header: 'HTTP Method',
        cell: ({ row }) => {
        const verb = row.getValue('verb') as string
        const verbColors: Record<string, string> = {
            'GET': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'POST': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'PUT': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
            'DELETE': 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'PATCH': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300'
        }
        return h('span', { 
            class: `inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${verbColors[verb] || 'bg-gray-100 text-gray-800'}`
        }, verb)
        },
    },
    {
        id: 'parameters',
        header: 'Parameters',
        cell: ({ row }) => {
        const route = row.original
        const requiredParams = route.required || []
        const optionalParams = route.optional || []
        
        if (requiredParams.length === 0 && optionalParams.length === 0) {
            return h('div', { class: 'text-gray-500 text-sm' }, 'No parameters')
        }
        
        return h('div', { class: 'flex flex-wrap gap-1' }, [
            ...requiredParams.map(param => 
            h('span', { 
                key: param.name,
                class: 'inline-flex items-center rounded px-2 py-1 text-xs bg-red-50 text-red-700 dark:bg-red-900 dark:text-red-300 font-medium'
            }, param.name)
            ),
            ...optionalParams.map(param => 
            h('span', { 
                key: param.name,
                class: 'inline-flex items-center rounded px-2 py-1 text-xs bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'
            }, param.name)
            )
        ])
        },
    },
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row }) => {
            const route = row.original
            const index = row.index
            return h('div', { class: 'flex gap-2' }, [
            h(Button, {
                variant: 'ghost',
                size: 'sm',
                onClick: (e: MouseEvent) => {
                e.stopPropagation()
                openParamsDialog(route, index)
                },
                class: 'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white'
            }, {
                default: () => [h(Settings, { class: 'h-4 w-4' })]
            }),
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
            }),
            ])
        },
    },
  
]


// Create table instance
const table = computed(() => {
  if (!props.apiData?.version_urls) {
    return null
  }

  return useVueTable({
    data: props.apiData.version_urls,
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
const pathFilterValue = computed({
  get: () => table.value?.getColumn('path')?.getFilterValue() as string || '',
  set: (value: string) => table.value?.getColumn('path')?.setFilterValue(value)
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
                        <p class="mt-2">Loading routes...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading routes: {{ apiError }}</p>
                    </div>
                </div>
            </template>

            <!-- Data Table -->
            <template v-else-if="apiData?.version_urls && table">
                <div class="w-full">        
                    <!-- Table Controls -->
                    <div class="flex items-center py-4">
                        <Input
                            class="max-w-sm"
                            placeholder="Filter by path"
                            v-model="pathFilterValue"
                        />
                        
                        <Dialog v-model:open="newRouteDialogOpen">
                            <DialogTrigger as-child>
                                <Button class="ml-4 text-green-600" variant="outline" @click="openNewRouteDialog">
                                    <Plus class="mr-2 h-4 w-4" />
                                    New Route
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-md">
                                <form @submit="submitNewRoute" class="space-y-6">
                                    <DialogHeader>
                                        <DialogTitle>{{ isEditMode ? 'Edit Route' : 'Create New Route' }}</DialogTitle>
                                    </DialogHeader>
                                    <div class="grid gap-4">
                                        <div>
                                            <Label for="route-path" class="mb-1">Path</Label>
                                            <Input id="route-path" v-model="newRouteForm.path" required placeholder="/api/endpoint" />
                                        </div>
                                        <div>
                                            <Label for="route-verb" class="mb-1">HTTP Method</Label>
                                            <DropdownMenu v-model:open="verbDropdownOpen">
                                                <DropdownMenuTrigger as-child>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    class="w-full justify-between"
                                                >
                                                    {{ newRouteForm.verb || 'Select method' }}
                                                    <ChevronDown class="ml-1 h-4 w-4" />
                                                </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="start" class="w-full">
                                                    <DropdownMenuItem
                                                        v-for="method in ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']"
                                                        :key="method"
                                                        @click="newRouteForm.verb = method"
                                                        :class="['w-full', {'font-semibold text-blue-600': newRouteForm.verb === method }]"
                                                    >
                                                        {{ method }}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                        <div>
                                            <Label for="route-view" class="mb-1">View Name</Label>
                                            <DropdownMenu v-model:open="viewDropdownOpen">
                                                <DropdownMenuTrigger as-child>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    class="w-full justify-between"
                                                >
                                                    {{ newRouteForm.view_name || 'Select view' }}
                                                    <ChevronDown class="ml-1 h-4 w-4" />
                                                </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="start" class="w-full">
                                                    <DropdownMenuItem
                                                        v-for="view_name in props.apiData?.version_views?.map(v => v.name) || []"
                                                        :key="view_name"
                                                        @click="newRouteForm.view_name = view_name"
                                                        :class="['w-full', {'font-semibold text-blue-600': newRouteForm.verb === view_name }]"
                                                    >
                                                        {{ view_name }}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                            <!-- <Label for="route-view" class="mb-1">View Name</Label>
                                            <Input id="route-view" v-model="newRouteForm.view_name" required placeholder="view_function_name" /> -->
                                        </div>
                                        
                                        <!-- <div>
                                            <Label for="required-params" class="mb-1">Required Parameters</Label>
                                            <Input id="required-params" v-model="newRouteForm.required" placeholder="param1, param2 (comma separated)" />
                                        </div> -->
                                        <!-- <div>
                                            <Label for="optional-params" class="mb-1">Optional Parameters</Label>
                                            <Input id="optional-params" v-model="newRouteForm.optional" placeholder="param3, param4 (comma separated)" />
                                        </div> -->
                                        <div v-if="newRouteError" class="text-red-600 text-sm">{{ newRouteError }}</div>
                                    </div>
                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary" type="button" @click="closeNewRouteDialog">Cancel</Button>
                                        </DialogClose>
                                        <Button type="submit" variant="default" :disabled="newRouteLoading">
                                            <span v-if="newRouteLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
                                            <span v-else>{{ isEditMode ? 'Save' : 'Create' }}</span>
                                        </Button>
                                    </DialogFooter>
                                </form>
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
                                        :data-route-name="row.original.view_name"
                                        class="cursor-pointer hover:bg-muted/50"
                                        @click="openEditRouteDialog(row.original, index)"
                                    >
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>                                                    
                                </template>
                          
                                <TableRow v-else>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No routes found.
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
                        <p>No routes available for this API version.</p>
                    </div>
                </div>
            </template>
        </div>
    </div>
    <Dialog v-model:open="paramsDialogOpen">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
            <DialogTitle>
                Manage Parameters — {{ editingParamsRoute?.verb }} {{ editingParamsRoute?.path }}
            </DialogTitle>
            </DialogHeader>

            <!-- Required Parameters -->
            <section class="mt-4">
            <h3 class="text-md font-semibold mb-2">Required Parameters</h3>
            <div v-if="paramsForm.required.length">
                <div v-for="(param, i) in paramsForm.required" :key="'req-'+i" class="grid grid-cols-9 gap-2 items-center mb-2">
                <Input v-model="param.name" placeholder="Name" class="col-span-3" />
                <Input v-model="param.example" placeholder="Example" class="col-span-2" />
                <Input v-model="param.description" placeholder="Description" class="col-span-3" />
                <Button variant="destructive" size="sm" @click="removeRequiredParam(i)">×</Button>
                </div>
            </div>
            <div v-else class="text-sm text-gray-500">No required parameters defined.</div>
            <Button variant="outline" size="sm" class="mt-2" @click="addRequiredParam">
                <Plus class="h-4 w-4 mr-1" /> Add Required
            </Button>
            </section>

            <!-- Optional Parameters -->
            <section class="mt-6">
            <h3 class="text-md font-semibold mb-2">Optional Parameters</h3>
            <div v-if="paramsForm.optional.length">
                <div v-for="(param, i) in paramsForm.optional" :key="'opt-'+i" class="grid grid-cols-9 gap-2 items-center mb-2">
                <Input v-model="param.name" placeholder="Name" class="col-span-3" />
                <Input v-model="param.example" placeholder="Example" class="col-span-2" />
                <Input v-model="param.description" placeholder="Description" class="col-span-3" />
                <Button variant="destructive" size="sm" @click="removeOptionalParam(i)">×</Button>
                </div>
            </div>
            <div v-else class="text-sm text-gray-500">No optional parameters defined.</div>
            <Button variant="outline" size="sm" class="mt-2" @click="addOptionalParam">
                <Plus class="h-4 w-4 mr-1" /> Add Optional
            </Button>
            </section>

            <DialogFooter class="mt-6">
            <DialogClose as-child>
                <Button variant="secondary" @click="closeParamsDialog">Cancel</Button>
            </DialogClose>
            <Button variant="default" @click="submitParams">Save</Button>
            </DialogFooter>
        </DialogContent>
        </Dialog>
    <Toaster />
</template>

