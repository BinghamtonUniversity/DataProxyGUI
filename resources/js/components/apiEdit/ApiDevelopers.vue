<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { type ApiData } from '@/types';
import { getCsrfToken } from '@/lib/utils';

interface Props {
    api_id: string
    server_slug: string
    // api_type: string
    apiData: ApiData | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
}

const props = defineProps<Props>()

// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);

// Data state
const allUsers = ref<any>([]);
const apiDevelopers = ref<any[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// DataGrid configuration - only display fields
const dataGridConfig = {
    label: 'API Developers',
    description: 'Manage developers assigned to this API.',
    name: "api-developers-datagrid",
    files: false,
    fields: [
        {
            name: "user_id",
            label: "ID",
            type: "text",
            placeholder: "Select a developer",
            value: "",
            help: "Choose a developer to assign to this API",
            info: "Select from available users with developer role",
            width: "12",
            offset: "0",
            required: true,
            show: false,
            edit: true,
            parse: true,
            showColumn: false,
        
        },
        {
            name: "developer_name",
            label: "Name",
            type: "text",
            placeholder: "Developer name",
            value: "",
            help: "Name of the assigned developer",
            info: "Developer assigned to this API",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: false,
            parse: false
        },
        {
            name: "unique_id",
            label: "Unique ID",
            type: "text",
            placeholder: "Unique ID",
            value: "",
            help: "Unique ID of the assigned developer",
            info: "Unique ID of the assigned developer",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: false,
            parse: false
        },
        {
            name: "developer_mail",
            label: "Email",
            type: "text",
            placeholder: "Developer email",
            value: "",
            help: "Email of the assigned developer",
            info: "Developer email address",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: false,
            parse: false
        },
        {
            name: "developer_username",
            label: "Username",
            type: "text",
            placeholder: "Developer username",
            value: "",
            help: "Username of the assigned developer",
            info: "Developer username",
            width: "12",
            offset: "0",
            required: false,
            show: true,
            edit: false,
            parse: false
        }
    ]
};

// Form configuration for modal - only form fields
const formConfig = {
    label: 'API Developers',
    description: 'Manage developers assigned to this API.',
    name: "api-developers-form",
    files: false,
    fields: [
        {
            name: "user_id",
            label: "Developer",
            type: "select",
            placeholder: "Select a developer",
            value: "",
            help: "Choose a developer to assign to this API",
            info: "Select from available users with developer role",
            width: "12",
            offset: "0",
            required: true,
            options: [], // Will be populated with available developers
            multiple: false,
            show: true,
            edit: true,
            parse: true
        }
    ]
};

// Get CSRF token from meta tag
// const getCsrfToken = () => {
//     const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
//     return token;
// };

// Clean form data for API submission
const cleanFormData = (formData: any) => {
    const cleaned = { ...formData };
    
    // Remove server-managed fields that shouldn't be sent to API
    delete cleaned.id; // Remove ID for new records
    
    // Remove empty strings and convert to null if needed
    Object.keys(cleaned).forEach(key => {
        if (cleaned[key] === '') {
            cleaned[key] = null;
        }
    });
    cleaned.api_id = props.api_id; // Ensure API ID is included in the payload
    return cleaned;
};

// Fetch available users (users with developer role)
const fetchAvailableUsers = async () => {
    try {        
        const users = allUsers.value;
        // Filter users who are users

        // Get currently assigned user IDs
        const assignedUserIds = apiDevelopers.value.map(dev => dev.user_id);
        
        // Filter out users who are already assigned to this API
        const availableUsers = users.filter((dev: any) => !assignedUserIds.includes(dev.id));
        
        // Update form config options for the dropdown
        formConfig.fields[0].options = availableUsers.map((dev: any) => ({
            label: `${dev.name} (${dev.username || dev.email})`,
            value: dev.id
        }));
        
        return users; // Return all users for display purposes
    } catch (err: any) {
        console.error('Error fetching users:', err);
        return [];
    }
};


// Fetch all developers for display purposes (not filtered by availability)
const fetchAllUsers = async () => {
    try {
        const response = await fetch(`/${props.server_slug}/api/users`, {
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
        
        const users = await response.json();
        allUsers.value = users;
        return users;
    } catch (err: any) {
        console.error('Error fetching all users:', err);
        return [];
    }
};

// Add developer names to the data for display
const addDeveloperNames = async (developers: any[]) => {
    const users = await fetchAllUsers();

    return developers.map(item => {
        // Handle both field name formats from server
        const developerUserId = item.user_id || item.user
        const developer = users.find((dev: any) => dev.id == developerUserId);

        const result = {
            ...item,
            // Ensure we have the correct field names for the DataGrid
            user_id: developerUserId, 
            api_id: item.api_id || item.api,
            developer_name: developer ? developer.name : `Developer ID: ${developerUserId}`,
            developer_mail: developer ? developer.email : '',
            developer_username: developer ? developer.username : '',
            unique_id: developer ? developer.unique_id : ''
        };

        return result;
    });
};

// Fetch API developers from API
const fetchApiDevelopers = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`/${props.server_slug}/api/apis/${props.api_id}/developers`, {
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
        // Add developer names for display
        apiDevelopers.value = await addDeveloperNames(data);
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch API developers';
        showError('Failed to fetch API developers. Please try again.', 'Error');
        console.error('Error fetching API developers:', err);
    } finally {
        loading.value = false;
    }
};

// Modal functions
const openNewModal = async () => {
    modalMode.value = 'new';
    // Fetch available developers before opening modal
    await fetchAvailableUsers();
    editingRow.value = null;
    showModal.value = true;
};

// Edit functionality removed - only create and delete are supported

const closeModal = () => {
    showModal.value = false;
    editingRow.value = null;
};

const handleFormSubmit = async (formValues: any) => {
    try {
        submitting.value = true;
        
        if (modalMode.value === 'new') {
            // Create new API developer assignment via API
            const cleanedData = cleanFormData(formValues);

            const response = await fetch(`/${props.server_slug}/api/apis/${props.api_id}/developers/${cleanedData.user_id}`, {
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

            const newApiDeveloper = await response.json();

            
            // Add developer name and add to local state
            const dataWithNames = await addDeveloperNames([newApiDeveloper]);
            apiDevelopers.value.push(dataWithNames[0]);
           
            // Refresh available developers for the dropdown
            await fetchAvailableUsers();
            
            success(`Developer assigned to API successfully!`, 'Developer Assigned');
        }
        closeModal();
    } catch (err: any) {
        showError(err.message || 'Failed to save API developer assignment. Please try again.', 'Error');
        console.error('Form submission error:', err);
    } finally {
        submitting.value = false;
    }
};

// Handle DataGrid action events
const handleAction = (actionData: { type: string; payload: any }) => {

    
    switch (actionData.type) {
        case 'single-delete':
            handleDelete([actionData.payload.id || actionData.payload.name]);
            break;
        case 'view':
            // Handle view action if needed

            break;
        case 'duplicate':
            // Handle duplicate action if needed

            break;
        default:
            warning('Unknown action type:', actionData.type);
    }
};

// Handle FormViewer action events
const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {

    
    switch (actionData.type) {
        case 'close':
        case 'cancel':
            closeModal();
            break;
        case 'save':
            handleFormSubmit(actionData.formData);
            break;
        default:
            warning('Unknown FormViewer action type:', actionData.type);
    }
};

const handleDelete = async (selectedRowIds?: number[]) => {
    if (selectedRowIds && selectedRowIds.length > 0) {
        const developersToDelete = apiDevelopers.value.filter(dev => selectedRowIds.includes(dev.id));
        
        try {
            // Delete API developer assignments via API
            for (const dev of developersToDelete) {
                const response = await fetch(`/${props.server_slug}/api/apis/${props.api_id}/developers/${dev.user_id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken() || '',
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Failed to remove developer assignment. Status: ${response.status}`);
                }
            }
            
            // Remove from local state after successful API calls
            developersToDelete.forEach(dev => {
                const index = apiDevelopers.value.findIndex(d => d.id === dev.id);
                if (index !== -1) {
                    apiDevelopers.value.splice(index, 1);
                }
            });
            
            // Refresh available developers for the dropdown
            await fetchAvailableUsers();
            
            // Show success message
            if (developersToDelete.length === 1) {
                success(`Developer assignment removed successfully!`, 'Assignment Removed');
            } else {
                success(`${developersToDelete.length} developer assignments removed successfully!`, 'Assignments Removed');
            }
        } catch (err: any) {
            showError(err.message || 'Failed to remove developer assignments. Please try again.', 'Error');
            console.error('Delete error:', err);
        }
    } else {
        warning('Please select at least one developer assignment to remove.', 'Selection Required');
    }
};

const handleDataGridActionHandler = (actionData: { action: string; selectedRows: any[]; selectedData: any[], selectedIndex: any[] }) => {
    switch (actionData.action) {
        case 'create':
            openNewModal();
            break;
        case 'delete':
            handleDelete([actionData.selectedData[0].id]);
            break;
    }
};

const handleDataGridRowActionHandler = (actionData: { type: string; payload: any }) => {
    switch (actionData.type) {
        case 'single-delete':
            handleDelete([actionData.payload.id || actionData.payload.name]);
            break;
    }
};
// Handle CSV upload
const handleCSVUpload = (uploadedData: any[]) => {
    // Here you can implement logic to process the uploaded CSV data
    // For example, you might want to validate the data or send it to the server
    alert(`CSV uploaded with ${uploadedData.length} rows. Check console for data.`);
};

// Fetch data on component mount
onMounted(() => {
    fetchApiDevelopers();
});
</script>

<template #default="{ apiData, loadingApiData, apiError, updateApiData }:
{
    apiData: ApiData | null, 
    loadingApiData: boolean, 
    apiError: string,
    updateApiData: (updatedApiData: ApiData) => void
}">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <div class="">
            
            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-300">Loading API developers...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex justify-center items-center py-12">
                <div class="text-red-600 dark:text-red-400">
                    <p class="text-lg font-semibold">Error loading API developers</p>
                    <p class="text-sm">{{ error }}</p>
                    <button @click="fetchApiDevelopers" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Try Again
                    </button>
                </div>
            </div>

            <!-- DataGrid -->
            <DataGrid 
                v-else
                :schema="dataGridConfig"
                :data="apiDevelopers"
                :count="25"
                :search="true"
                :filter="true"
                :upload="true"
                :download="true"
                :columns="true"
                theme="default"
                :showNew="true"
                :showEdit="false"
                :showDelete="true"
                :rowActionDropdown="false"
                :rowActionLabels="false"
                :rowActions="[
                    { type: 'single-delete', label: 'Remove', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50' }
                ]"
                :actions="[
                    { name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus' },
                    { name: 'delete', type: 'danger', min: 1, max: 1, label: 'Delete', icon: 'trash', loc: 'right' }
                ]"
                @actionHandler="handleDataGridActionHandler"                    
                @rowActionHandler="handleDataGridRowActionHandler"
                @upload="handleCSVUpload"
            >
            </DataGrid>

            <!-- Modal for New API Developer -->
            <AlertModal 
                :isOpen="showModal"
                title="Assign Developer to API"
                @close="closeModal"
            >
                <FormViewer 
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
            <!-- <Toaster /> -->
        </div>
    </div>
</template>
