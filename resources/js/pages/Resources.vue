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
import { type BreadcrumbItem, Resource, Environment } from '@/types'
import { Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuCheckboxItem,
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
import TableActions from '../components/TableActions.vue'
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { getCsrfToken } from '@/lib/utils'

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Resources',
    href: '/resources',
  },
]

const resources = ref<Resource[]>([])
const environments = ref<Environment[]>([])
const loading = ref(true)

// Resource type options
const resourceTypeOptions = [
  { value: 'mysql', label: 'MySQL' },
  { value: 'oracle', label: 'Oracle' },
  { value: 'password', label: 'Password' },
  { value: 'value', label: 'Value' }
]

//new API
const newResourceDialogOpen = ref(false)
const newResourceForm = ref({
  name: '',
  type: '',
  resource_type: '',
  config: {            
    pass: '',
    tns: '',
    user: ''
  }
})
const newResourceLoading = ref(false)
const newResourceError = ref('')

//edit API
const isEditMode = ref(false)
const editingResourceId = ref<number|null>(null)

// Computed property to get environment types from environments array
const environmentTypes = computed(() => {
  const types = environments.value.map(env => ({
    value: env.type,
    label: env.type
  }))
  // Remove duplicates
  const uniqueTypes = types.filter((type, index, self) => 
    index === self.findIndex(t => t.value === type.value)
  )
  return uniqueTypes
})

const openNewResourceDialog = () => {
  newResourceForm.value = { name: '', type: '', resource_type: '', config: { user: '', pass: '', tns: '' } }
  newResourceError.value = ''
  newResourceDialogOpen.value = true
}

const closeNewResourceDialog = () => {
  newResourceDialogOpen.value = false
  newResourceError.value = ''
  newResourceForm.value = { name: '', type: '', resource_type: '', config: { user: '', pass: '', tns: '' } }
  isEditMode.value = false
  editingResourceId.value = null
}

const submitNewResource = async (e: Event) => {
  e.preventDefault()
  newResourceLoading.value = true
  newResourceError.value = ''
  try {
    let url = `/ajax/resources`
    let request_method = 'POST'
    console.log(request_method)
    
    if (isEditMode.value && editingResourceId.value) {
      url = `/ajax/resources/${editingResourceId.value}`
      request_method = 'PUT'
    }
    const body = isEditMode.value && editingResourceId.value
      ? { ...newResourceForm.value, id: editingResourceId.value }
      : {... newResourceForm.value}
    console.log('Submitting Resource:', body)
    const response = await fetch(url, {
      method: request_method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken() || '',
      },
      body: JSON.stringify(body),
    })

    // if (!response.ok) throw new Error('Failed to save Resource')
    closeNewResourceDialog()
    await fetchResources()
    // refresh  API list here
  } catch (err: any) {
    newResourceError.value = err.message || 'Error saving Resource'
  } finally {
    newResourceLoading.value = false
    isEditMode.value = false
    editingResourceId.value = null
  }
}

const openEditResourceDialog = (resource: Resource) => {
  isEditMode.value = true
  editingResourceId.value = resource.id
  newResourceForm.value = {
    name: resource.name,
    type: resource.type,
    resource_type: resource.resource_type,
    config: {
      user: resource.config?.user || '',
      pass: resource.config?.pass || '',
      tns: resource.config?.tns || ''
    }
  }
  newResourceDialogOpen.value = true
}


