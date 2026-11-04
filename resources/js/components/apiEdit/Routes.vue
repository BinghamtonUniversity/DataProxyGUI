<script setup lang="ts">
import { computed, ref } from 'vue'
import { type ApiData, RouteData, Api } from '@/types'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';

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

const showDeleteModal = ref(false)
const pendingDeleteRoute = ref<RouteData | null>(null)
const deleting = ref(false)

// FormViewer refs for validation
const newRouteFormViewer = ref<any>(null);
const requiredParamsFormViewer = ref<any>(null);
const optionalParamsFormViewer = ref<any>(null);

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
            label: "Function",
            type: "text",
            placeholder: "Enter function name",
            value: "",
            help: "Name of the function",
            info: "Name of the function",
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
                { label: "PATCH", value: "PATCH" , color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'},
                { label: "ALL", value: "ALL" , color: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'}
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
            "min": 0,
            "max": 20
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
          "min": 0,
          "max": 20
        },
        fields: [
          { name: 'name', label: 'Name', type: 'text',offset: "0",width: "6", required: true },
          { name: 'example', label: 'Example', type: 'text',offset: "6",width: "6", required: false },
          { name: 'description', label: 'Description', type: 'textarea',offset: "0",width: "12", required: false },
        ]
      }
    ]
}
const newRouteFormConfig = computed(() => ({
    label: isEditMode.value ? 'Edit Route' : 'New Route',
    description: isEditMode.value ? 'Edit the selected route' : 'Create a new route',
    name: "new-route-form",
    files: false,
    fields: [
        //view_name name cannot be "Constructor"
        { name: 'description', label: 'Description', type: 'text', required: true },
        { name: 'view_name', label: 'Function', type: 'combobox',options: props.apiData?.version_views?.filter((view: any ) => view.name !== 'Constructor').map((view: any ) => ({ label: view.name, value: view.name })), required: true },
        //path should start with /
        { name: 'path', label: 'Path', type: 'text', required: true, validate: [{ type: 'pattern', regex: '^/', message: 'Path must start with /', conditions: true }] },
        { name: 'verb', label: 'HTTP Method', type: 'select', required: true, options: [
            { label: 'GET', value: 'GET' },
            { label: 'POST', value: 'POST' },
            { label: 'PUT', value: 'PUT' },
            { label: 'DELETE', value: 'DELETE' },
            { label: 'PATCH', value: 'PATCH' },
            { label: 'ALL', value: 'ALL' }
        ] },
        
    ]
}))

const handleRequiredParamsFormAction = async (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.action) {
    case 'close':
    case 'cancel':
      closeRequiredParamsDialog()
      break
    case 'save':
    case 'submit':
      // Validate form before submitting
      if (requiredParamsFormViewer.value) {
        const isValid = requiredParamsFormViewer.value.validateForm();
        if (!isValid) {
          // Validation failed - errors are already displayed by FormViewer
          return
        }
      }
      requiredParamsData.value = { 
        required_parameters: actionData.formData.required_parameters || []
      }
      await submitRequiredParams()
      break
  }
}

const handleOptionalParamsFormAction = async (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.action) {
    case 'close':
    case 'cancel':
      closeOptionalParamsDialog()
      break
    case 'save':
      // Validate form before submitting
      if (optionalParamsFormViewer.value) {
        const isValid = optionalParamsFormViewer.value.validateForm()
        if (!isValid) {
          // Validation failed - errors are already displayed by FormViewer
          return
        }
      }
      optionalParamsData.value = {
        optional_parameters: actionData.formData.optional_parameters || []
      }
      await submitOptionalParams()
      break
  }
}

