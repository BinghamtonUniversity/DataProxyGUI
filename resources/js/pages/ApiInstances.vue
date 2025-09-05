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
import { ArrowUpDown, ChevronDown, Plus, Check } from 'lucide-vue-next'
import { h, ref, onMounted, onUnmounted, computed, reactive } from 'vue'
import { valueUpdater } from '@/lib/utils'

import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiInstance, Environment, ApiUser, Api, Resource} from '@/types'
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
const environments = ref<Environment[]>([])
const apis = ref<Api[]>([])
const api_users = ref<ApiUser[]>([]) // TO:DO - Use after view details tabs
// const resources = ref<Resource[]>([])

const loading = ref(true)
const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL

//new API Instance
const newApiInstanceDialogOpen = ref(false)

//TO-DO: Should send route_user_map and resources as JSON strings here or in Details page??
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

// Dropdown state
const dropdownOpen = reactive({
  environment: false,
  api: false
})

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
    await fetchApiInstances()
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

// MOVE THESE to Details Page ?? Helper functions for managing array fields
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

// Dropdown functionality
const toggleDropdown = (type: keyof typeof dropdownOpen) => {
  // Close all dropdowns first
  Object.keys(dropdownOpen).forEach(key => {
    dropdownOpen[key as keyof typeof dropdownOpen] = false
  })
  // Open the requested dropdown
  dropdownOpen[type] = !dropdownOpen[type]
}

const selectEnvironment = (env: Environment) => {
  newApiInstanceForm.value.environment_id = env.id.toString()
  dropdownOpen.environment = false
}

const selectApi = (api: Api) => {
  newApiInstanceForm.value.api_id = api.id.toString()
  dropdownOpen.api = false
}

const getSelectedEnvironmentName = () => {
  if (!newApiInstanceForm.value.environment_id || !environments.value) return ''
  const selected = environments.value.find(env => env.id.toString() === newApiInstanceForm.value.environment_id)
  return selected ? `${selected.name} - ${selected.type}` : ''
}

const getSelectedApiName = () => {
  if (!newApiInstanceForm.value.api_id || !apis.value) return ''
  const selected = apis.value.find(api => api.id.toString() === newApiInstanceForm.value.api_id)
  return selected ? selected.name : ''
}

// Close dropdowns when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  const dropdownElements = document.querySelectorAll('.relative')
  let clickedInside = false
  
  dropdownElements.forEach(element => {
    if (element.contains(target)) {
      clickedInside = true
    }
  })
  
  if (!clickedInside) {
    Object.keys(dropdownOpen).forEach(key => {
      dropdownOpen[key as keyof typeof dropdownOpen] = false
    })
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
      const api_id = row.getValue('api_id') as number
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
            onEdit: () => openEditApiInstanceDialog(instance),
            // onDelete: () => handleDeleteInstance(instance),
        })
        }
    }
]

// Table state
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

