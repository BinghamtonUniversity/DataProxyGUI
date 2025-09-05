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
import { h, ref } from 'vue'
import { valueUpdater } from '@/lib/utils'

import { Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import APILayout from '@/layouts/api/Layout.vue'
import { type BreadcrumbItem, type ApiData, type ModelData } from '@/types'
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
import Editor from '@/pages/Editor.vue'

interface Props {
    api_id: string;
    api_type: string;
}

const props = defineProps<Props>()

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/models`,
    },
]

// --- Dialog State and Handlers ---
const isEditDialogOpen = ref(false)
const selectedModel = ref<ModelData | null>(null)


const openEditDialog = (model: ModelData) => {
  // Create a deep copy to prevent modifying the original data directly
  selectedModel.value = JSON.parse(JSON.stringify(model))
  isEditDialogOpen.value = true
}

const addMetaProperty = () => {
  if (selectedModel.value) {
    if (!selectedModel.value.class_meta) {
      selectedModel.value.class_meta = [];
    }
    selectedModel.value.class_meta.push({ name: '', value: '' });
  }
};

const removeMetaProperty = (index: number) => {
  if (selectedModel.value && selectedModel.value.class_meta) {
    selectedModel.value.class_meta.splice(index, 1);
  }
};

const handleSaveChanges = async () => {
  if (selectedModel.value) {
    try {
      // await api call
      console.log('Saving changes for model:', selectedModel.value)
    } catch (e) {
      // handle error
    }
  }
  isEditDialogOpen.value = false
}


// --- Table Definition ---

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
]

// Table state
const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})

// Create table instance that will be computed based on apiData
const createTable = (data: ModelData[]) => {
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
    <Head title="Models" />
    
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
                        <template v-else-if="apiData?.version_models">
                            <div v-if="(() => {
                                const table = createTable(apiData.version_models)
                                return true
                            })()" class="w-full">

                                <!-- Table Controls -->
                                <div class="flex items-center py-4">
                                    <Input
                                        class="max-w-sm"
                                        placeholder="Filter by model name..."
                                        :model-value="(() => {
                                            const table = createTable(apiData.version_models)
                                            return table.getColumn('name')?.getFilterValue() as string
                                        })()"
                                        @update:model-value="(() => {
                                            const table = createTable(apiData.version_models)
                                            table.getColumn('name')?.setFilterValue($event)
                                        })"
                                    />
                                    
                                    <Button class="ml-4 text-green-600" variant="outline">
                                        New Model
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
                                                    const table = createTable(apiData.version_models)
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
                                                const table = createTable(apiData.version_models)
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
                                                const table = createTable(apiData.version_models)
                                                return table.getRowModel().rows?.length
                                            })()">
                                                <TableRow 
                                                    v-for="row in (() => {
                                                        const table = createTable(apiData.version_models)
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
                                                    No models found.
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>

                                <!-- Pagination -->
                                <div class="flex items-center justify-end space-x-2 py-4">
                                    <div class="flex-1 text-sm text-muted-foreground">
                                        {{ (() => {
                                            const table = createTable(apiData.version_models)
                                            return table.getFilteredSelectedRowModel().rows.length
                                        })() }} of
                                        {{ (() => {
                                            const table = createTable(apiData.version_models)
                                            return table.getFilteredRowModel().rows.length
                                        })() }} row(s) selected.
                                    </div>
                                    <div class="space-x-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="!(() => {
                                                const table = createTable(apiData.version_models)
                                                return table.getCanPreviousPage()
                                            })()"
                                            @click="(() => {
                                                const table = createTable(apiData.version_models)
                                                table.previousPage()
                                            })"
                                        >
                                            Previous
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            :disabled="!(() => {
                                                const table = createTable(apiData.version_models)
                                                return table.getCanNextPage()
                                            })()"
                                            @click="(() => {
                                                const table = createTable(apiData.version_models)
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
                                    <p>No models available for this API version.</p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Edit Model Dialog -->
                <Dialog v-model:open="isEditDialogOpen">
                    <DialogContent class="sm:max-w-2xl">
                        <form @submit.prevent="handleSaveChanges">
                            <DialogHeader>
                                <DialogTitle>Edit Model: {{ selectedModel?.name }}</DialogTitle>
                            </DialogHeader>
                                <div v-if="selectedModel" class="grid gap-4 py-4 max-h-[70vh] overflow-y-auto pr-6">
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="model-name" class="text-right">Name</Label>
                                        <Input id="model-name" v-model="selectedModel.name" class="col-span-3" />
                                    </div>
                                    <div class="grid grid-cols-4 items-center gap-4">
                                        <Label for="model-inheritance" class="text-right">Inheritance</Label>
                                        <Input id="model-inheritance" v-model="selectedModel.inheritance" class="col-span-3" />
                                    </div>
                                   
                                    <!-- Meta Properties Section -->
                                    <div class="flex flex-col gap-2 border-t pt-4 mt-4">
                                        <h3 class="text-lg font-medium">Meta Properties</h3>
                                        <div v-if="selectedModel.class_meta && selectedModel.class_meta.length > 0" class="space-y-3 pr-1">
                                            <div class="grid grid-cols-9 items-center gap-2">
                                                <Label class="col-span-4 text-sm font-semibold">Name</Label>
                                                <Label class="col-span-4 text-sm font-semibold">Value</Label>
                                            </div>
                                            <div v-for="(meta, index) in selectedModel.class_meta" :key="index" class="grid grid-cols-9 items-center gap-2">
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
                                                    @click="removeMetaProperty(index)"
                                                    class="col-span-1"
                                                >
                                                    x
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
                                            @click="addMetaProperty"
                                            class="mt-2 self-start"
                                        >
                                            +
                                        </Button>
                                    </div>
                                     <div class="grid grid-cols-4 items-start gap-4">
                                        <Label for="model-content" class="text-right pt-2">Content</Label>
                                        <Editor id="model-content" v-model="selectedModel.content" class="col-span-6" :code="selectedModel.content" :language="props.api_type === 'python' || props.api_type === 'php' ? props.api_type : undefined" />
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
