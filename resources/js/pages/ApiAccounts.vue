<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { ref, onMounted } from 'vue';
import TextField from '@/components/fields/TextField.vue';
import { getCsrfToken } from '@/lib/utils';


// Use Laravel API routes instead of direct Django calls to avoid CORS
const apiBaseUrl = 'api';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'API Accounts',
        href: '/api_accounts',
    },
];

// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);

// Comment dialog (required before create/update request)
const commentDialogOpen = ref(false);
const commentFormRef = ref<InstanceType<typeof FormViewer> | null>(null);
const commentForm = ref({ comment: '' });
const pendingAccountPayload = ref<Record<string, any> | null>(null);

const commentFormConfig = {
    label: '',
    description: '',
    name: 'api-account-comment-form',
    showLabel: false,
    files: false,
    fields: [
        {
            name: 'comment',
            label: 'Comment',
            type: 'textarea',
            placeholder: 'Describe why this change is being made',
            value: '',
            required: true,
        },
    ],
};

// Secret modal state
const showSecretModal = ref(false);
const decryptedSecret = ref('');
const secretLoading = ref(false);

// Delete confirmation dialog
const showDeleteModal = ref(false);
const pendingDeleteIds = ref<number[]>([]);
const deleting = ref(false);

// Data state
const users = ref<any[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const apiAccountFromRef = ref<InstanceType<typeof FormViewer> | null>(null);
// Toaster      
const { success, error: showError, warning, info } = useToaster();

// Form configuration for API users
const formConfig = ref({
    label: 'API Accounts',
    description: 'API Accounts are used to authenticate API requests. They are created by the system and can be used to authenticate API requests.',
    name: "api_users_form",
    files: false,
    fields: [
        {
            name: "app_name",
            label: "Name",
            type: "text",
            placeholder: "Enter the app name",
            value: "",
            help: "Unique name for the API application",
            info: "Unique identifier for the API application",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "api_secret",
            label: "Secret",
            type: "password",
            placeholder: "Enter the app secret",
            value: "",
            help: "Secret key for API authentication",
            info: "Secret key used for API authentication",
            width: "12",
            offset: "0",
            showColumn: false // Hide this column from the DataGrid display
        },
        {
            name: "environment_id",
            label: "Environment",
            type: "select",
            placeholder: "Select environment",
            value: "",
            help: "Environment where this API user is active",
            info: "Select the environment for this API user",
            width: "12",
            offset: "0",
            required: true,
            options: [] // Will be populated with available environments
        },
        { 
            name: 'ips', 
            label: 'IPs', 
            type: 'text',
            help: "Use the following fields to limit requests to one or more IP Addresses, or part (substring) of an IP Address. (Leave blank to allow from any IP)",
            array: {min: 0, max: 10},
            value: null,
            required: false,
            showColumn: true
        }
        // {
        //     name: "is_active",
        //     label: "Active",
        //     type: "checkbox",
        //     placeholder: "",
        //     value: true,
        //     help: "Whether the API user is active",
        //     info: "Whether the API user is currently active",
        //     width: "12",
        //     offset: "0",
        //     options: [
        //         { label: 'false', value: false, color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
        //         { label: 'true', value: true, color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }
        //     ],
        //     required: false
        // }
    ]
});

// Get CSRF token from meta tag
// const getCsrfToken = () => {
//     const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
//     return token;
// };

// Clean form data for API submission
const cleanFormData = (formData: any) => {
    const cleaned = { ...formData };
    cleaned.app_secret = cleaned.api_secret || null; // Map api_secret to app_secret for backend
    
    // Remove server-managed fields that shouldn't be sent to API
    delete cleaned.id; // Remove ID for new records
    delete cleaned.created_at; // Remove timestamp fields
    delete cleaned.updated_at;
    delete cleaned.encrypted_api_secret; // Don't send encrypted secret
    delete cleaned.api_type;
    delete cleaned.api_secret; // Remove api_secret as it's only used for form input, backend expects app_secret
    
    // Convert checkbox fields to proper booleans
    // const booleanFields = ['is_active'];
    // booleanFields.forEach(field => {
    //     if (cleaned[field] !== undefined && cleaned[field] !== null) {
    //         // Convert string 'true'/'false' or actual boolean to boolean
    //         cleaned[field] = cleaned[field] === true || cleaned[field] === 'true' || cleaned[field] === 1;
    //     }
    // });
     if (Array.isArray(cleaned.ips)) {
        cleaned.ips = cleaned.ips.filter((ip: string) => ip && ip.trim() !== '');
        if (cleaned.ips.length === 0) {
            delete cleaned.ips;
        }
    } else if (!cleaned.ips) {
        delete cleaned.ips;
    }
    
    // Remove empty strings and convert to null if needed
    Object.keys(cleaned).forEach(key => {
        if (cleaned[key] === '') {
            cleaned[key] = null;
        }
    });
    
    return cleaned;
};

// Fetch environments for the dropdown
const fetchEnvironments = async () => {
    try {
        const response = await fetch(`${apiBaseUrl}/environments`, {
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
        
        const environments = await response.json();
        
        // Update form config options for the environment dropdown
        formConfig.value.fields[2].options = environments.map((env: any) => ({
            label: env.name + ' (' + env.type + ') '  || `Environment ${env.id}`,
            value: env.id,
            color: env.type === 'test' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : env.type === 'dev' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
        }));
        
        return environments;
    } catch (err: any) {
        console.error('Error fetching environments:', err);
        return [];
    }
};

// Fetch API users from API
const fetchUsers = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`${apiBaseUrl}/api_users`, {
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
        users.value = data;
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch API users';
        showError('Failed to fetch API users. Please try again.', 'Error');
        console.error('Error fetching API users:', err);
    } finally {
        loading.value = false;
    }
};

// Modal functions
const openNewModal = () => {
    modalMode.value = 'new';
    // Let FormViewer use the default values from formConfig
    editingRow.value = null;
    pendingAccountPayload.value = null;
    commentDialogOpen.value = false;
    showModal.value = true;
};

const openEditModal = (row?: any) => {
    if (row) {
        modalMode.value = 'edit';
        // Create a clean copy for editing, preserving original data
        editingRow.value = { 
            id: row.id,
            app_name: row.app_name,
            api_secret: null,
            environment_id: row.environment_id,
            ips: row.ips || [],
            // is_active: row.is_active
        };
    
        pendingAccountPayload.value = null;
        commentDialogOpen.value = false;
        showModal.value = true;
    } else {
        warning('Please select exactly one row to edit.', 'Selection Required');
    }
};

const clearPendingAccountSave = () => {
    pendingAccountPayload.value = null;
    commentForm.value = { comment: '' };
};

const closeModal = () => {
    showModal.value = false;
    if (!commentDialogOpen.value) {
        editingRow.value = null;
        clearPendingAccountSave();
    }
};

const closeCommentDialog = () => {
    commentDialogOpen.value = false;
    editingRow.value = null;
    clearPendingAccountSave();
};

const closeSecretModal = () => {
    showSecretModal.value = false;
    decryptedSecret.value = '';
};

// Fetch decrypted secret from backend
const fetchDecryptedSecret = async (userId: number): Promise<string> => {

    try {

    const response = await fetch(`${apiBaseUrl}/api_users/${userId}/decrypted_secret`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken() || '',
        },
        credentials: 'same-origin'
    });

    if (!response.ok) {
        if (response.status === 403) {
            return 'Unauthorized';
        }
        throw new Error('Unauthorized to fetch decrypted secret');
    }

    const data = await response.json();
    return atob(data.api_secret) || 'No secret found';
    } catch (error: any) {
       
        throw new Error('Failed to fetch decrypted secret');
    }
};