// Define table columns
const columns: ColumnDef<Resource>[] = [
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
    accessorKey: 'type',
    header: 'Environment Type',
    cell: ({ row }) => {
      const type = row.getValue('type') as string
      return h('div', { 
        class: 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
      }, type)
    },
  },
  {
    accessorKey: 'resource_type',
    header: 'Resource Type',
    cell: ({ row }) => {
      const tags = row.getValue('resource_type') as string
      return h('div', { class: 'truncate max-w-32' }, tags || 'No resource type')
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
//   {
//     accessorKey: 'created_by_id',
//     header: 'Created By',
//     cell: ({ row }) => h('div', { class: 'text-sm' }, `User ${row.getValue('created_by_id')}`),
//   },
]

// // Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})


const table = computed(() => {
  if (!resources.value || resources.value.length === 0) {
    return null
  }

  return useVueTable({
    data: resources.value,
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

const fetchResources = async () => {
  loading.value = true
  try {
    const response = await fetch(`/ajax/resources`)
    resources.value = await response.json()
  } catch (e) {
    resources.value = []
    console.error('Error fetching Resources:', e)
  } finally {
    loading.value = false
  }
}

const fetchAllData = async () => {
  loading.value = true
  try {
    const [
      resourcesResponse,
      environmentsResponse,
   
    ] = await Promise.all([
      fetch(`/ajax/resources`),
      fetch(`/api/environments`),
      
    ])

    if (!resourcesResponse.ok) throw new Error('Failed to fetch resources')
    if (!environmentsResponse.ok) throw new Error('Failed to fetch environments')
    const [
      resourcesData,
      environmentsData
    ] = await Promise.all([
      resourcesResponse.json(),
      environmentsResponse.json(),
    ])

    resources.value = resourcesData
    environments.value = environmentsData
  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    loading.value = false
  }
}

// Fetch data on mount
onMounted(() => fetchAllData())
</script>

<template>
  <Head title="Resources" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
        
        <!-- Loading State -->
        <template v-if="loading">
          <div class="flex items-center justify-center h-32">
            <div class="text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
              <p class="mt-2">Loading Resources...</p>
            </div>
          </div>
        </template>
        
        <!-- Always show New Resource button and dialog -->
        <template v-else>
          <!-- Top Controls - Always visible -->
          <div class="flex items-center py-4 justify-between">
            <div class="flex items-center">
              <Input
                v-if="resources?.length"
                class="max-w-sm"
                placeholder="Filter by name..."
                v-model="nameFilterValue"
              />
            </div>
            
            <div class="flex items-center gap-2">
              <Dialog v-model:open="newResourceDialogOpen">
                <DialogTrigger as-child>
                  <Button class="text-green-600" variant="outline" @click="openNewResourceDialog">
                    <Plus class="mr-2 h-4 w-4" />
                    New Resource
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <form @submit="submitNewResource" class="space-y-6">
                    <DialogHeader>
                      <DialogTitle>{{ isEditMode ? 'Edit Resource' : 'Create New Resource' }}</DialogTitle>
                    </DialogHeader>
                    <div class="grid gap-4">
                      <div>
                        <Label for="api-name" class="mb-1">Name</Label>
                        <Input id="api-name" v-model="newResourceForm.name" required placeholder="Resource Name" />
                      </div>
                      <div>
                        <Label for="environment-type" class="mb-1">Environment Type</Label>
                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="w-full justify-between">
                              {{ newResourceForm.type || 'Select environment type' }}
                              <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent class="w-full">
                            <DropdownMenuItem 
                              v-for="envType in environmentTypes" 
                              :key="envType.value"
                              @click="newResourceForm.type = envType.value"
                            >
                              {{ envType.label }}
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </div>
                      <div>
                        <Label for="resource-type" class="mb-1">Resource Type</Label>
                        <DropdownMenu>
                          <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="w-full justify-between">
                              {{ resourceTypeOptions.find(r => r.value === newResourceForm.resource_type)?.label || 'Select resource type' }}
                              <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent class="w-full">
                            <DropdownMenuItem 
                              v-for="resourceType in resourceTypeOptions" 
                              :key="resourceType.value"
                              @click="newResourceForm.resource_type = resourceType.value"
                            >
                              {{ resourceType.label }}
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </div>
                      <!-- CONFIGURATION SECTION -->
                    <div class="border-t pt-4 mt-4 space-y-4">
                      <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Configuration</h3>
                      <div>
                        <Label>User</Label>
                        <Input v-model="newResourceForm.config.user" placeholder="Database Username" />
                      </div>
                      <div>
                        <Label>Password</Label>
                        <Input type="password" v-model="newResourceForm.config.pass" placeholder="Database Password" />
                      </div>
                      <div>
                        <Label>TNS</Label>
                        <Input v-model="newResourceForm.config.tns" rows="4" class="resize-none" placeholder="TNS connection string" />
                      </div>
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

              <DropdownMenu v-if="resources?.length && table">
                <DropdownMenuTrigger as-child>
                  <Button variant="outline">
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
          </div>

          <!-- Data Table or Empty State -->
          <template v-if="resources?.length && table">
            <div class="w-full">
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
                              <h4 class="font-semibold">Resource Details</h4>
                              <div class="grid grid-cols-2 gap-4 text-sm">
                                <div><strong>ID:</strong> {{ row.original.id }}</div>
                                <div><strong>Name:</strong> {{ row.original.name }}</div>
                                <div><strong>Type:</strong> {{ row.original.type || "No Type" }}</div>
                                <div><strong>Resource Type:</strong> {{ row.original.resource_type  || "No Resource Type"}}</div>
                                <div><strong>User:</strong> {{ row.original.config?.user || "No User" }}</div>
                                <div><strong>TNS:</strong> {{ row.original.config?.tns ? "Configured" : "Not Configured" }}</div>
                                <div><strong>Created:</strong> {{ new Date(row.original.created_at).toLocaleString() }}</div>
                                <div><strong>Updated:</strong> {{ new Date(row.original.updated_at).toLocaleString() }}</div>
                              </div>
                            </div>
                          </TableCell>
                        </TableRow>
                      </template>
                    </template>
                    <TableRow v-else>
                      <TableCell :colspan="columns.length" class="h-24 text-center">
                        No Resources found.
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
          
          <!-- Empty State -->
          <template v-else>
            <div class="flex flex-col items-center justify-center h-64 text-center">
              <div class="text-muted-foreground mb-4">
                <h3 class="text-lg font-semibold mb-2">No Resources Yet</h3>
                <p>Get started by creating your first resource.</p>
              </div>
            </div>
          </template>
        </template>
        
      </div>
    </div>
  </AppLayout>
</template>