const handleRouteFormAction = async (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.action) {
    case 'close':
    case 'cancel':
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
      warning('Unknown action type:', actionData.action);
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
        // const existingRoutes = props.apiData.version_urls || []
        // const duplicate = existingRoutes.some((route, index) => {
        //     const samePath = route.path.trim() === newRoute.path
        //     const sameVerb = route.verb.trim().toUpperCase() === newRoute.verb
        //     const isSameRoute = isEditMode.value && index === editingRouteIndex.value
            
        //     return samePath && sameVerb && !isSameRoute
        // })

        // if (duplicate) {
        //     newRouteError.value = `A route with path "${newRoute.path}" and verb "${newRoute.verb}" already exists.`
        //     error( newRouteError.value, 'Duplicate Route')
        //     newRouteLoading.value = false
        //     return
        // }

        // Duplicate route check
        // PHP routes by path + verb. Django ignores the verb, so for Python the path alone must be unique.
        const isPython = props.api_type === 'python'
        const newPath = newRoute.path.trim()
        const newVerb = newRoute.verb.trim().toUpperCase()

        const existingRoutes = props.apiData.version_urls || []
        const duplicate = existingRoutes.find((route, index) => {
            const samePath = route.path.trim() === newPath
            const sameVerb = route.verb.trim().toUpperCase() === newVerb
            const isSameRoute = isEditMode.value && index === editingRouteIndex.value

            const sameParams = (route.required || []).map(p => p.name).join('/') === (newRoute.required || []).map((p: any) => p.name).join('/')

            return samePath && (isPython ? sameParams : true) && (isPython || sameVerb) && !isSameRoute
        })

        if (duplicate) {
            newRouteError.value = isPython
                ? `A route with path "${newPath}" already exists (${duplicate.verb}). Django routes by path only, so use one route and check request.method in the function.`
                : `A route with path "${newPath}" and verb "${newVerb}" already exists.`
            error(newRouteError.value, 'Duplicate Route')
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

const handleDelete = (route: RouteData) => {
    pendingDeleteRoute.value = route
    showDeleteModal.value = true
}

const closeDeleteModal = () => {
    if (deleting.value) return
    showDeleteModal.value = false
    pendingDeleteRoute.value = null
}

const confirmDelete = async () => {
    const route = pendingDeleteRoute.value
    if (!route) {
        closeDeleteModal()
        return
    }

    if (!props.apiData) {
        console.error('API data not available')
        closeDeleteModal()
        return
    }

    deleting.value = true
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
        showDeleteModal.value = false
        pendingDeleteRoute.value = null
    } catch (err: any) {
        console.error('Error deleting route:', err)
        error(err.message || 'Error deleting route', 'Error');
    } finally {
        deleting.value = false
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



// ---------------------------------------------------------------------------
// Sort Routes Dialog (drag & drop)
// ---------------------------------------------------------------------------
const sortDialogOpen = ref(false)
const sortSaving = ref(false)
// Each item keeps a reference to the original route object + its original index
const sortableRoutes = ref<{ route: RouteData; originalIndex: number }[]>([])
const dragIndex = ref<number | null>(null)
const dragOverIndex = ref<number | null>(null)

const sortOrderChanged = computed(() =>
    sortableRoutes.value.some((item, index) => item.originalIndex !== index)
)

const openSortDialog = () => {
    const routes = props.apiData?.version_urls || []
    if (routes.length < 2) {
        info('You need at least two routes to change the order.', 'Nothing to Sort')
        return
    }
    sortableRoutes.value = routes.map((route, index) => ({ route, originalIndex: index }))
    dragIndex.value = null
    dragOverIndex.value = null
    sortDialogOpen.value = true
}

const closeSortDialog = () => {
    if (sortSaving.value) return
    sortDialogOpen.value = false
    sortableRoutes.value = []
    dragIndex.value = null
    dragOverIndex.value = null
}

const moveSortItem = (from: number, to: number) => {
    if (from === to || to < 0 || to >= sortableRoutes.value.length) return
    const items = [...sortableRoutes.value]
    const [moved] = items.splice(from, 1)
    items.splice(to, 0, moved)
    sortableRoutes.value = items
}

const onSortDragStart = (event: DragEvent, index: number) => {
    dragIndex.value = index
    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'
        // Firefox requires data to be set for drag to start
        event.dataTransfer.setData('text/plain', String(index))
    }
}

const onSortDragOver = (event: DragEvent, index: number) => {
    event.preventDefault()
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move'
    if (dragIndex.value === null || dragIndex.value === index) return
    // Live reorder while dragging
    moveSortItem(dragIndex.value, index)
    dragIndex.value = index
    dragOverIndex.value = index
}

const onSortDrop = (event: DragEvent) => {
    event.preventDefault()
    dragIndex.value = null
    dragOverIndex.value = null
}

const onSortDragEnd = () => {
    dragIndex.value = null
    dragOverIndex.value = null
}

const resetSortOrder = () => {
    sortableRoutes.value = [...sortableRoutes.value].sort((a, b) => a.originalIndex - b.originalIndex)
}

const saveSortOrder = async () => {
    if (!props.apiData) {
        error('API data not available', 'Error')
        return
    }
    if (!sortOrderChanged.value) {
        closeSortDialog()
        return
    }

    sortSaving.value = true
    try {
        const updatedApiData = {
            ...props.apiData,
            version_urls: sortableRoutes.value.map(item => item.route),
        }
        // Update Layout's apiData only (persisted when the user clicks Layout's Save)
        props.updateApiData(updatedApiData)
        success('Route order updated. Please save.', 'Order Updated')

        sortSaving.value = false
        closeSortDialog()
    } catch (err: any) {
        error(err.message || 'Error saving route order', 'Error')
    } finally {
        sortSaving.value = false
    }
}

const verbBadgeClass = (verb: string) => {
    switch ((verb || '').toUpperCase()) {
        case 'GET': return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        case 'POST': return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
        case 'PUT': return 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
        case 'PATCH': return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
        case 'DELETE': return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
        case 'ALL': return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
        default: return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
    }
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
        case 'sort':
            openSortDialog();
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
            warning('Unknown action type:', actionData.action);
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
            warning('Unknown action type:', actionData.type);
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
                    :rowActionDropdown="true"
                    :rowActionLabels="true"
                    :rowActions="[
                    
                        { type: 'required_parameters', label: 'Required Parameters', icon: 'cog', colorClass: 'text-orange-600 hover:bg-orange-50 dark:text-orange-400 dark:hover:bg-orange-900/20' },
                        { type: 'optional_parameters', label: 'Optional Parameters', icon: 'cog', colorClass: 'text-blue-600 hover:bg-blue-50 dark:text-blue dark:hover:bg-blue-900/20' },
                        { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20' }
                    ]"
                    :actions="[
                        { name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus' },
                        { name: 'sort', type: 'info', min: 0, label: 'Sort', loc: 'right', icon: 'sort' },
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
                    ref="requiredParamsFormViewer"
                    :formConfig="requiredParamsFormConfig" 
                    :initialData="requiredParamsData" 
                    :validateOnSubmit="true"
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
                    ref="optionalParamsFormViewer"
                    :formConfig="optionalParamsFormConfig" 
                    :initialData="optionalParamsData" 
                    :validateOnSubmit="true"
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

                <!-- Sort Routes Dialog -->
                <AlertModal
                    :isOpen="sortDialogOpen"
                    title="Sort Routes"
                    @close="closeSortDialog"
                >
                    <div class="space-y-4 w-full min-w-0 [contain:inline-size]">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Drag the routes to change their order, then click Save.
                        </p>

                        <ul class="space-y-2 max-h-[60vh] overflow-y-auto overflow-x-hidden pr-1 w-full min-w-0">
                            <li
                                v-for="(item, index) in sortableRoutes"
                                :key="`${item.originalIndex}-${item.route.verb}-${item.route.path}`"
                                draggable="true"
                                @dragstart="onSortDragStart($event, index)"
                                @dragover="onSortDragOver($event, index)"
                                @drop="onSortDrop"
                                @dragend="onSortDragEnd"
                                :class="[
                                    'flex items-center gap-3 p-3 border rounded-lg bg-white dark:bg-gray-800 cursor-move select-none transition-all w-full min-w-0 overflow-hidden',
                                    dragIndex === index
                                        ? 'opacity-50 border-blue-400 ring-2 ring-blue-400/40'
                                        : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
                                ]"
                            >
                                <!-- Drag handle -->
                                <svg class="w-4 h-4 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 4a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm9-12a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm0 6a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
                                </svg>

                                <span class="w-6 text-xs text-gray-500 dark:text-gray-400 text-right shrink-0">{{ index + 1 }}</span>

                                <span :class="['px-2 py-0.5 text-xs font-semibold rounded shrink-0 w-16 text-center', verbBadgeClass(item.route.verb)]">
                                    {{ item.route.verb }}
                                </span>

                                <div class="flex-1 min-w-0 overflow-hidden">
                                    <div class="font-mono text-sm text-gray-900 dark:text-white truncate" :title="item.route.path">
                                        {{ item.route.path }}
                                    </div>
                                    <div
                                        class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                        :title="item.route.description ? `${item.route.view_name} · ${item.route.description}` : item.route.view_name"
                                    >
                                        {{ item.route.view_name }}<span v-if="item.route.description"> · {{ item.route.description }}</span>
                                    </div>
                                </div>

                                <!-- Up / Down buttons (keyboard / non-drag fallback) -->
                                <div class="flex flex-col shrink-0">
                                    <button
                                        type="button"
                                        class="p-0.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 disabled:opacity-30 disabled:cursor-not-allowed"
                                        :disabled="index === 0"
                                        title="Move up"
                                        @click="moveSortItem(index, index - 1)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                                    </button>
                                    <button
                                        type="button"
                                        class="p-0.5 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 disabled:opacity-30 disabled:cursor-not-allowed"
                                        :disabled="index === sortableRoutes.length - 1"
                                        title="Move down"
                                        @click="moveSortItem(index, index + 1)"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                </div>
                            </li>
                        </ul>

                        <div class="flex items-center justify-between pt-2">
                            <button
                                type="button"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 disabled:opacity-40 disabled:cursor-not-allowed"
                                :disabled="!sortOrderChanged || sortSaving"
                                @click="resetSortOrder"
                            >
                                Reset
                            </button>
                            <div class="flex gap-2">
                                <button
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors disabled:opacity-50"
                                    :disabled="sortSaving"
                                    @click="closeSortDialog"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="button"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="!sortOrderChanged || sortSaving"
                                    @click="saveSortOrder"
                                >
                                    {{ sortSaving ? 'Saving...' : 'Save' }}
                                </button>
                            </div>
                        </div>
                    </div>
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

    <ConfirmDeleteModal
        :isOpen="showDeleteModal"
        :count="pendingDeleteRoute ? 1 : 0"
        :deleting="deleting"
        @confirm="confirmDelete"
        @close="closeDeleteModal"
    />
</template>