const fetchAllData = async () => {
  loading.value = true
  try {
    const [
      apiInstancesResponse,
      environmentsResponse,
      apisResponse,
      apiUserResponse
    ] = await Promise.all([
      fetch(`${djangoBaseUrl}/api/api_instances`),
      fetch(`${djangoBaseUrl}/api/environments`),
      fetch(`${djangoBaseUrl}/api/apis`),
      fetch(`${djangoBaseUrl}/api/api_users`),
    ])

    if (!apiInstancesResponse.ok) throw new Error('Failed to fetch API instances')
    if (!environmentsResponse.ok) throw new Error('Failed to fetch environments')
    if (!apisResponse.ok) throw new Error('Failed to fetch APIs')
    if (!apiUserResponse.ok) throw new Error('Failed to fetch API users')

    const [
      apiInstancesData,
      environmentsData,
      apisData,
      apiUserData
    ] = await Promise.all([
      apiInstancesResponse.json(),
      environmentsResponse.json(),
      apisResponse.json(),
      apiUserResponse.json(),
    ])

    api_instances.value = apiInstancesData
    environments.value = environmentsData
    apis.value = apisData
    api_users.value = apiUserData

  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle hooks
onMounted(() => { 
  fetchAllData()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
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
                <Dialog v-model:open="newApiInstanceDialogOpen">
                    <DialogTrigger as-child>
                    <Button class="ml-4 text-green-600" variant="outline" @click="openNewApiInstanceDialog">
                        <Plus class="mr-2 h-4 w-4" />
                        New API Instance
                    </Button>
                    </DialogTrigger>
                    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                    <form @submit="submitNewApiInstance" class="space-y-6">
                        <DialogHeader>
                        <DialogTitle>{{ isEditMode ? 'Edit API Instance' : 'Create New API Instance' }}</DialogTitle>
                        </DialogHeader>
                        <div class="grid gap-6">
                        <!-- Environment Selection -->
                        <div class="relative">
                            <Label for="environment-id" class="mb-1">Environment</Label>
                            <div class="relative">
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 flex items-center justify-between"
                                @click="toggleDropdown('environment')"
                            >
                                <span class="block truncate">
                                {{ getSelectedEnvironmentName() || 'Select Environment' }}
                                </span>
                                <ChevronDown class="h-4 w-4 text-gray-400" :class="{ 'rotate-180': dropdownOpen.environment }" />
                            </button>
                            
                            <div
                                v-show="dropdownOpen.environment"
                                class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                            >
                                <div
                                v-for="env in environments"
                                :key="env.id"
                                class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center"
                                @click="selectEnvironment(env)"
                                >
                                <span class="block truncate">{{ env.name }} - {{ env.type }}</span>
                                <Check
                                    v-if="newApiInstanceForm.environment_id === env.id.toString()"
                                    class="h-4 w-4 text-blue-600 ml-auto"
                                />
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- API Selection -->
                        <div class="relative">
                            <Label for="api-id" class="mb-1">API</Label>
                            <div class="relative">
                            <button
                                type="button"
                                class="w-full px-3 py-2 text-left bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 flex items-center justify-between"
                                @click="toggleDropdown('api')"
                            >
                                <span class="block truncate">
                                {{ getSelectedApiName() || 'Select API' }}
                                </span>
                                <ChevronDown class="h-4 w-4 text-gray-400" :class="{ 'rotate-180': dropdownOpen.api }" />
                            </button>
                            
                            <div
                                v-show="dropdownOpen.api"
                                class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto"
                            >
                                <div
                                v-for="api in apis"
                                :key="api.id"
                                class="px-3 py-2 cursor-pointer hover:bg-gray-100 flex items-center"
                                @click="selectApi(api)"
                                >
                                <span class="block truncate">{{ api.name }}</span>
                                <Check
                                    v-if="newApiInstanceForm.api_id === api.id.toString()"
                                    class="h-4 w-4 text-blue-600 ml-auto"
                                />
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- API Version -->
                        <div>
                            <Label for="api-version" class="mb-1">API Version</Label>
                            <Input id="api-version" v-model="newApiInstanceForm.api_version_id" placeholder="API Version" />
                        </div>

                        <!-- Name -->
                        <div>
                            <Label for="instance-name" class="mb-1">Name</Label>
                            <Input id="instance-name" v-model="newApiInstanceForm.name" required placeholder="API Instance Name" />
                        </div>

                        <!-- Route/Slug -->
                        <div>
                            <Label for="instance-route" class="mb-1">Slug</Label>
                            <Input id="instance-route" v-model="newApiInstanceForm.route" required placeholder="Route/Slug" />
                        </div>

                        <div v-if="newApiInstanceError" class="text-red-600 text-sm">{{ newApiInstanceError }}</div>
                        </div>
                        <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="secondary" type="button" @click="closeNewApiInstanceDialog">Cancel</Button>
                        </DialogClose>
                        <Button type="submit" variant="default" :disabled="newApiInstanceLoading">
                            <span v-if="newApiInstanceLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
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
                    <template v-for="row in tableRows" :key="row.id" >
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

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>