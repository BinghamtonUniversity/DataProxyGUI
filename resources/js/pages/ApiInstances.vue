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
import { h, ref, onMounted, computed } from 'vue'
import { valueUpdater } from '@/lib/utils'

import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiInstance} from '@/types'
import { Head } from '@inertiajs/vue3'
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
import TableActions from '../components/TableActions.vue'
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'



const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'API Instances',
    href: '/api_instances',
  },
]

const api_instances = ref<ApiInstance[]>([])
const loading = ref(true)
const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL

//new API Instance
const newApiInstanceDialogOpen = ref(false)
const newApiInstanceForm = ref({
  id: '',
  environment_id: '',
  api_id: '',
  api_version_id: '',
  name: '',
  route: '',
  route_user_map: [
    {
      api_user: '',
      verb: '',
      route: ''
    }
  ],
  resources: [
    {
      name: '',
      resource: ''
    }
  ]
})
const newApiInstanceLoading = ref(false)
const newApiInstanceError = ref('')

// Edit API Instance
const isEditMode = ref(false)
const editingApiInstanceId = ref<number|null>(null)

const openNewApiInstanceDialog = () => {
  newApiInstanceForm.value = {
    id: '',
    environment_id: '',
    api_id: '',
    api_version_id: '',
    name: '',
    route: '',
    route_user_map: [
      {
        api_user: '',
        verb: '',
        route: ''
      }
    ],
    resources: [
      {
        name: '',
        resource: ''
      }
    ]
  }
  newApiInstanceError.value = ''
  newApiInstanceDialogOpen.value = true
}

const closeNewApiInstanceDialog = () => {
  newApiInstanceDialogOpen.value = false
  newApiInstanceError.value = ''
  newApiInstanceForm.value = {
    id: '',
    environment_id: '',
    api_id: '',
    api_version_id: '',
    name: '',
    route: '',
    route_user_map: [
      {
        api_user: '',
        verb: '',
        route: ''
      }
    ],
    resources: [
      {
        name: '',
        resource: ''
      }
    ]
  }
  isEditMode.value = false
  editingApiInstanceId.value = null
}

const submitNewApiInstance = async (e: Event) => {
  e.preventDefault()
  newApiInstanceLoading.value = true
  newApiInstanceError.value = ''
  try {
    let url = `${djangoBaseUrl}/api/api_instances`
    let method = 'POST'
    if (isEditMode.value && editingApiInstanceId.value) {
      url = `${djangoBaseUrl}/api/api_instances/${editingApiInstanceId.value}`
      method = 'PUT'
    }
    const body = isEditMode.value && editingApiInstanceId.value
      ? { ...newApiInstanceForm.value, id: editingApiInstanceId.value }
      : { ...newApiInstanceForm.value }

    console.log('Submitting API Instance:', { url, method, body })
    // const response = await fetch(url, {
    //   method,
    //   headers: { 'Content-Type': 'application/json' },
    //   body: JSON.stringify(body),
    // })
    // if (!response.ok) throw new Error('Failed to save API Instance')
    closeNewApiInstanceDialog()
    await fetchApiInstances() // do i need this?
    // refresh API Instance list here
  } catch (err: any) {
    newApiInstanceError.value = err.message || 'Error saving API Instance'
  } finally {
    newApiInstanceLoading.value = false
    isEditMode.value = false
    editingApiInstanceId.value = null
  }
}

const openEditApiInstanceDialog = (apiInstance: ApiInstance) => {
  isEditMode.value = true
  editingApiInstanceId.value = apiInstance.id
  newApiInstanceForm.value = {
    id: apiInstance.id?.toString() || '',
    environment_id: apiInstance.environment_id?.toString() || '',
    api_id: apiInstance.api_id?.toString() || '',
    api_version_id: apiInstance.api_version_id?.toString() || '',
    name: apiInstance.name || '',
    route: apiInstance.route || '',
    route_user_map: apiInstance.route_user_map?.map(item => ({
      api_user: item.api_user?.toString() || '',
      verb: item.verb || '',
      route: item.route || ''
    })) || [],
    resources: apiInstance.resources?.map(item => ({
      name: item.name || '',
      resource: item.resource?.toString() || ''
    })) || [
      {
        name: '',
        resource: ''
      }
    ]
  }
  newApiInstanceDialogOpen.value = true
}

// Helper functions for managing array fields
const addRouteUserMap = () => {
  newApiInstanceForm.value.route_user_map.push({
    api_user: '',
    verb: '',
    route: ''
  })
}

const removeRouteUserMap = (index: number) => {
  if (newApiInstanceForm.value.route_user_map.length > 0) {
    newApiInstanceForm.value.route_user_map.splice(index, 1)
  }
}

const addResource = () => {
  newApiInstanceForm.value.resources.push({
    name: '',
    resource: ''
  })
}

const removeResource = (index: number) => {
  if (newApiInstanceForm.value.resources.length > 1) {
    newApiInstanceForm.value.resources.splice(index, 1)
  }
}

