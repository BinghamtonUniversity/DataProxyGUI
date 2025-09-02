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
import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import { h, ref, computed } from 'vue'
import { valueUpdater } from '@/lib/utils'

import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import APILayout from '@/layouts/api/Layout.vue'
import { type BreadcrumbItem, type ApiData, type ResourceData } from '@/types'
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
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose,
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
        href: `/apis/${props.api_type}/${props.api_id}/resources`,
    },
]

const isEditDialogOpen = ref(false)
const selectedResource = ref<ResourceData | null>(null)

const openEditDialog = (resource: ResourceData) => {
  selectedResource.value = JSON.parse(JSON.stringify(resource))
  isEditDialogOpen.value = true
}

const handleSaveChanges = () => {
  if (selectedResource.value) {
    console.log('Saving changes for resource:', selectedResource.value)
    // TODO: make an API call to persist the changes.
    // Inertia.put(`/apis/resources/${selectedResource.value.id}`, selectedResource.value)  
  }
  isEditDialogOpen.value = false
}


// --- Table Definition ---

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
    cell: ({ row }) => h('div', { class: 'font-medium text-blue-600' }, row.getValue('name')),
  },
  {
    accessorKey: 'type',
    header: 'Type',
    cell: ({ row }) => {
      const type = row.getValue('type') as string
      return h('div', { 
        class: 'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900 dark:text-green-300' 
      }, type)
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
    cell: ({ row }) => h('div', { class: 'font-medium' }, row.getValue('model_name')),
  }
]

// Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

// Create table instance that will be computed based on apiData
const createTable = (data: ResourceData[]) => {
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
    <Head title="Resources" />
    
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
                        <template v-else-if="apiData?.resources && Array.isArray(apiData.resources)">
                            <div v-if="(() => {
                                const table = createTable(apiData.resources as ResourceData[])
                                return true
                            })()" class="w-full">

                                <!-- Table Controls -->
                                <div class="flex items-center py-4">
                                    <Input
                                        class="max-w-sm"
                                        placeholder="Filter by name..."
                                        :model-value="(() => {
                                            const table = createTable(apiData.resources as ResourceData[])
                                            return table.getColumn('name')?.getFilterValue() as string
                                        })()"
                                        @update:model-value="(() => {
                                            const table = createTable(apiData.resources as ResourceData[])
                                            table.getColumn('name')?.setFilterValue($event)
                                        })"
                                    />
                                    
                                    <Button class="ml-4 text-green-600" variant="outline">
                                        New Resource
                                    </Button>

                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="outline" class="ml-auto">
                                                Columns <ChevronDown class="ml-2 h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuCheckboxItem
                                                v-for="column in (() => {
                                                    const table = createTable(apiData.resources as ResourceData[])
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

                                <!-- Data Table -->
                                <div class="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow v-for="headerGroup in (() => {
                                                const table = createTable(apiData.resources as ResourceData[])
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
                                                const table = createTable(apiData.resources as ResourceData[])
                                                return table.getRowModel().rows?.length
                                            })()">
                                                <TableRow 
                                                    v-for="row in (() => {
                                                        const table = createTable(apiData.resources as ResourceData[])
                                                        return table.getRowModel().rows
                                                    })()" 
                                                    :key="row.id" 
                                                    :data-state="row.getIsSelected() && 'selected'"
                                                    class="cursor-pointer hover:bg-muted/50"
                                                    @click="openEditDialog(row.original)"
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
                                        {{ (() => {
                                            const table = createTable(apiData.resources as ResourceData[])
                                            return table.getFilteredSelectedRowModel().rows.length
                                        })() }} of
                                        {{ (() => {
                                            const table = createTable(apiData.resources as ResourceData[])
                                            return table.getFilteredRowModel().rows.length
                                        })() }} row(s) selected.
                                    </div>
                                    <div class="space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="!(() => {
                                                const table = createTable(apiData.resources as ResourceData[])
                                                return table.getCanPreviousPage()
                                            })()"
                                            @click="(() => {
                                                const table = createTable(apiData.resources as ResourceData[])
                                                table.previousPage()
                                            })"
                                        >
                                            Previous
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="!(() => {
                                                const table = createTable(apiData.resources as ResourceData[])
                                                return table.getCanNextPage()
                                            })()"
                                            @click="(() => {
                                                const table = createTable(apiData.resources as ResourceData[])
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
                                    <p>No resources available for this API version.</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Edit Resource Dialog -->
                <Dialog v-model:open="isEditDialogOpen">
                    <DialogContent class="sm:max-w-xl">
                        <form @submit.prevent="handleSaveChanges">
                            <DialogHeader>
                                <DialogTitle>Edit Resource: {{ selectedResource?.name }}</DialogTitle>
                            </DialogHeader>
                                <div v-if="selectedResource" class="grid gap-4 py-4 max-h-[70vh] overflow-y-auto pr-6">
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="resource-name" class="text-right">Name</Label>
                                        <Input id="resource-name" v-model="selectedResource.name" class="col-span-3" />
                                    </div>
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="resource-type" class="text-right">Type</Label>
                                        <Input id="resource-type" v-model="selectedResource.type" class="col-span-3" />
                                    </div>
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="resource-modelName" class="text-right">Model Name</Label>
                                        <Input id="resource-modelName" v-model="selectedResource.model_name" class="col-span-3" />
                                    </div>
                                </div>

                                <DialogFooter class="pt-6">
                                    <DialogClose as-child>
                                        <Button variant="secondary" type="button">Cancel</Button>
                                    </DialogClose>
                                    <Button type="submit">Save Changes</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                </Dialog>
            </template>
        </APILayout>
    </AppLayout>
</template>
    