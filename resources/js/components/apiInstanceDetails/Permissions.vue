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
import { ArrowUpDown, ChevronDown, Plus } from 'lucide-vue-next'
import { h, ref, computed } from 'vue'
import { getCsrfToken, valueUpdater } from '@/lib/utils'

import { ApiInstance, ApiUser, Resource, type ApiInstanceRouteUserMap } from '@/types'
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
    instance_id: string
    apiInstanceData: ApiInstance | null,
    apiUsers: ApiUser[] | null,
    resources: Resource[] | null,
    loading: boolean,
    apiInstanceError: string
    updateApiInstanceData: (updatedApiInstanceData: ApiInstance) => void
}

const props = defineProps<Props>()

// New Permission Dialog
const userDropdownOpen = ref(false)
const newPermissionDialogOpen = ref(false)
const newPermissionForm = ref({
    user: '',
    verb: '',
    route: ''
})
const newPermissionLoading = ref(false)
const newPermissionError = ref('')
const isEditMode = ref(false)
const editingPermissionIndex = ref<number | null>(null)

// Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

const openNewPermissionDialog = () => {
  newPermissionForm.value = {
    user: '',
    verb: '',
    route: ''
  }
  newPermissionError.value = ''
  newPermissionDialogOpen.value = true
}

const closeNewPermissionDialog = () => {
  newPermissionDialogOpen.value = false
  newPermissionError.value = ''
  isEditMode.value = false
  editingPermissionIndex.value = null
}

