<script setup lang="ts">

import { ref } from 'vue'
import { type ApiData, type ResourceData, Api } from '@/types'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import DataGrid from '@/components/datagrid/DataGrid.vue'
import AlertModal from '@/components/AlertModal.vue'
import FormViewer from '@/components/formviewer/FormViewer.vue'

interface Props {
    api_id: string
    api_type: string
    apiData: ApiData | null
    api: Api | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
    handleSave?: () => Promise<void>
    highlightQuery?: string
    highlightTarget?: string
}

const props = defineProps<Props>()

const newResourceDialogOpen = ref(false)

const newResourceForm = ref({
  name: '',
  type: '' as string | null,
  model_name: '' as string | null
})
const newResourceLoading = ref(false)
const newResourceError = ref('')
const isEditMode = ref(false)
const editingResourceIndex = ref<number | null>(null)

// Toaster
const { success, error, warning, info } = useToaster();

const formConfig = {
  label: 'New Resource',
  description: 'Create a new resource',
  fields: [
    { name: 'name', label: 'Name', type: 'text', required: true },
    { name: 'type', label: 'Type', type: 'select', required: props.api_type === 'python' ? true : false, show: props.api_type === 'python' ? true : false, options: ['Model', 'Password', 'Other'] },
    { name: 'model_name', label: 'Model Name', type: 'select', required: "show", show: {op: 'and', conditions: [{type: 'matches', name: 'type', value: ['Model']}]}, options: props.apiData?.version_models?.map((model: any) => model.name) || [] },
  ],
  files: false,
  name: "new-resource-form",

}
const resourcesSchema = {
  label: 'Resources',
  description: 'A list of resources with their information.',
  name: "resources-schema",
  fields: [
    {
      name: "name",
      label: "Name",
      type: "text",
      placeholder: "Resource Name",
      value: "",
      help: "Name of the resource",
      info: "Name of the resource",
      width: "12",
      offset: "0",
      required: true,
      showColumn: true
    },
    {
      name: "type",
      label: "Type",
      type: "select",
      placeholder: "Resource Type",
      value: "",
      help: "Type of the resource",
      info: "Type of the resource",
      showColumn: props.api_type === 'python' ? true : false,
    },
    {
      name: "model_name",
      label: "Model Name",
      type: "select",
      placeholder: "Model Name",
      value: "",
      help: "Model Name",
      info: "Model Name",
      showColumn: props.api_type === 'python' ? true : false,
    }
  ]
}

const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {

  switch (actionData.action) {
    case 'close':
      closeNewResourceDialog()
      break
    case 'save':
      newResourceForm.value = actionData.formData
      submitNewResource()
      break
  }
}

// DataGrid action handlers
const handleDataGridActionHandler = (actionData: { action: string; selectedRows: any[]; selectedData: any[], selectedIndex: any[] }) => {
  switch (actionData.action) {
    case 'create':
      openNewResourceDialog()
      break
  }
}

const handleDataGridRowActionHandler = (actionData: { type: string; payload: any, index: number }) => {
  switch (actionData.type) {
    case 'single-edit':
      openEditResourceDialog(actionData.payload, actionData.index)
      break
    case 'single-delete':
      handleDelete(actionData.payload)
      break
  }
}

const handleDataGridRowClick = (row: any, index: number) => {
  console.log('row', row)
  console.log('index', index)
  openEditResourceDialog(row, index)
}

// New Resource Dialog handlers
const openNewResourceDialog = () => {
  newResourceForm.value = {
    name: '',
    type: props.api_type === 'python' ? '' : null,
    model_name: props.api_type === 'python' ? '' : null
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


const submitNewResource = async () => {
  
  newResourceLoading.value = true
  newResourceError.value = ''
  
  if (!props.apiData) {
    newResourceError.value = 'API data not available'
    newResourceLoading.value = false
    return
  }
  
  try {
    const newResource: any = {
      name: newResourceForm.value.name
    }

    if (props.api_type === 'python') {
      newResource.type = newResourceForm.value.type
      if(newResourceForm.value.type === 'Model'){
        newResource.model_name = newResourceForm.value.model_name
      }
      
    }

    // duplicate name check
    const duplicateName = props.apiData.resources?.some((res, index) =>
      res.name === newResource.name && index !== editingResourceIndex.value
    )

    // duplicate model_name check
    let duplicateModel = false
    if (props.api_type === 'python') {
      duplicateModel = props.apiData.resources?.some((res, index) =>
        res.model_name === newResourceForm.value.model_name &&
        index !== editingResourceIndex.value
      )
    }

    if (duplicateName) {
      newResourceError.value = `A resource with the name "${newResource.name}" already exists.`
      newResourceLoading.value = false
      return
    }

    if (duplicateModel) {
      newResourceError.value = `The model "${newResource.model_name}" is already assigned to another resource.`
      newResourceLoading.value = false
      return
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


    // const responseData = await response.json()
    props.updateApiData(updatedApiData)
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
        
        props.updateApiData(updatedApiData)
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
    type:  props.api_type === 'python' ? resource.type || 'Other' : null,
    model_name:  props.api_type === 'python' ? resource.model_name || '': null
  }
  newResourceDialogOpen.value = true
}






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
        <div class="relative min-h-[100vh] flex-1 p-4">
            
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
              <AlertModal
                :isOpen="newResourceDialogOpen"
                :title="isEditMode ? 'Edit Resource' : 'Create New Resource'"
                @close="closeNewResourceDialog"
            >
                <FormViewer 
                :formConfig="formConfig" 
                :initialData="newResourceForm" 
                :cancelAction="'close'"
                :actionHandler="handleFormAction"
                :actions="[
                  { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                  { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                ]" />
            </AlertModal>
              <DataGrid
            
                :schema="resourcesSchema"
                :data="apiData.resources"
                :clickableRows="true"
                :rowActionDropdown="false"
                :showCheckboxes="false"
                :actions="[
                  {name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus'}
                ]"
                :rowActions="[
                  { type: 'single-edit', label: 'Edit', icon: 'edit', colorClass: 'text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20' },
                  { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20' }
                ]"
                @actionHandler="handleDataGridActionHandler"
                @rowActionHandler="handleDataGridRowActionHandler"
                @rowClick="handleDataGridRowClick"
              
              />
             
                
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
  
</template>
<!--  OLD CODE --------------
<div class="w-full">

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
                  <div v-if="props.api_type === 'python'">
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
                      <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full justify-between"
                        >
                            {{ newResourceForm.model_name || 'Select model' }}
                            <ChevronDown class="ml-1 h-4 w-4" />
                        </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" class="w-full">
                        <DropdownMenuItem
                            v-for="model in apiData.version_models || []"
                            :key="model.name"
                            @click="newResourceForm.model_name = model.name"
                            :class="['w-full', {'font-semibold text-blue-600': newResourceForm.model_name === model.name }]"
                        >
                            {{ model.name }}
                        </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    
                  </div>
                  <div v-if="newResourceError" class="text-red-600 text-sm">{{ newResourceError }}</div>
              </div>
              <DialogFooter class="gap-2">
                  <DialogClose as-child>
                  <Button variant="secondary" type="button" @click="closeNewResourceDialog">Cancel</Button>
                  </DialogClose>
                  <Button type="submit" variant="default" :disabled="newResourceLoading || (props.api_type === 'python' && !newResourceForm.type)">
                  <span v-if="newResourceLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
                  <span v-else>{{ isEditMode ? 'Save' : 'Create' }}</span>
                  </Button>
              </DialogFooter>
              </form>
          </DialogContent>
          </Dialog>
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
</div> -->