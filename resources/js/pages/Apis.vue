<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { type BreadcrumbItem, type Api, ApiUser, User } from '@/types';
import { getCsrfToken } from '@/lib/utils';
import { useProxyServer } from '@/composables/useProxyServer';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'APIs',
    href: '/apis',
  },
]

const { serverApiType } = useProxyServer();


// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);
const apiFormRef = ref<InstanceType<typeof FormViewer> | null>(null);

// Data state
const apis = ref<Api[]>([]);
const users = ref<any[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// DataGrid configuration
const dataGridConfig = {
    label: '',
    description: '',
    name: "apis-datagrid",
    files: false,
    fields: [
        {
            name: "id",
            label: "ID",
            type: "text",
            placeholder: "",
            value: "",
            help: "API ID",
            info: "Unique identifier for the API",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: false,
            parse: false
        },
        {
            name: "name",
            label: "Name",
            type: "text",
            placeholder: "API name",
            value: "",
            help: "Name of the API",
            info: "API name",
            width: "12",
            offset: "0",
            required: true,
            show: true,
            edit: true,
            parse: true,

        },
        {
            name: "api_type",
            label: "Type",
            type: "select",
            placeholder: "API type",
            value: "",
            help: "Type of the API",
            info: "API type (e.g., python, php)",
            width: "12",
            offset: "0",
            options:[{ label: "Python", value: "python" ,color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'},
                    { label: "Php", value: "php" , color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'}                ],
            required: false,
            show: true,
            edit: false,
            parse: false
        },
        {
            name: "description",
            label: "Description",
            type: "text",
            placeholder: "API description",
            value: "",
            help: "Description of the API",
            info: "Detailed description",
            width: "12",
            offset: "0",
            required: false,
            show: false,
            edit: true,
            parse: true
        },
        {
            name: "tags",
            label: "Tags",
            type: "text",
            placeholder: "Tags",
            value: "",
            help: "Tags for the API",
            info: "Comma-separated tags",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: true,
            parse: true
        },
        {
            name: "created_at",
            label: "Created At",
            type: "text",
            placeholder: "",
            value: "",
            help: "Creation date",
            info: "When the API was created",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: false,
            parse: false
        },
        {
            name: "user_id",
            label: "Created By",
            type: "text",
            placeholder: "",
            value: "",
            help: "Creator user ID",
            info: "User who created the API",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: false,
            parse: false
        }
    ]
};

// Form configuration for modal
const formConfig = {
    label: 'API',
    description: 'Create or edit an API.',
    name: "api-form",
    files: false,
    fields: [
        {
            name: "name",
            label: "Name",
            type: "text",
            placeholder: "Enter API name",
            value: "",
            help: "Name of the API",
            info: "API name",
            width: "12",
            offset: "0",
            required: true,
            show: true,
            edit: true,
            parse: true
        },
        {
            name: "description",
            label: "Description",
            type: "textarea",
            placeholder: "Enter API description",
            value: "",
            help: "Description of the API",
            info: "Detailed description",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: true,
            parse: true
        },
        {
            name: "api_type",
            label: "Type",
            type: "select",
            placeholder: "Select API type",
            value: "python",
            help: "Choose API type",
            info: "Type of API (programming language)",
            width: "12",
            offset: "0",
            required: true,
            options: [
                { label: 'Python', value: 'python' },
                { label: 'PHP', value: 'php' }
            ],
            multiple: false,
            show: true,
            edit: false,
            parse: true
        },
        {
            name: "tags",
            label: "Tags",
            type: "text",
            placeholder: "Enter tags (comma-separated)",
            value: "",
            help: "Tags for the API",
            info: "Comma-separated tags for categorization",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: true,
            parse: true
        }
    ]
};


// Clean form data for API submission
const cleanFormData = (formData: any) => {
    const cleaned = { ...formData };
    
    // Remove server-managed fields that shouldn't be sent to API
    if (modalMode.value === 'new') {
        delete cleaned.id;
    }
    
    // Remove empty strings and convert to null if needed
    Object.keys(cleaned).forEach(key => {
        if (cleaned[key] === '') {
            cleaned[key] = null;
        }
    });
    
    // Set default api_type if not provided ?
    if (!cleaned.api_type) {
        cleaned.api_type = 'python';
    }
    
    return cleaned;
};
const fetchApiUsers = async () => {
    try {
        const response = await fetch('api/users', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        if (!response.ok) {
            const errorData = await response.json()
            showError(errorData.error || 'Failed to fetch API users', 'Error')
            throw new Error(errorData.error || 'Failed to fetch API users')
        }
        const data = await response.json();
        users.value = data;
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch API users';
        // showError('Failed to fetch API users. Please try again.', 'Error');
        console.error('Error fetching API users:', err);
    }
};
// Fetch APIs from API
const fetchApis = async () => {
    try {
        await fetchApiUsers();
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`api/apis`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            const errorData = await response.json()
            showError(errorData.error || 'Failed to fetch APIs', 'Error');
            throw new Error(errorData.error || 'Failed to fetch API data')
        }
        
        const data = await response.json();
                
        // Handle different response formats: array, object with numeric keys, or mixed
        let apiArray: Api[] = [];
        
        if (Array.isArray(data)) {
            // Normal array response
            apiArray = data;
        } else if (data && typeof data === 'object') {
            // Object with numeric keys (e.g., {0: {...}, 1: {...}, error: "..."})
            apiArray = Object.keys(data)
                .filter(key => key !== 'error' && !isNaN(Number(key)))
                .map(key => data[Number(key)])
                .filter(item => item && typeof item === 'object');
            
            // Log error if present but don't fail completely
            if (data.error) {
                console.warn('API response contains error:', data.error);
                warning(`Some APIs may not have loaded correctly: ${data.error}`, 'Partial Data Load');
            }
        }
        
        
        // Format dates for display
        apis.value = apiArray.map((api: any) => ({
            ...api,
            api_type: api.api_type || 'php', // Default to 'php' if api_type is missing 
            created_at: api.created_at ? new Date(api.created_at).toLocaleDateString() : '',
            user_id: (api.user_id ? users.value.find((user: User) => user.id === api.user_id)?.name : "Unknown" ) as any
        })) as Api[];
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch APIs';
        // showError('Failed to fetch APIs. Please try again.', 'Error');
        console.error('Error fetching APIs:', err);
    } finally {
        loading.value = false;
    }
};

// Modal functions
const openNewModal = () => {
    modalMode.value = 'new';
    // editingRow.value = null;
    editingRow.value = {
        name: '',
        description: '',
        api_type: serverApiType.value || 'python',
        tags: ''
    }
    showModal.value = true;
};

const openEditModal = (row: any) => {
    modalMode.value = 'edit';
    editingRow.value = {
        ...row,
        // Remove formatted display values for editing
        created_at: undefined,
        created_by_id: undefined
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingRow.value = null;
};

const handleFormSubmit = async (formValues: any) => {
    if (!apiFormRef.value) {
        warning('Form is not ready. Please try again.', 'Validation Error');
        return;
    }
    const isValid = apiFormRef.value?.validateForm();
    if (!isValid) {
        warning('Please fix validation errors before saving.', 'Validation Error');
        return;
    }
    try {
        submitting.value = true;
        
        if (modalMode.value === 'new') {
            // Create new API
            const cleanedData = cleanFormData(formValues);

            // Check if name contains spaces
            if (cleanedData.api_type === 'php' && cleanedData.name && cleanedData.name.includes(' ')) {
                throw new Error('API name cannot contain spaces. Please use underscores or hyphens instead.');
            }

            const response = await fetch(`api/apis`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(cleanedData)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
            }

            const newApi = await response.json();
            


            // Format and add to local state
            // TO-DO: created_by_id mapping for php??
            apis.value.push({
                ...newApi,
                api_type: newApi.api_type || 'php',
                created_at: newApi.created_at ? new Date(newApi.created_at).toLocaleDateString() : new Date().toLocaleDateString(),
                user_id: newApi.user_id ? users.value.find((user: User) => user.id === newApi.user_id)?.name : "Unknown"
            });
            
            success('API created successfully!', 'API Created');
        } else {
            // Update existing API
            const cleanedData = cleanFormData(formValues);
             // Check if name contains spaces
            if (cleanedData.api_type === 'php' && cleanedData.name && cleanedData.name.includes(' ')) {
                throw new Error('API name cannot contain spaces. Please use underscores or hyphens instead.');
            }
            
            const response = await fetch(`api/apis/${editingRow.value.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ ...cleanedData, id: editingRow.value.id })
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || errorData.error || `HTTP error! status: ${response.status}`);
            }


            const updatedApi = await response.json();
            
            // Update local state
            const index = apis.value.findIndex(api => api.id === editingRow.value.id);
            if (index !== -1) {
                apis.value[index] = {
                    ...updatedApi,
                    api_type: updatedApi.api_type || 'php',
                    created_at: updatedApi.created_at ? new Date(updatedApi.created_at).toLocaleDateString() : apis.value[index].created_at,
                    user_id: updatedApi.user_id ? users.value.find((user: User) => user.id === updatedApi.user_id)?.name : "Unknown"
                };           
            }
            
            success('API updated successfully!', 'API Updated');
        }
        closeModal();
    } catch (err: any) {
        showError(err.message || 'Failed to save API. Please try again.', 'Error');
        console.error('Form submission error:', err);
    } finally {
        submitting.value = false;
    }
};

const handleDataGridActionHandler = (actionData: { action: string; selectedRows: any[]; selectedData: any[], selectedIndex: any[] }) => {
    switch (actionData.action) {
        case 'create':
            openNewModal();
            break;  
        case 'edit':
            openEditModal(actionData.selectedData[0]);
            break;
        case 'delete':
            handleDelete([actionData.selectedData[0].id]);
            break;
    }
};
// Handle DataGrid action events
const handleAction = (actionData: any) => {

    switch (actionData.type) {
        case 'single-delete':
            handleDelete([actionData.payload.id]);
            break;
        case 'view':
            // Navigate to API routes page
            router.visit(`/apis/${actionData.payload.id}/routes`);
            break;
        case 'single-edit':
            openEditModal(actionData.payload);
            break;
        default:
            warning('Unknown action type:', actionData.type);
    }
};

const handleRowClick = (row: any) => {
    router.visit(`apis/${row.id}/routes`);
};

const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {

    
    switch (actionData.type) {
        case 'close':
        case 'cancel':
            closeModal();
            break;
        case 'save':
            // Prevent multiple submissions
            if(!submitting.value) {
                handleFormSubmit(actionData.formData);
            }
            break;
        default:
            console.log('Unknown FormViewer action type:', actionData.type);
    }
};

const handleDelete = async (selectedRowIds?: number[]) => {
    if (selectedRowIds && selectedRowIds.length > 0) {
        const apisToDelete = apis.value.filter(api => selectedRowIds.includes(api.id));
        
        // Confirm deletion
        const apiNames = apisToDelete.map(api => api.name).join(', ');
        if (!confirm(`Are you sure you want to delete ${apisToDelete.length} API(s): ${apiNames}? This action cannot be undone.`)) {
            return;
        }
        
        try {
            // Delete APIs via API
            for (const api of apisToDelete) {
                const response = await fetch(`api/apis/${api.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken() || '',
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Failed to delete API. Status: ${response.status}`);
                }
            }
            
            // Remove from local state after successful API calls
            apisToDelete.forEach(api => {
                const index = apis.value.findIndex(a => a.id === api.id);
                if (index !== -1) {
                    apis.value.splice(index, 1);
                }
            });
            
            // Show success message
            if (apisToDelete.length === 1) {
                success(`API "${apisToDelete[0].name}" deleted successfully!`, 'API Deleted');
            } else {
                success(`${apisToDelete.length} APIs deleted successfully!`, 'APIs Deleted');
            }
        } catch (err: any) {
            showError(err.message || 'Failed to delete APIs. Please try again.', 'Error');
            console.error('Delete error:', err);
        }
    } else {
        warning('Please select at least one API to delete.', 'Selection Required');
    }
};

// Handle CSV upload
const handleCSVUpload = (uploadedData: any[]) => {

    info(`CSV uploaded with ${uploadedData.length} rows. Feature coming soon!`, 'CSV Upload');
};

// Fetch data on component mount
onMounted(() => {
    fetchApis();
});
</script>

<template>
    <Head title="APIs" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="">
                
                <!-- Loading State -->
                <div v-if="loading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-300">Loading APIs...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="error" class="flex justify-center items-center py-12">
                    <div class="text-red-600 dark:text-red-400">
                        <p class="text-lg font-semibold">{{ error }}</p>
                        <p class="text-sm">Couldn't load APIs.</p>
                        <button @click="fetchApis" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Try Again
                        </button>
                    </div>
                </div>

                <!-- DataGrid -->
                <DataGrid 
                    v-else
                    :schema="dataGridConfig"
                    :data="apis"
                    :count="25"
                    :search="true"
                    :filter="true"
                    :upload="true"
                    :download="true"
                    :columns="true"
                    theme="default"
                    :showNew="true"
                    :showEdit="true"
                    :showDelete="true"
                    :clickableRows="true"
                    :rowActions="[
                        { type: 'view', label: 'View Details', icon: 'eye', colorClass: 'text-blue-600 hover:bg-blue-50' },
                        { type: 'single-edit', label: 'Edit', icon: 'edit', colorClass: 'text-green-600 hover:bg-green-50' },
                        { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50' }
                    ]"
                    :actions="[
                        { name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus' },
                        { name: 'edit', type: 'primary', min: 1, max: 1, label: 'Edit', icon: 'edit', loc: 'right' },
                        { name: 'delete', type: 'danger', min: 1, max: 1, label: 'Delete', icon: 'trash', loc: 'right' }
                    ]"
                    @actionHandler="handleDataGridActionHandler"
                  
                    @rowActionHandler="handleAction"
                    @upload="handleCSVUpload"
                    @row-click="handleRowClick"
                >
                </DataGrid>

                <!-- Modal for New/Edit API -->
                <AlertModal 
                    :isOpen="showModal"
                    :title="modalMode === 'new' ? 'Create New API' : 'Edit API'"
                    @close="closeModal"
                >
                    <FormViewer 
                        ref="apiFormRef"
                        :formConfig="formConfig" 
                        :initialData="editingRow"
                        :cancelAction="'close'"
                        :actionHandler="handleFormAction"
                        :actions="[
                            { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                            { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                        ]"
                        :isSubmitting="submitting"
                    />
                </AlertModal>
                
                <!-- Global Toaster -->
                <Toaster />
            </div>
        </div>
    </AppLayout>
</template>