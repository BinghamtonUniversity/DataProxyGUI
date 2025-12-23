<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { ref, onMounted } from 'vue';

// Use Laravel API routes instead of direct Django calls to avoid CORS
const apiBaseUrl = '/api';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);

// Data state
const users = ref<any[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// Form configuration for users
const formConfig = {
    label: 'Users',
    description: 'A list of users with their information.',
    name: "users-form",
    files: false,
    fields: [
        {
            name: "unique_id",
            label: "Unique ID",
            type: "text",
            placeholder: "Enter the unique ID",
            value: "",
            help: "Unique identifier for the user",
            info: "Unique identifier for the user",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "name",
            label: "Name",
            type: "text",
            placeholder: "Enter the user's name",
            value: "",
            help: "Full name of the user",
            info: "Full name of the user",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "username",
            label: "Username",
            type: "text",
            placeholder: "Enter the username",
            value: "",
            help: "Username for login",
            info: "Username for login",
            width: "12",
            offset: "0",
            required: false
        },
        {
            name: "email",
            label: "Email",
            type: "email",
            placeholder: "Enter the email address",
            value: "",
            help: "Email address of the user",
            info: "Email address of the user",
            width: "12",
            offset: "0",
            required: false
        },
        {
            name: "admin",
            label: "Admin",
            type: "checkbox",
            placeholder: "",
            options: [
                { label: 'false', value: false, color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
                { label: 'true', value: true, color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }
            ],
            value: false,
            help: "Whether the user is an admin",
            info: "Whether the user is an admin",
            width: "12",
            offset: "0",
            required: false
        },
        {
            name: "active",
            label: "Active",
            type: "checkbox",
            placeholder: "",
            value: true,
            options: [
                { label: 'false', value: false, color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
                { label: 'true', value: true, color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }
            ],
            help: "Whether the user is active",
            info: "Whether the user is active",
            width: "12",
            offset: "0",
            required: false
        },
        {
            name: "developer",
            label: "Developer",
            type: "checkbox",
            placeholder: "",
            value: false,
            options: [
                { label: 'false', value: false, color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
                { label: 'true', value: true, color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }
            ],
            help: "Whether the user is a developer",
            info: "Whether the user is a developer",
            width: "12",
            offset: "0",
            required: false
        }
    ]
};

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
};

// Clean form data for API submission
const cleanFormData = (formData: any) => {
    const cleaned = { ...formData };
    
    // Remove server-managed fields that shouldn't be sent to API
    delete cleaned.id; // Remove ID for new records
    
    // Convert checkbox fields to proper booleans
    const booleanFields = ['admin', 'active', 'developer'];
    booleanFields.forEach(field => {
        if (cleaned[field] !== undefined && cleaned[field] !== null) {
            // Convert string 'true'/'false' or actual boolean to boolean
            cleaned[field] = cleaned[field] === true || cleaned[field] === 'true' || cleaned[field] === 1;
        }
    });
    
    // Remove empty strings and convert to null if needed
    Object.keys(cleaned).forEach(key => {
        if (cleaned[key] === '') {
            cleaned[key] = null;
        }
    });
    
    return cleaned;
};

// Fetch users from API
const fetchUsers = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`${apiBaseUrl}/users`, {
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
        error.value = err.message || 'Failed to fetch users';
        showError('Failed to fetch users. Please try again.', 'Error');
        console.error('Error fetching users:', err);
    } finally {
        loading.value = false;
    }
};

// Modal functions
const openNewModal = () => {
    modalMode.value = 'new';
    // Let FormViewer use the default values from formConfig
    editingRow.value = null;
    showModal.value = true;
};

const openEditModal = (row?: any) => {
    if (row) {
        modalMode.value = 'edit';

        // admin: row.admin == true ? "true" : "false",
        //     active: row.active == true ? "true" : "false",
        //     developer: row.developer == true ? "true" : "false"
        // Create a clean copy for editing, preserving original data
        editingRow.value = { 
            id: row.id,
            unique_id: row.unique_id,
            name: row.name,
            username: row.username,
            email: row.email,
            admin: row.admin,
            active: row.active,
            developer: row.developer
        };
     
        showModal.value = true;
    } else {
        warning('Please select exactly one row to edit.', 'Selection Required');
    }
};

const closeModal = () => {
    showModal.value = false;
    editingRow.value = null;
};

const handleFormSubmit = async (formValues: any) => {
    try {
        submitting.value = true;
        
        if (modalMode.value === 'new') {
            // Create new user via API
            const cleanedData = cleanFormData(formValues);
            const response = await fetch(`${apiBaseUrl}/users`, {
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
      
            
            // Add to local state with server-provided data
            users.value.push(newUser);
            
            success(`User "${formValues.name}" added successfully!`, 'User Added');
        } else if (modalMode.value === 'edit' && editingRow.value) {
            // Update existing user via API
            const cleanedData = cleanFormData(formValues);
            const response = await fetch(`${apiBaseUrl}/users/${editingRow.value.id}`, {
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
          
            
            // Update local state with server-provided data
            const index = users.value.findIndex((user: any) => user.id === editingRow.value.id);
            if (index !== -1) {
                users.value[index] = updatedUser;
            }
            
            success(`User "${formValues.name}" updated successfully!`, 'User Updated');
        }
        closeModal();
    } catch (err: any) {
        showError(err.message || 'Failed to save user. Please try again.', 'Error');
        console.error('Form submission error:', err);
    } finally {
        submitting.value = false;
    }
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
            console.log('Unknown action type:', actionData.type);
    }
};

// Handle FormViewer action events
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
        const usersToDelete = users.value.filter(user => selectedRowIds.includes(user.id));
        
        try {
            // Delete users via API
            for (const user of usersToDelete) {
                const response = await fetch(`${apiBaseUrl}/users/${user.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken() || '',
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Failed to delete user ${user.name}. Status: ${response.status}`);
                }
            }
            
            // Remove from local state after successful API calls
            usersToDelete.forEach(user => {
                const index = users.value.findIndex(u => u.id === user.id);
                if (index !== -1) {
                    users.value.splice(index, 1);
                }
            });
            
            // Show success message
            if (usersToDelete.length === 1) {
                success(`User "${usersToDelete[0].name}" deleted successfully!`, 'User Deleted');
            } else {
                success(`${usersToDelete.length} users deleted successfully!`, 'Users Deleted');
            }
        } catch (err: any) {
            showError(err.message || 'Failed to delete users. Please try again.', 'Error');
            console.error('Delete error:', err);
        }
    } else {
        warning('Please select at least one user to delete.', 'Selection Required');
    }
};

// Fetch data on component mount
onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <Head title="Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">

            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-300">Loading users...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex justify-center items-center py-12">
                <div class="text-red-600 dark:text-red-400">
                    <p class="text-lg font-semibold">Error loading users</p>
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
                theme="default"
                :showNew="true"
                :showEdit="true"
                :showDelete="true"
                @create="openNewModal"
                @edit="openEditModal"
                @delete="handleDelete"
                @action="handleAction"
            >
            </DataGrid>

            <!-- Modal for New/Edit User -->
            <AlertModal 
                :isOpen="showModal"
                :title="modalMode === 'new' ? 'Add New User' : 'Edit User'"
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
    </AppLayout>
</template>
