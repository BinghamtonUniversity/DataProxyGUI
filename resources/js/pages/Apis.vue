<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { type BreadcrumbItem, type Api, ApiUser } from '@/types';
import { getCsrfToken } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'APIs',
    href: '/apis',
  },
]

// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);

// Data state
const apis = ref<Api[]>([]);
const apiUsers = ref<ApiUser[]>([]);
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
            info: "API type (e.g., python, php, javascript)",
            width: "12",
            offset: "0",
            options:[{ label: "Python", value: "python" ,color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'},
                    { label: "Php", value: "php" , color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'}
                ],
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
            name: "created_by_id",
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
                { label: 'PHP', value: 'php' },
                { label: 'JavaScript', value: 'javascript' }
            ],
            multiple: false,
            show: true,
            edit: true,
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
        const response = await fetch('/api/api_users', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        apiUsers.value = data;
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch API users';
        showError('Failed to fetch API users. Please try again.', 'Error');
        console.error('Error fetching API users:', err);
    }
};
// Fetch APIs from API
const fetchApis = async () => {
    try {
        await fetchApiUsers();
        loading.value = true;
        error.value = null;
        
        const response = await fetch('/api/apis', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Fetched APIs:', data);
        // Format dates for display
        apis.value = data.map((api: Api) => ({
            ...api,
            created_at: api.created_at ? new Date(api.created_at).toLocaleDateString() : '',
            created_by_id: api.created_by_id ? apiUsers.value.find((user: ApiUser) => user.id === api.created_by_id)?.app_name : ''
        }));
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch APIs';
        showError('Failed to fetch APIs. Please try again.', 'Error');
        console.error('Error fetching APIs:', err);
    } finally {
        loading.value = false;
    }
};

// Modal functions
const openNewModal = () => {
    modalMode.value = 'new';
    editingRow.value = null;
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
    try {
        submitting.value = true;
        
        if (modalMode.value === 'new') {
            // Create new API
            const cleanedData = cleanFormData(formValues);
   
            
            const response = await fetch('/api/apis', {
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
            apis.value.push({
                ...newApi,
                created_at: newApi.created_at ? new Date(newApi.created_at).toLocaleDateString() : new Date().toLocaleDateString(),
                created_by_id: newApi.created_by ? apiUsers.value.find((user: ApiUser) => user.id === newApi.created_by)?.app_name : 'Unknown'
            });
            
            success('API created successfully!', 'API Created');
        } else {
            // Update existing API
            const cleanedData = cleanFormData(formValues);
      
            
            const response = await fetch(`/api/apis/${editingRow.value.id}`, {
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
                    created_at: updatedApi.created_at ? new Date(updatedApi.created_at).toLocaleDateString() : apis.value[index].created_at,
                    created_by_id: updatedApi.created_by ? apiUsers.value.find((user: ApiUser) => user.id === updatedApi.created_by)?.app_name : 'Unknown'
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
    console.log('DataGrid action data:', actionData);
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
            console.log('Unknown action type:', actionData.type);
    }
};

const handleRowClick = (row: any) => {
    router.visit(`/apis/${row.id}/routes`);
};

const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {

    
    switch (actionData.type) {
        case 'close':
            closeModal();
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
                const response = await fetch(`/api/apis/${api.id}`, {
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
                        <p class="text-lg font-semibold">Error loading APIs</p>
                        <p class="text-sm">{{ error }}</p>
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
                        :formConfig="formConfig" 
                        :initialData="editingRow"
                        :cancelAction="'close'"
                        @submit="handleFormSubmit"
                        @action="handleFormAction"
                        :actions="[
                            { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                            { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                        ]"
                        :disabled="submitting"
                    />
                </AlertModal>
                
                <!-- Global Toaster -->
                <Toaster />
            </div>
        </div>
    </AppLayout>
</template>