const showUserSecret = async (user: any) => {
    if (user.id) {
        secretLoading.value = true;
        showSecretModal.value = true;

        try {
            const decrypted = await fetchDecryptedSecret(user.id);
            decryptedSecret.value = decrypted;
            if (decrypted === 'Unauthorized') {
                closeSecretModal();
                warning('You are not authorized to view this secret','Unauthorized');
            }
        } catch (error) {
            console.error('Failed to fetch secret:', error);
            decryptedSecret.value = 'Failed to fetch secret';
        } finally {
            secretLoading.value = false;
        }
    } else {
        warning('No user ID found.', 'No User');
    }
};

const copyToClipboard = async () => {
    try {
        await navigator.clipboard.writeText(decryptedSecret.value);
        success('Secret copied to clipboard!', 'Copied');
    } catch (err) {
        showError('Failed to copy to clipboard', 'Copy Error');
    }
};

const prepareAccountSave = (formValues: any) => {
    if (!apiAccountFromRef.value) {
        warning('Form is not ready. Please try again.', 'Validation Error');
        return;
    }
    const isValid = apiAccountFromRef.value?.validateForm();
    if (!isValid) {
        warning('Please fix validation errors before saving.', 'Validation Error');
        return;
    }

    if (modalMode.value === 'edit' && !editingRow.value) {
        warning('Please select exactly one row to edit.', 'Selection Required');
        return;
    }

    pendingAccountPayload.value = cleanFormData(formValues);
    commentForm.value = { comment: '' };
    commentDialogOpen.value = true;
    showModal.value = false;
};

