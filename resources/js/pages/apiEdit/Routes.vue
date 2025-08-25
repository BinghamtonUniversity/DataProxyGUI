<script setup lang="ts">
import type {
  ColumnDef,
  ColumnFiltersState,
  ExpandedState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'
import {
  FlexRender,
  getCoreRowModel,
  getExpandedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, ChevronDown, Plus } from 'lucide-vue-next'
import { h, ref } from 'vue'
import { valueUpdater } from '@/lib/utils'

import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import APILayout from '@/layouts/api/Layout.vue'
import { type BreadcrumbItem, ApiData, RouteData } from '@/types'
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

interface Props {
    api_id: string;
    api_type: string;
}


const props = defineProps<Props>()


const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/routes`,
    },
]

// New Route Dialog
const verbDropdownOpen = ref(false)
const newRouteDialogOpen = ref(false)
const newRouteForm = ref({
  path: '',
  verb: 'GET',
  view_name: '',
  required_params: '',
  optional_params: ''
})
const newRouteLoading = ref(false)
const newRouteError = ref('')
const isEditMode = ref(false)
const editingRouteIndex = ref<number | null>(null)


const openNewRouteDialog = () => {
  newRouteForm.value = {
    path: '',
    verb: 'GET',
    view_name: '',
    required_params: '',
    optional_params: ''
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

const submitNewRoute = async (e: Event) => {
  e.preventDefault()
  newRouteLoading.value = true
  newRouteError.value = ''
  
  try {
    // TO:DO API call to save the route
    // const response = await fetch(`/api/routes`, { ... })
    closeNewRouteDialog()
    // Refresh routes data here
  } catch (err: any) {
    newRouteError.value = err.message || 'Error saving route'
  } finally {
    newRouteLoading.value = false
  }
}

const openEditRouteDialog = (route: RouteData) => {
  isEditMode.value = true
  newRouteForm.value = {
    path: route.path,
    verb: route.verb,
    view_name: route.view_name,
    required_params: route.required?.map(p => p.name).join(', ') || '',
    optional_params: route.optional?.map(p => p.name).join(', ') || ''
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
    cell: ({ row }) => h('div', { class: 'font-medium text-blue-600' }, row.getValue('view_name')),
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
      class: 'bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm font-mono' 
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
//   {
//     id: 'actions',
//     enableHiding: false,
//     cell: ({ row }) => {
//       return h('div', { class: 'flex items-center gap-2' }, [
//         h(Button, {
//           variant: 'ghost',
//           size: 'sm',
//           onClick: () => row.toggleExpanded(),
//         }, () => [
//           row.getIsExpanded() ? h(EyeOff, { class: 'h-4 w-4' }) : h(Eye, { class: 'h-4 w-4' }),
//           h('span', { class: 'ml-1' }, row.getIsExpanded() ? 'Hide' : 'Details')
//         ]),
//         h(Button, {
//           variant: 'ghost',
//           size: 'sm',
//           onClick: () => openEditRouteDialog(row.original, row.index),
//         }, () => 'Edit')
//       ])
//     },
//   },
]

// Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})


// Create table instance
const createTable = (data: RouteData[]) => {
  return useVueTable({
    data,
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
}
</script>

<template>
    <Head title="Routes" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <APILayout :api_id="props.api_id" :api_type="props.api_type">
            <template #default="{ apiData, loadingApiData, apiError }:
            {
                apiData: ApiData | null, 
                loadingApiData: boolean, 
                apiError: string 
            }">
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
                        <template v-else-if="apiData?.version_urls">
                                <div v-if="(() => {
                                    const table = createTable(apiData.version_urls)
                                    return true
                                })()" class="w-full">        

                                <!-- Table Controls -->
                                <div class="flex items-center py-4">
                                    <Input
                                        class="max-w-sm"
                                        placeholder="Filter by path"
                                        :model-value="(() => {
                                            const table = createTable(apiData.version_urls)
                                            return table.getColumn('path')?.getFilterValue() as string
                                        })()"
                                        @update:model-value="(() => {
                                            const table = createTable(apiData.version_urls)
                                            table.getColumn('path')?.setFilterValue($event)
                                        })"
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
                                                        <Input id="route-view" v-model="newRouteForm.view_name" required placeholder="view_function_name" />
                                                    </div>
                                                    <div>
                                                        <Label for="required-params" class="mb-1">Required Parameters</Label>
                                                        <Input id="required-params" v-model="newRouteForm.required_params" placeholder="param1, param2 (comma separated)" />
                                                    </div>
                                                    <div>
                                                        <Label for="optional-params" class="mb-1">Optional Parameters</Label>
                                                        <Input id="optional-params" v-model="newRouteForm.optional_params" placeholder="param3, param4 (comma separated)" />
                                                    </div>
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
                                               v-for="column in (() => {
                                                    const table = createTable(apiData.version_urls)
                                                    return table.getAllColumns().filter((column) => column.getCanHide())
                                                })()"
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
                                            <TableRow v-for="headerGroup in (() => {
                                                const table = createTable(apiData.version_urls)
                                                return table.getHeaderGroups()
                                            })()" :key="headerGroup.id">
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
                                            <template v-if="(() => {
                                                const table = createTable(apiData.version_urls)
                                                return table.getRowModel().rows?.length
                                            })()">
                                                
                                                    <TableRow v-for="row in (() => {
                                                        const table = createTable(apiData.version_urls)
                                                        return table.getRowModel().rows
                                                            })()" 
                                                            :key="row.id" 
                                                            :data-state="row.getIsSelected() && 'selected'"
                                                            class="cursor-pointer hover:bg-muted/50"
                                                            @click="openEditRouteDialog(row.original)">
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
                                        {{ (() => {
                                            const table = createTable(apiData.version_urls)
                                            return table.getFilteredSelectedRowModel().rows.length
                                        })() }} of
                                        {{ (() => {
                                            const table = createTable(apiData.version_urls)
                                            return table.getFilteredRowModel().rows.length
                                        })() }} row(s) selected.
                                    </div>
                                    <div class="space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="!(() => {
                                                const table = createTable(apiData.version_urls)
                                                return table.getCanPreviousPage()
                                            })()"
                                            @click="(() => {
                                                const table = createTable(apiData.version_urls)
                                                table.previousPage()
                                            })"
                                        >
                                            Previous
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="!(() => {
                                                const table = createTable(apiData.version_urls)
                                                return table.getCanNextPage()
                                            })()"
                                            @click="(() => {
                                                const table = createTable(apiData.version_urls)
                                                table.nextPage()
                                            })"
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
            </template>
        </APILayout>
    </AppLayout>
</template>