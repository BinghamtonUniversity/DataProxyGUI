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
import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import { h, ref, onMounted } from 'vue'
import { valueUpdater } from '@/lib/utils'

import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
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
import ApiTableActions from '../components/ApiTableActions.vue'
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'



interface Api {
  id: number
  name: string
  description: string
  tags: string
  api_type: string
  user_id: number
  created_at: string
  updated_at: string
  created_by_id: number
  updated_by_id: number
  deleted_at: string | null
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'APIs',
    href: '/apis',
  },
]

const apis = ref<Api[]>([])
const loading = ref(true)
const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL

//new API
const newApiDialogOpen = ref(false)
const newApiForm = ref({
  name: '',
  description: '',
  tags: ''
})
const newApiLoading = ref(false)
const newApiError = ref('')

//edit API
const isEditMode = ref(false)
const editingApiId = ref<number|null>(null)

const openNewApiDialog = () => {
  newApiForm.value = { name: '', description: '', tags: '' }
  newApiError.value = ''
  newApiDialogOpen.value = true
}

const closeNewApiDialog = () => {
  newApiDialogOpen.value = false
  newApiError.value = ''
  newApiForm.value = { name: '', description: '', tags: '' }
  isEditMode.value = false
  editingApiId.value = null
}

const submitNewApi = async (e: Event) => {
  e.preventDefault()
  newApiLoading.value = true
  newApiError.value = ''
  try {
    let url = `${djangoBaseUrl}/api/apis`
    let method = 'POST'
    if (isEditMode.value && editingApiId.value) {
      url = `${djangoBaseUrl}/api/apis/${editingApiId.value}`
      method = 'PUT'
    }
    const body = isEditMode.value && editingApiId.value
      ? { ...newApiForm.value, id: editingApiId.value }
      : {... newApiForm.value, api_type: 'python'}
    const response = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    })
    if (!response.ok) throw new Error('Failed to save API')
    closeNewApiDialog()
    await fetchApis()
    // refresh  API list here
  } catch (err: any) {
    newApiError.value = err.message || 'Error saving API'
  } finally {
    newApiLoading.value = false
    isEditMode.value = false
    editingApiId.value = null
  }
}

const openEditApiDialog = (api: Api) => {
  isEditMode.value = true
  editingApiId.value = api.id
  newApiForm.value = {
    name: api.name,
    description: api.description,
    tags: api.tags,
  }
  newApiDialogOpen.value = true
}


// Define table columns
const columns: ColumnDef<Api>[] = [
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
    accessorKey: 'api_type',
    header: 'Type',
    cell: ({ row }) => {
      const type = row.getValue('api_type') as string
      return h('div', { 
        class: 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-300' 
      }, type)
    },
  },
  {
    accessorKey: 'tags',
    header: 'Tags',
    cell: ({ row }) => {
      const tags = row.getValue('tags') as string
      return h('div', { class: 'truncate max-w-32' }, tags || 'No tags')
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
    accessorKey: 'created_by_id',
    header: 'Created By',
    cell: ({ row }) => h('div', { class: 'text-sm' }, `User ${row.getValue('created_by_id')}`),
  },
  {
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
      const api = row.original
      return h(ApiTableActions, {
        api,
        // onExpand: row.toggleExpanded,
        onEdit: () => openEditApiDialog(api),
      })
    },
  },
]

// Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})

// Create table instance
const table = useVueTable({
  get data() { return apis.value },
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
    get expanded() { return expanded.value },
  },
})

const fetchApis = async () => {
  loading.value = true
  try {
    const response = await fetch(`${djangoBaseUrl}/api/apis`)
    apis.value = await response.json()
  } catch (e) {
    apis.value = []
    console.error('Error fetching APIs:', e)
  } finally {
    loading.value = false
  }
}

// Fetch data on mount
onMounted(fetchApis)
</script>

<template>
  <Head title="APIs" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
        <template v-if="loading">
          <div class="flex items-center justify-center h-32">
            <div class="text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
              <p class="mt-2">Loading APIs...</p>
            </div>
          </div>
        </template>
        <template v-else>
          <div class="w-full">
            <!-- Table Controls -->
            <div class="flex items-center py-4">
              <Input
                class="max-w-sm"
                placeholder="Filter by name..."
                :model-value="table.getColumn('name')?.getFilterValue() as string"
                @update:model-value="table.getColumn('name')?.setFilterValue($event)"
              />
              <Dialog v-model:open="newApiDialogOpen">
              <DialogTrigger as-child>
                <Button class="ml-4 text-green-600" variant="outline" @click="openNewApiDialog">
                  New API
                </Button>
              </DialogTrigger>
              <DialogContent>
                <form @submit="submitNewApi" class="space-y-6">
                  <DialogHeader>
                    <DialogTitle>{{ isEditMode ? 'Edit API' : 'Create New API' }}</DialogTitle>

                  </DialogHeader>
                  <div class="grid gap-4">
                    <div>
                      <Label for="api-name" class="mb-1">Name</Label>
                      <Input id="api-name" v-model="newApiForm.name" required placeholder="API Name" />
                    </div>
                    <div>
                      <Label for="api-description" class="mb-1">Description</Label>
                      <Input id="api-description" v-model="newApiForm.description" placeholder="Description" />
                    </div>
                    <div>
                      <Label for="api-tags" class="mb-1">Tags</Label>
                      <Input id="api-tags" v-model="newApiForm.tags" placeholder="Tags (comma separated)" />
                    </div>
                    <div v-if="newApiError" class="text-red-600 text-sm">{{ newApiError }}</div>
                  </div>
                  <DialogFooter class="gap-2">
                    <DialogClose as-child>
                      <Button variant="secondary" type="button" @click="closeNewApiDialog">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" variant="default" :disabled="newApiLoading">
                      <span v-if="newApiLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
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
                    v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
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
                  <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
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
                  <template v-if="table.getRowModel().rows?.length">
                    <template v-for="row in table.getRowModel().rows" :key="row.id">
                      <TableRow :data-state="row.getIsSelected() && 'selected'">
                        <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                          <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </TableCell>
                      </TableRow>
                      <TableRow v-if="row.getIsExpanded()" class="bg-muted/50">
                        <TableCell :colspan="row.getAllCells().length" class="p-4">
                          <div class="space-y-2">
                            <h4 class="font-semibold">API Details</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                              <div><strong>Description:</strong> {{ row.original.description || 'No description' }}</div>
                              <div><strong>User ID:</strong> {{ row.original.user_id }}</div>
                              <div><strong>Created:</strong> {{ new Date(row.original.created_at).toLocaleString() }}</div>
                              <div><strong>Updated:</strong> {{ new Date(row.original.updated_at).toLocaleString() }}</div>
                              <div><strong>Updated By:</strong> User {{ row.original.updated_by_id }}</div>
                              <div><strong>Status:</strong> {{ row.original.deleted_at ? 'Deleted' : 'Active' }}</div>
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
                {{ table.getFilteredSelectedRowModel().rows.length }} of
                {{ table.getFilteredRowModel().rows.length }} row(s) selected.
              </div>
              <div class="space-x-2">
                <Button
                  variant="outline"
                  size="sm"
                  :disabled="!table.getCanPreviousPage()"
                  @click="table.previousPage()"
                >
                  Previous
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  :disabled="!table.getCanNextPage()"
                  @click="table.nextPage()"
                >
                  Next
                </Button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </AppLayout>
</template>