// Define table columns
const columns: ColumnDef<ApiInstance>[] = [
  {
    id: 'select',
    header: ({ table }) => h(Checkbox, {
      'modelValue': table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
      'onUpdate:modelValue': value => table.toggleAllPageRowsSelected(!!value),
      'ariaLabel': 'Select all',
    }),
    cell: ({ row }) => h(Checkbox, {
      'modelValue': row.getIsSelected(),
      'onUpdate:modelValue': value => row.toggleSelected(!!value),
      'ariaLabel': 'Select row',
    }),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: 'id',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['ID', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('id')),
  },
  {
    accessorKey: 'name',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Name', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('name')),
  },
  {
    accessorKey: 'route',
    header: 'Slug',
    cell: ({ row }) => {
      const type = row.getValue('route') as string
      return h('div', { 
        class: 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
      }, type)
    },
  },
  {
    accessorKey: 'environment_id',
    header: 'Environment',
    cell: ({ row }) => {
      const env_id = row.getValue('environment_id') as number
      return h('div', { class: 'truncate max-w-32' }, env_id || 'No environment' )
    },
  },
  {
    accessorKey: 'api_id',
    header: 'API ID',
    cell: ({ row }) => {
      const api_id = row.getValue('environment_id') as number
      return h('div', { class: 'truncate max-w-32' }, api_id || 'No API ID' )
    },
  },
  {
    accessorKey: 'api_version_id',
    header: 'API Version ID',
    cell: ({ row }) => {
      const api_version_id = row.getValue('api_version_id') as number
      return h('div', { class: 'truncate max-w-32' }, api_version_id || 'No Version ID' )
    },
  },
  {
    accessorKey: 'created_at',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Created At', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => {
      const date = new Date(row.getValue('created_at'))
      return h('div', { class: 'text-sm' }, date.toLocaleDateString())
    },
  },
  {
    id: 'resources',
    header: 'Resources',
    cell: ({ row }) => {
      const resources = row.original.resources || []
      
      if (resources.length === 0) {
        return h('div', { class: 'text-gray-500 text-sm' }, 'No resources')
      }
      
      return h('div', { class: 'flex flex-wrap gap-1' }, [
        ...resources.map(res => 
          h('span', { 
            key: res.name,
            class: 'inline-flex items-center rounded px-2 py-1 text-xs bg-red-50 text-red-700 dark:bg-red-900 dark:text-red-300 font-medium'
          }, res.name)
        ),
      ])
    },
  },

  {
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
        const instance = row.original
        return h(TableActions<ApiInstance>, {
            item: instance,
            viewDetailsHref: `/api-instances/${instance.id}/details`,
            editLabel: 'Edit Instance',
            deleteLabel: 'Delete Instance',
            // onEdit: () => openEditInstanceDialog(instance),
            // onDelete: () => handleDeleteInstance(instance),
        })
        }
    }
]

// // Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

const table = computed(() => {
  if (!api_instances.value || api_instances.value.length === 0) {
    return null
  }

  return useVueTable({
    data: api_instances.value,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
    // onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
    state: {
      get sorting() { return sorting.value },
      get columnFilters() { return columnFilters.value },
      get columnVisibility() { return columnVisibility.value },
      get rowSelection() { return rowSelection.value },
      // get expanded() { return expanded.value },
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

const fetchApiInstances = async () => {
  loading.value = true
  try {
    const response = await fetch(`${djangoBaseUrl}/api/api_instances`)
    api_instances.value = await response.json()
  } catch (e) {
    api_instances.value = []
    console.error('Error fetching APIs:', e)
  } finally {
    loading.value = false
  }
}

// Fetch data on mount
onMounted(fetchApiInstances)
</script>

<template>
  <Head title="APIs" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
        
        <!-- Loading State -->
        <template v-if="loading">
          <div class="flex items-center justify-center h-32">
            <div class="text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
              <p class="mt-2">Loading APIs...</p>
            </div>
          </div>
        </template>
        
        <!-- Data Table -->
        <template v-else-if="api_instances?.length && table">
          <div class="w-full">
            <!-- Table Controls -->
            <div class="flex items-center py-4">
                <Input
                class="max-w-sm"
                placeholder="Filter by name..."
                v-model="nameFilterValue"
              />
                
             

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
                    <template v-for="row in tableRows" :key="row.id">
                      <TableRow :data-state="row.getIsSelected() && 'selected'">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                          <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </TableCell>
                      </TableRow>
                      <TableRow v-if="row.getIsExpanded()" class="bg-muted/50">
                        <TableCell :colspan="row.getAllCells().length" class="p-4">
                          <div class="space-y-2">
                            <h4 class="font-semibold">API Instance Details</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                              <div><strong>ID:</strong> {{ row.original.id }}</div>
                              <div><strong>Name:</strong> {{ row.original.name }}</div>
                              <div><strong>Slug:</strong> {{ row.original.route }}</div>
                              <div><strong>Environment:</strong> {{ row.original.environment_id }}</div>
                              <div><strong>API:</strong> {{ row.original.api_id }}</div>
                              <div><strong>API Version:</strong> {{ row.original.api_version_id }}</div>
                              <div><strong>Created At:</strong> {{ new Date(row.original.created_at).toLocaleString() }}</div>
                            </div>
                          </div>
                        </TableCell>
                      </TableRow>
                    </template>
                  </template>
                  <TableRow v-else>
                    <TableCell :colspan="columns.length" class="h-24 text-center">
                      No APIs found.
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
              <p>No APIs available.</p>
            </div>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
</template>