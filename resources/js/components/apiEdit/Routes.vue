<script setup lang="ts">
import { ref } from 'vue'
import { type ApiData, RouteData, Api } from '@/types'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';

interface Props {
    api_id: string
    api_type: string
    api: Api | null
    apiData: ApiData | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
    handleSave?: () => Promise<void>
    highlightQuery?: string
    highlightTarget?: string
}

const props = defineProps<Props>()

// New Route Dialog
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
const editingParamsIndex = ref<number | null>(null)
const requiredParamsDialogOpen = ref(false)
const optionalParamsDialogOpen = ref(false)
const requiredParamsData = ref<any>(null);
const optionalParamsData = ref<any>(null);

// Toaster
const { success, error, warning, info } = useToaster();

// FormViewer ref for validation
const newRouteFormViewer = ref<any>(null);

// DataGrid form configuration for routes
const routeFormConfig = {
    label: 'Route',
    description: 'API Route Configuration',
    name: "route-form",
    files: false,
    fields: [
        { name: 'description', label: 'Description', type: 'text', required: false },
        {
            name: "view_name",
            label: "View Name",
            type: "text",
            placeholder: "Enter view name",
            value: "",
            help: "Name of the view function",
            info: "Name of the view function",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "path",
            label: "Path",
            type: "text",
            placeholder: "/api/endpoint",
            value: "",
            help: "API endpoint path",
            info: "API endpoint path",
            width: "12",
            offset: "0",
            required: true,
            labelColor: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
        },
        {
            name: "verb",
            label: "HTTP Method",
            type: "select",
            placeholder: "Select HTTP method",
            value: "GET",
            help: "HTTP method for the route",
            info: "HTTP method for the route",
            width: "12",
            offset: "0",
            options: [
                { label: "GET", value: "GET" ,color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'},
                { label: "POST", value: "POST" , color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'},
                { label: "PUT", value: "PUT" , color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'},
                { label: "DELETE", value: "DELETE" , color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'},
                { label: "PATCH", value: "PATCH" , color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'}
            ],
            multiple: false,
            show: true,
            edit: true,
            parse: true,
            required: true
        },
        {
            name: "required",
            label: "Required Parameters",
            type: "text",
            placeholder: "Enter required parameters",
            value: "",
            help: "Required parameters of the route",
            info: "Required parameters of the route",
            width: "12",
            offset: "0",
            required: false,
            isArrayObject: true,
            targetObjectAttribute: "name",
            targetColor: "bg-orange-50 border-orange-200 text-orange-800 dark:bg-orange-900/20 dark:border-orange-800 dark:text-orange-200",
            mergedTo: "parameters",
            showColumn: false
        },
        {
            name: "optional",
            label: "Optional Parameters",
            type: "text",
            placeholder: "Enter optional parameters",
            value: "",
            help: "Optional parameters of the route",
            info: "Optional parameters of the route",
            width: "12",
            offset: "0",
            required: false,
            isArrayObject: true,
            targetObjectAttribute: "name",
            targetColor: "bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-200",
            mergedTo: "parameters",
            showColumn: false
        },

        {
            name: "parameters",
            label: "Parameters",
            type: "text",
            placeholder: "Enter parameters",
            value: "",
            help: "Parameters of the route",
            info: "Parameters of the route",
            width: "12",
            offset: "0",
            required: false,
        }
    ]
}

const requiredParamsFormConfig = {
    label: 'Required Parameters',
    description: 'Required Parameters Configuration',
    name: "required-params-form",
    files: false,
    fields: [
        { name: 'required_parameters', label: '', type: 'fieldset', 
            array: {
            "min": 1,
            "max": ""
            },
            fields:[
                { name: 'name', label: 'Name', type: 'text',offset: "0",width: "6", required: true },
                { name: 'example', label: 'Example', type: 'text',offset: "6",width: "6", required: false },
                { name: 'description', label: 'Description', type: 'textarea',offset: "0",width: "12", required: false },
            ]  
        }
    ]
}
const optionalParamsFormConfig = {
    label: 'Optional Parameters Configuration',
    description: '',
    name: "optional-params-form",
    files: false,
    fields: [
      { name: 'optional_parameters', label: '', type: 'fieldset',
        array: {
          "min": 1,
          "max": ""
        },
        fields: [
          { name: 'name', label: 'Name', type: 'text',offset: "0",width: "6", required: true },
          { name: 'example', label: 'Example', type: 'text',offset: "6",width: "6", required: false },
          { name: 'description', label: 'Description', type: 'textarea',offset: "0",width: "12", required: false },
        ]
      }
    ]
}
const newRouteFormConfig = {
    label: 'New Route',
    description: 'Create a new route',
    name: "new-route-form",
    files: false,
    fields: [
        //view_name name cannot be "Constructor"
        { name: 'view_name', label: 'View Name', type: 'select',options: props.apiData?.version_views?.filter((view: any ) => view.name !== 'Constructor').map((view: any ) => ({ label: view.name, value: view.name })), required: true },
        //path should start with /
        { name: 'path', label: 'Path', type: 'text', required: true, validate: [{ type: 'pattern', regex: '^/', message: 'Path must start with /', conditions: true }] },
        { name: 'verb', label: 'HTTP Method', type: 'select', required: true, options: [
            { label: 'GET', value: 'GET' },
            { label: 'POST', value: 'POST' },
            { label: 'PUT', value: 'PUT' },
            { label: 'DELETE', value: 'DELETE' },
            { label: 'PATCH', value: 'PATCH' }
        ] },
        { name: 'description', label: 'Description', type: 'text', required: false },
    ]
}

const handleRequiredParamsFormAction = (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.action) {
    case 'close':
      closeRequiredParamsDialog()
      break
    case 'save':
      requiredParamsData.value = { 
        required_parameters: actionData.formData.required_parameters || []
      }
      submitRequiredParams()
      break
  }
}

const handleOptionalParamsFormAction = (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.action) {
    case 'close':
      closeOptionalParamsDialog()
      break
    case 'save':
      optionalParamsData.value = {
        optional_parameters: actionData.formData.optional_parameters || []
      }
      submitOptionalParams()
      break
  }
}

const handleRouteFormAction = async (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.action) {
    case 'close':
      closeNewRouteDialog()
      break
    case 'save':
    case 'submit':
      // Use FormViewer's built-in validation to show errors inline
      if (newRouteFormViewer.value) {
        const isValid = newRouteFormViewer.value.validateForm()
        if (!isValid) {
          // Validation errors are already displayed in the form by FormViewer
          return
        }
      }
      newRouteForm.value = {
        description: actionData.formData.description,
        path: actionData.formData.path,
        verb: actionData.formData.verb,
        view_name: actionData.formData.view_name,
        required: actionData.formData.required,
        optional: actionData.formData.optional,
      }
      submitNewRoute(newRouteForm.value)
      break;
    default:
      console.log('Unknown action type:', actionData.action);
      break;
  }
}
const openNewRouteDialog = () => {
isEditMode.value = false;
editingRouteIndex.value = null;
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


const openRequiredParamsDialog = (route: RouteData, index: number) => {
  editingParamsIndex.value = index
  requiredParamsData.value = {
    required_parameters: route.required ? route.required : []
  }
  requiredParamsDialogOpen.value = true
}

const openOptionalParamsDialog = (route: RouteData, index: number) => {
  editingParamsIndex.value = index
  optionalParamsData.value = {
    optional_parameters: route.optional ? JSON.parse(JSON.stringify(route.optional)) : []
  }
  optionalParamsDialogOpen.value = true
}

const closeRequiredParamsDialog = () => {
  requiredParamsDialogOpen.value = false
  editingParamsIndex.value = null
  requiredParamsData.value = null
}

const closeOptionalParamsDialog = () => {
  optionalParamsDialogOpen.value = false
  editingParamsIndex.value = null
  optionalParamsData.value = null
}

const submitRequiredParams = async () => {
  if (!props.apiData || editingParamsIndex.value === null || editingParamsIndex.value === undefined){
    error('API data not available', 'Error')
    return
  }

  const updatedRoutes = [...props.apiData.version_urls]
  updatedRoutes[editingParamsIndex.value] = {
    ...updatedRoutes[editingParamsIndex.value],
    required: (requiredParamsData.value?.required_parameters || []).filter((p: { name: string }) => p.name?.trim()) || [],
  }

  const updatedApiData = { ...props.apiData, version_urls: updatedRoutes }

  props.updateApiData(updatedApiData)
  success('Required parameters updated successfully', 'Updated')
  closeRequiredParamsDialog()
}

const submitOptionalParams = async () => {
  if (!props.apiData || editingParamsIndex.value === null || editingParamsIndex.value === undefined){
    error('API data not available', 'Error')
    return
  }

  const updatedRoutes = [...props.apiData.version_urls]
  updatedRoutes[editingParamsIndex.value] = {
    ...updatedRoutes[editingParamsIndex.value],
    optional: (optionalParamsData.value?.optional_parameters || []).filter((p: { name: string }) => p.name?.trim()) || [],
  }

  const updatedApiData = { ...props.apiData, version_urls: updatedRoutes }

  props.updateApiData(updatedApiData)
  success('Optional parameters updated successfully', 'Updated')
  closeOptionalParamsDialog()
}

const submitNewRoute = async (formData: any) => {

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
            required: newRouteForm.value.required,
            optional: newRouteForm.value.optional,
            // NOTE (ECT): Trimming is not working as expected, so we are not using it for now
            // required: newRouteForm.value.required.filter(param => param.name.trim() && param.description.trim() && param.example.trim()),
            // optional: newRouteForm.value.optional.filter(param => param.name.trim() && param.description.trim() && param.example.trim()),
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

        props.updateApiData(updatedApiData)
        debugger;
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
 
        props.updateApiData(updatedApiData)
        success(`Path "${route.path}-${route.verb}" deleted successfully`, 'Route Deleted');

    } catch (err: any) {
        console.error('Error deleting route:', err)
        error(err.message || 'Error deleting route', 'Error');
    }
}

const openEditRouteDialog = ( payload: any, index: number) => {
  isEditMode.value = true
  editingRouteIndex.value = index
  newRouteForm.value = {
    description: payload.description || '',
    path: payload.path,
    verb: payload.verb,
    view_name: payload.view_name,
    required: payload.required?.map((p: any) => ({
      name: p.name,
      description: p.description || '',
      example: p.example || ''
    })) || [],
    optional: payload.optional?.map((p: any) => ({
      name: p.name,
      description: p.description || '',
      example: p.example || ''
    })) || []
  }

  newRouteDialogOpen.value = true
}



// Function to highlight text in UI elements
const highlightText = (text: string, query: string) => {
    if (!query || !text) return text
    
    const regex = new RegExp(`(${query})`, 'gi')
    return text.replace(regex, '<mark class="search-highlight">$1</mark>')
}

// DataGrid action handlers
const handleDataGridActionHandler = (actionData: { action: string; selectedRows: any[]; selectedData: any[], selectedIndex: any[] }) => {


    switch (actionData.action) {
        case 'create':
            openNewRouteDialog();
            break;
        case 'edit':
            openEditRouteDialog(actionData.selectedData[0], actionData.selectedIndex[0]);
            break;
        case 'delete':
            handleDelete(actionData.selectedData[0]);
            break;
        case 'required_parameters':
            openRequiredParamsDialog(actionData.selectedData[0], actionData.selectedIndex[0]);
            break;
        case 'optional_parameters':
            openOptionalParamsDialog(actionData.selectedData[0], actionData.selectedIndex[0]);
            break;
        default:
            console.log('Unknown action type:', actionData.action);
            break;
    }
};

// DataGrid row action handlers
const handleDataGridRowActionHandler = (actionData: { type: string; payload: any, index: number }) => {
    
    switch (actionData.type) {
        case 'single-edit':
            openEditRouteDialog(actionData.payload, actionData.index);
            break;
        case 'single-delete':
            handleDelete(actionData.payload);
            break;
        case 'required_parameters':
            openRequiredParamsDialog(actionData.payload, actionData.index);
            break;
        case 'optional_parameters':
            openOptionalParamsDialog(actionData.payload, actionData.index);
            break;
        default:
            console.log('Unknown action type:', actionData.type);
    }
};
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
               
                <!-- DataGrid Section -->
                <DataGrid 
                    :schema="routeFormConfig"
                    :data="apiData?.version_urls || []"
                    theme="default"
                    :clickableRows="true"
                    :rowActionDropdown="false"
                    :rowActionLabels="false"
                    :rowActions="[
                    
                        { type: 'required_parameters', label: 'Required Parameters', icon: 'cog', colorClass: 'text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20' },
                        { type: 'optional_parameters', label: 'Optional Parameters', icon: 'cog', colorClass: 'text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20' },
                        { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20' }
                    ]"
                    :actions="[
                        { name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus' },
                        { name: 'required_parameters', type: 'warning', min: 1, max: 1, label: 'Required Parameters', icon: 'cog', loc: 'right' },
                        { name: 'optional_parameters', type: 'info', min: 1, max: 1, label: 'Optional Parameters', icon: 'cog', loc: 'right' },
                        { name: 'edit', type: 'primary', min: 1, max: 1, label: 'Edit', icon: 'edit', loc: 'right' },
                        { name: 'delete', type: 'danger', min: 1, max: 25, label: 'Delete', icon: 'trash', loc: 'right' }
                    ]"
                    @actionHandler="handleDataGridActionHandler"                    
                    @rowClick="openEditRouteDialog"
                    @rowActionHandler="handleDataGridRowActionHandler"
                />
                <AlertModal
                    :isOpen="requiredParamsDialogOpen"
                    :title="''"
                    @close="closeRequiredParamsDialog"
                >
                    <FormViewer 
                    :formConfig="requiredParamsFormConfig" 
                    :initialData="requiredParamsData" 
                    :actions="[ { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' }, 
                                { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                            ]" 
                    :actionHandler="handleRequiredParamsFormAction" 
                    :cancelAction="'close'"
                    />
                </AlertModal>
                <AlertModal
                    :isOpen="optionalParamsDialogOpen"
                    :title="''"
                    @close="closeOptionalParamsDialog"
                >   
                    <FormViewer 
                    :formConfig="optionalParamsFormConfig" 
                    :initialData="optionalParamsData" 
                    :actions="[ { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                                { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                                ]" 
                    :actionHandler="handleOptionalParamsFormAction" 
                    :cancelAction="'close'"
                    />
                </AlertModal>
         

                <AlertModal
                    :isOpen="newRouteDialogOpen"
                    :title="isEditMode ? 'Edit Route' : 'Create New Route'"
                    @close="closeNewRouteDialog"
                >
                    <FormViewer 
                    ref="newRouteFormViewer"
                    :formConfig="newRouteFormConfig" 
                    :initialData="newRouteForm" 
                    :actions="[{ type: 'submit', action: 'submit', label: 'Submit', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' }, 
                    { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }]" 
                    :actionHandler="handleRouteFormAction"
                    :cancelAction="'close'" />
                </AlertModal>
             
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