const submitNewPermission = async (e: Event) => {
    e.preventDefault()
    newPermissionLoading.value = true
    newPermissionError.value = ''
    
    if (!props.apiInstanceData) {
        newPermissionError.value = 'API instance data not available'
        newPermissionLoading.value = false
        return
    }
    
    try {
        const newPermission: ApiInstanceRouteUserMap = {
            api_user: newPermissionForm.value.user,
            verb: newPermissionForm.value.verb,
            route: newPermissionForm.value.route
        }
        
        let updatedApiInstanceData
    
        if (isEditMode.value && editingPermissionIndex.value !== null) {
            updatedApiInstanceData = {
                ...props.apiInstanceData,
                route_user_map: props.apiInstanceData.route_user_map?.map((permission, index) => 
                index === editingPermissionIndex.value 
                    ? { ...permission, ...newPermission }
                    : permission
                ) || []
            }
        } else {
            updatedApiInstanceData = {
                ...props.apiInstanceData,
                route_user_map: [...(props.apiInstanceData.route_user_map || []), newPermission]
            }
        }
        console.log(updatedApiInstanceData)

        const response = await fetch(`/ajax/api_instances/${props.instance_id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            body: JSON.stringify(updatedApiInstanceData)
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }

        const responseData = await response.json()
        props.updateApiInstanceData(responseData || updatedApiInstanceData)

        closeNewPermissionDialog()
    } catch (err: any) {
        console.error('Error saving permission:', err)
        newPermissionError.value = err.message || 'Error saving permission'
    } finally {
        newPermissionLoading.value = false
    }
}

const openEditPermissionDialog = (permission: ApiInstanceRouteUserMap, index: number) => {
  isEditMode.value = true
  editingPermissionIndex.value = index
  newPermissionForm.value = {
    user: permission.api_user,
    verb: permission.verb,
    route: permission.route
  }
  newPermissionDialogOpen.value = true
}

// Define table columns
const columns: ColumnDef<ApiInstanceRouteUserMap>[] = [
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
    accessorKey: 'api_user',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['User', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
     cell: ({ row }) => {
      const userId = row.getValue('api_user') as string | number
      const user = props.apiUsers?.find(u => u.id === Number(userId))
      return h('div', { class: 'font-medium text-blue-600' }, user?.app_name || userId)
        }
    
   },
  {
    accessorKey: 'route',
    header: ({ column }) => {
      return h(Button, {
        variant: 'ghost',
        onClick: () => column.toggleSorting(column.getIsSorted() === 'asc'),
      }, () => ['Route', h(ArrowUpDown, { class: 'ml-2 h-4 w-4' })])
    },
    cell: ({ row }) => h('code', { 
      class: 'bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm font-mono' 
    }, row.getValue('route')),
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
]

// Create table instance
const table = computed(() => {
  if (!props.apiInstanceData?.route_user_map) {
    return null
  }

  return useVueTable({
    data: props.apiInstanceData.route_user_map,
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
const userFilterValue = computed({
  get: () => table.value?.getColumn('api_user')?.getFilterValue() as string || '',
  set: (value: string) => table.value?.getColumn('api_user')?.setFilterValue(value)
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
            <template v-if="loading">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
                        <p class="mt-2">Loading permissions...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiInstanceError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading permissions: {{ apiInstanceError }}</p>
                    </div>
                </div>
            </template>

            <!-- Data Table -->
            <template v-else-if="apiInstanceData?.route_user_map && table">
                <div class="w-full">        
                    <!-- Table Controls -->
                    <div class="flex items-center py-4">
                        <Input
                            class="max-w-sm"
                            placeholder="Filter by user"
                            v-model="userFilterValue"
                        />
                        
                        <Dialog v-model:open="newPermissionDialogOpen">
                            <DialogTrigger as-child>
                                <Button class="ml-4 text-green-600" variant="outline" @click="openNewPermissionDialog">
                                    <Plus class="mr-2 h-4 w-4" />
                                    New Permission
                                </Button>
                            </DialogTrigger>
                            <DialogContent class="sm:max-w-md">
                                <form @submit="submitNewPermission" class="space-y-6">
                                    <DialogHeader>
                                        <DialogTitle>{{ isEditMode ? 'Edit Permission' : 'Create New Permission' }}</DialogTitle>
                                    </DialogHeader>
                                    <div class="grid gap-4">
                                        <div>
                                            <Label for="permission-user" class="mb-1">User</Label>
                                            <DropdownMenu v-model:open="userDropdownOpen">
                                                <DropdownMenuTrigger as-child>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    class="w-full justify-between"
                                                >
                                                    {{ newPermissionForm.user ? apiUsers?.find(u => u.id === Number(newPermissionForm.user))?.app_name || 'Select user' : 'Select user' }}

                                                    <ChevronDown class="ml-1 h-4 w-4" />
                                                </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="start" class="w-full">
                                                    <DropdownMenuItem
                                                        v-for="user in apiUsers"
                                                        :key="user.id"
                                                        @click="newPermissionForm.user = String(user.id)"
                                                        :class="['w-full', {'font-semibold text-blue-600': Number(newPermissionForm.user) === user.id }]"
                                                    >
                                                        {{ user.app_name }}
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </div>
                                        <div>
                                            <Label for="permission-verb" class="mb-1">HTTP Method (Verb)</Label>
                                            <Input id="permission-verb" v-model="newPermissionForm.verb" required placeholder="GET, POST, PUT, etc." />
                                        </div>
                                        <div>
                                            <Label for="permission-route" class="mb-1">Route</Label>
                                            <Input id="permission-route" v-model="newPermissionForm.route" required placeholder="/api/endpoint" />
                                        </div>
                                        <div v-if="newPermissionError" class="text-red-600 text-sm">{{ newPermissionError }}</div>
                                    </div>
                                    <DialogFooter class="gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary" type="button" @click="closeNewPermissionDialog">Cancel</Button>
                                        </DialogClose>
                                        <Button type="submit" variant="default" :disabled="newPermissionLoading">
                                            <span v-if="newPermissionLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
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
                                        class="cursor-pointer hover:bg-muted/50"
                                        @click="openEditPermissionDialog(row.original, index)"
                                    >
                                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                                        </TableCell>
                                    </TableRow>                                                    
                                </template>
                          
                                <TableRow v-else>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No permissions found.
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
                        <p>No permissions available for this API instance.</p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>