const resolveEnvironmentId = (user: any, fallback?: any) => {
    if (user?.environment_id != null && typeof user.environment_id !== 'object') {
        return Number(user.environment_id);
    }
    if (user?.environment != null && typeof user.environment === 'object') {
        return user.environment.id != null ? Number(user.environment.id) : null;
    }
    if (user?.environment != null && typeof user.environment !== 'object') {
        return Number(user.environment);
    }
    if (fallback != null && typeof fallback !== 'object') {
        return Number(fallback);
    }
    return null;
};

const normalizeApiUser = (user: any, fallbackEnvironmentId?: any) => {
    const normalized = { ...user };
    normalized.environment_id = resolveEnvironmentId(normalized, fallbackEnvironmentId);
    return normalized;
};

const submitAccountWithComment = async (formData: any) => {
    if (!commentFormRef.value) {
        warning('Form is not ready. Please try again.', 'Validation Error');
        return;
    }
    const isValid = commentFormRef.value.validateForm();
    if (!isValid) {
        warning('Please enter a comment before saving.', 'Validation Error');
        return;
    }

    const comment = (formData?.comment ?? '').trim();
    if (!comment) {
        warning('Comment is required.', 'Validation Error');
        return;
    }

    if (!pendingAccountPayload.value) {
        showError('Nothing to save. Please try again.', 'Error');
        closeCommentDialog();
        return;
    }

    const cleanedData = {
        ...pendingAccountPayload.value,
        comment,
    };

    try {
        submitting.value = true;

        if (modalMode.value === 'new') {
            const response = await fetch(`${apiBaseUrl}/api_users`, {
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
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const newUser = await response.json();
            users.value.push(normalizeApiUser(newUser, cleanedData.environment_id));

            success(`API User "${cleanedData.app_name}" added successfully!`, 'API User Added');
        } else if (modalMode.value === 'edit' && editingRow.value) {
            const response = await fetch(`${apiBaseUrl}/api_users/${editingRow.value.id}`, {
                method: 'PUT',
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
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const updatedUser = await response.json();

            const index = users.value.findIndex((user: any) => user.id === editingRow.value.id);
            if (index !== -1) {
                users.value[index] = normalizeApiUser(
                    updatedUser,
                    cleanedData.environment_id ?? editingRow.value.environment_id
                );
            }

            success(`API User "${cleanedData.app_name}" updated successfully!`, 'API User Updated');
        }

        commentDialogOpen.value = false;
        editingRow.value = null;
        clearPendingAccountSave();
    } catch (err: any) {
        showError(err.message || 'Failed to save user. Please try again.', 'Error');
        console.error('Form submission error:', err);
    } finally {
        submitting.value = false;
    }
};

const handleCommentFormAction = (actionData: { type: string; action: string; formData: any }) => {
    switch (actionData.type) {
        case 'close':
        case 'cancel':
            closeCommentDialog();
            break;
        case 'save':
            submitAccountWithComment(actionData.formData);
            break;
        default:
            warning('Unknown FormViewer action type:', actionData.type);
    }
};

const handleClick = (row: any) => {
  
    info(`User "${row.app_name}" clicked!`, 'User Clicked');
};
// Handle DataGrid action events
const handleAction = (actionData: { type: string; payload: any }) => {
 
    
    switch (actionData.type) {
        case 'single-edit':
            openEditModal(actionData.payload);
            break;
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
            prepareAccountSave(actionData.formData);
            break;
        default:
            warning('Unknown FormViewer action type:', actionData.type);
    }
};

const handleDelete = (selectedRowIds?: number[]) => {
    if (!selectedRowIds || selectedRowIds.length === 0) {
        warning('Please select at least one user to delete.', 'Selection Required');
        return;
    }
    pendingDeleteIds.value = [...selectedRowIds];
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (deleting.value) return;
    showDeleteModal.value = false;
    pendingDeleteIds.value = [];
};

const confirmDelete = async () => {
    const selectedRowIds = pendingDeleteIds.value;
    if (!selectedRowIds.length) {
        closeDeleteModal();
        return;
    }

    const usersToDelete = users.value.filter(user => selectedRowIds.includes(user.id));
    deleting.value = true;
    try {
        for (const user of usersToDelete) {
            const response = await fetch(`${apiBaseUrl}/api_users/${user.id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `Failed to delete API user ${user.app_name}. Status: ${response.status}`);
            }
        }

        usersToDelete.forEach(user => {
            const index = users.value.findIndex(u => u.id === user.id);
            if (index !== -1) {
                users.value.splice(index, 1);
            }
        });

        if (usersToDelete.length === 1) {
            success(`API User "${usersToDelete[0].app_name}" deleted successfully!`, 'API User Deleted');
        } else {
            success(`${usersToDelete.length} API users deleted successfully!`, 'API Users Deleted');
        }

        showDeleteModal.value = false;
        pendingDeleteIds.value = [];
    } catch (err: any) {
        showError(err.message || 'Failed to delete users. Please try again.', 'Error');
        console.error('Delete error:', err);
    } finally {
        deleting.value = false;
    }
};

// Handle custom actions from DataGrid
const handleCustomAction = (actionData: { type: string; action: string; selectedRows: any[]; selectedData: any[] ,selectedIndex: any[]}) => {

    
    switch (actionData.action) {
        case 'create':
            openNewModal();
            break;
        case 'edit':
            // Export functionality
            openEditModal(actionData.selectedData[0]);
            break;
        case 'delete':
            // Handle bulk delete
            handleDelete([actionData.selectedData[0].id]);

            break;
        case 'show_secret':
            // Show decrypted secret for selected users
            if (actionData.selectedData.length > 0) {
                showUserSecret(actionData.selectedData[0]);
            } else {
                warning('Please select a user to show their secret.', 'Selection Required');
            }
            break;
        default:
            info(`Please implement the ${actionData.action} function`, 'Action Not Implemented');
    }
};

// Fetch data on component mount
onMounted(async () => {
    await fetchEnvironments(); // Load environments first for dropdown
    await fetchUsers();
});
</script>

<template>
    <Head title="API Accounts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-300">Loading API users...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex justify-center items-center py-12">
                <div class="text-red-600 dark:text-red-400">
                    <p class="text-lg font-semibold">Error loading API users</p>
                    <p class="text-sm">{{ error }}</p>
                    <button @click="fetchUsers" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Try Again
                    </button>
                </div>
            </div>

            <!-- DataGrid -->
            <DataGrid 
                v-else
                :schema="formConfig"
                :data="users"
                :filter="true"
                :actions="[
                    {name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus'},
                    '|',
                    {name: 'show_secret', type: 'info', min: 1,max:1, label: 'Show Secret', icon: 'eye', loc: 'left'},
                    '|',
                    {name: 'edit', type: 'primary', min: 1, max: 1, label: 'Edit', icon: 'edit', loc: 'right'},
                    '|',
                    {name: 'delete', type: 'danger', min: 1, max: 1, label: 'Delete', icon: 'trash', loc: 'right'}
                ]"
                :rowActions="[
                    { type: 'single-edit', label: 'Edit', icon: 'edit', colorClass: 'text-blue-600 hover:bg-blue-50' },
                    { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50' }
                ]"
                @actionHandler="handleCustomAction"
                @rowActionHandler="handleAction"
                
            >
            </DataGrid>

            <!-- Modal for New/Edit API User -->
            <AlertModal 
                :isOpen="showModal"
                :title="modalMode === 'new' ? 'New' : 'Edit'"
                @close="closeModal"
            >
                <FormViewer 
                    ref="apiAccountFromRef"
                    :formConfig="formConfig" 
                    :initialData="editingRow"
                    :cancelAction="'close'"
                    :actionHandler="handleFormAction"
                    :isSubmitting="submitting"
                    :actions="[
                        { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                        { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                    ]"
                />
            </AlertModal>

            <!-- Required comment before create/update request -->
            <AlertModal
                :isOpen="commentDialogOpen"
                title="Save comment"
                @close="() => { if (!submitting) closeCommentDialog() }"
            >
                <FormViewer
                    ref="commentFormRef"
                    :formConfig="commentFormConfig"
                    :initialData="commentForm"
                    :cancelAction="'close'"
                    :actionHandler="handleCommentFormAction"
                    :isSubmitting="submitting"
                    :actions="[
                        { type: 'save', action: 'save', label: 'Confirm', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                        { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                    ]"
                    :disabled="submitting"
                />
            </AlertModal>

            <!-- Delete confirmation -->
            <ConfirmDeleteModal
                :isOpen="showDeleteModal"
                :count="pendingDeleteIds.length"
                :deleting="deleting"
                @confirm="confirmDelete"
                @close="closeDeleteModal"
            />

            <!-- Modal for showing decrypted secret -->
            <AlertModal 
                :isOpen="showSecretModal"
                title="Decrypted App Secret"
                width="sm:max-w-2xl"
                @close="closeSecretModal"
            >
                <div class="p-6">
                    <div v-if="secretLoading" class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3 text-gray-600 dark:text-gray-300">Decrypting secret...</span>
                    </div>
                    <div v-else>
                        <div class="mb-4">
                            <div class="relative">
                               
                                <TextField
                                    name="decrypted_secret"
                                    :value="decryptedSecret"
                                    :disabled="true"
                                    :edit="false"
                                />
                                <button 
                                    @click="copyToClipboard"
                                    class="absolute top-2 right-2 p-1 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                                    title="Copy to clipboard"
                                >
                                    <font-awesome-icon icon="copy" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button 
                                @click="closeSecretModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </AlertModal>
            
            <!-- Global Toaster -->
            <Toaster />
        </div>
    </AppLayout>
</template>
