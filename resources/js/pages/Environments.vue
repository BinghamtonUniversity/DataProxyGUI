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
        title: 'Environments',
        href: '/environments',
    },
];

// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);

// Data state
const environments = ref<any[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// Form configuration for environments
const formConfig = {
    label: '',
    description: '',
    name: "my-form",
    files: false,
    fields: [
        {
            name: "name",
            label: "Name",
            type: "text",
            placeholder: "Enter the name of the environment",
            value: "",
            help: "Name of the environment",
            info: "Name of the environment",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "domain",
            label: "Domain",
            type: "text",
            placeholder: "Enter the domain of the environment",
            value: "",
            help: "Domain of the environment",
            info: "Domain of the environment",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "type",
            label: "Type",
            type: "select",
            placeholder: "Select the type of the environment",
            value: "test",
            help: "Type of the environment",
            info: "Type of the environment",
            width: "12",
            offset: "0",
            options: [
                {
                    label: "Test",
                    value: "test"
                },
                {
                    label: "Dev",
                    value: "dev"
                },
                {
                    label: "Prod",
                    value: "prod"
                }
            ],
            multiple: false,
            show: true,
            edit: true,
            parse: true,
            required: true
        },
        {
            name: "created_at",
            label: "Created",
            type: "text",
            placeholder: "",
            value: "",
            help: "",
            info: "",
            width: "12",
            offset: "0",
            show: false,
            edit: false,
            parse: false,
            required: false
        },
        {
            name: "updated_at",
            label: "Updated",
            type: "text",
            placeholder: "",
            value: "",
            help: "",
            info: "",
            width: "12",
            offset: "0",
            show: false,
            edit: false,
            parse: false,
            required: false
        }
    ]
};

// Format timestamp for display
const formatTimestamp = (timestamp: string | null | undefined) => {
    if (!timestamp || timestamp === null || timestamp === undefined) {
        console.log('formatTimestamp: No timestamp provided:', timestamp);
        return '';
    }
    
    try {
        const date = new Date(timestamp);
        if (isNaN(date.getTime())) {
            console.log('formatTimestamp: Invalid date:', timestamp);
            return '';
        }
        const formatted = date.toLocaleString();
        console.log('formatTimestamp: Successfully formatted:', timestamp, '->', formatted);
        return formatted;
    } catch (error) {
        console.log('formatTimestamp: Error formatting timestamp:', timestamp, error);
        return timestamp;
    }
};

// Clean form data for API submission
const cleanFormData = (formData: any) => {
    const cleaned = { ...formData };
    
    // Remove server-managed fields that shouldn't be sent to API
    delete cleaned.created_at;
    delete cleaned.updated_at;
    delete cleaned.id; // Remove ID for new records
    
    // Remove empty strings and convert to null if needed
    Object.keys(cleaned).forEach(key => {
        if (cleaned[key] === '') {
            cleaned[key] = null;
        }
    });
    
    return cleaned;
};

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
};

// Fetch environments from API
const fetchEnvironments = async () => {
    try {
        loading.value = true;
        error.value = null;
        console.log(`${apiBaseUrl}/environments`);
        
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
        
        const data = await response.json();
        // Format timestamps for display
        environments.value = data.map((env: any) => ({
            ...env,
            created_at: formatTimestamp(env.created_at),
            updated_at: formatTimestamp(env.updated_at)
        }));
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch environments';
        showError('Failed to fetch environments. Please try again.', 'Error');
        console.error('Error fetching environments:', err);
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
        // Create a clean copy for editing, preserving original data
        editingRow.value = { 
            id: row.id,
            name: row.name,
            domain: row.domain,
            type: row.type
            // Don't include created_at, updated_at as they're server-managed
        };
        console.log('Opening edit modal with data:', editingRow.value);
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
            // Create new environment via API
            const cleanedData = cleanFormData(formValues);
            const response = await fetch(`${apiBaseUrl}/environments`, {
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

            const newEnv = await response.json();
            console.log('Server response for create:', newEnv);
            
            // Add to local state with server-provided data and formatted timestamps
            const formattedNewEnv = {
                ...newEnv,
                // Use server-provided timestamps, not user input
                created_at: formatTimestamp(newEnv.created_at),
                updated_at: formatTimestamp(newEnv.updated_at)
            };
            console.log('Formatted new environment:', formattedNewEnv);
            environments.value.push(formattedNewEnv);
            
            success(`Environment "${formValues.name}" added successfully!`, 'Environment Added');
        } else if (modalMode.value === 'edit' && editingRow.value) {
            // Update existing environment via API
            const cleanedData = cleanFormData(formValues);
            const response = await fetch(`${apiBaseUrl}/environments/${editingRow.value.id}`, {
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

            const updatedEnv = await response.json();
            console.log('Server response for edit:', updatedEnv);
            
            // Update local state with server-provided data
            const index = environments.value.findIndex((env: any) => env.id === editingRow.value.id);
            if (index !== -1) {
                const formattedEnv = {
                    ...updatedEnv,
                    // Use server-provided timestamps, not user input
                    created_at: formatTimestamp(updatedEnv.created_at),
                    updated_at: formatTimestamp(updatedEnv.updated_at)
                };
                console.log('Formatted environment for update:', formattedEnv);
                environments.value[index] = formattedEnv;
            }
            
            success(`Environment "${formValues.name}" updated successfully!`, 'Environment Updated');
        }
        closeModal();
    } catch (err: any) {
        showError(err.message || 'Failed to save environment. Please try again.', 'Error');
        console.error('Form submission error:', err);
    } finally {
        submitting.value = false;
    }
};

// Handle DataGrid action events
const handleAction = (actionData: { type: string; payload: any }) => {
    console.log('DataGrid action:', actionData);
    
    switch (actionData.type) {
        case 'single-edit':
            openEditModal(actionData.payload);
            break;
        case 'single-delete':
            handleDelete([actionData.payload.id || actionData.payload.name]);
            break;
        case 'view':
            // Handle view action if needed
            console.log('View environment:', actionData.payload);
            break;
        case 'duplicate':
            // Handle duplicate action if needed
            console.log('Duplicate environment:', actionData.payload);
            break;
        default:
            console.log('Unknown action type:', actionData.type);
    }
};

// Handle FormViewer action events
const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {
    console.log('FormViewer action:', actionData);
    
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
        const envsToDelete = environments.value.filter(env => selectedRowIds.includes(env.id));
        
        try {
            // Delete environments via API
            for (const env of envsToDelete) {
                const response = await fetch(`${apiBaseUrl}/environments/${env.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken() || '',
                    },
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `Failed to delete environment ${env.name}. Status: ${response.status}`);
                }
            }
            
            // Remove from local state after successful API calls
            envsToDelete.forEach(env => {
                const index = environments.value.findIndex(e => e.id === env.id);
                if (index !== -1) {
                    environments.value.splice(index, 1);
                }
            });
            
            // Show success message
            if (envsToDelete.length === 1) {
                success(`Environment "${envsToDelete[0].name}" deleted successfully!`, 'Environment Deleted');
            } else {
                success(`${envsToDelete.length} environments deleted successfully!`, 'Environments Deleted');
            }
        } catch (err: any) {
            showError(err.message || 'Failed to delete environments. Please try again.', 'Error');
            console.error('Delete error:', err);
        }
    } else {
        warning('Please select at least one environment to delete.', 'Selection Required');
    }
};

// Fetch data on component mount
onMounted(() => {
    fetchEnvironments();
});
</script>

<template>

    <Head title="Environments" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">


            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-300">Loading environments...</span>
            </div>

   

            <!-- DataGrid -->
            <DataGrid 
                v-else
                :schema="formConfig"
                :data="environments"
                theme="default"
                :showNew="true"
                :showEdit="true"
                :showDelete="true"
                :rowActions="[

                    { type: 'single-delete', label: 'Delete', icon: 'delete', colorClass: 'text-red-600 hover:bg-red-50' }
                ]"
                @create="openNewModal"
                @edit="openEditModal"
                @delete="handleDelete"
                @action="handleAction"
                         >
             </DataGrid>

            <!-- Modal for New/Edit Environment -->
            <AlertModal 
                :isOpen="showModal"
                :title="modalMode === 'new' ? 'Add New Environment' : 'Edit Environment'"
                @close="closeModal"
            >
                                                                   <FormViewer 
                      :formConfig="formConfig" 
                      :initialData="editingRow"
                      :cancelAction="'close'"
                      @submit="handleFormSubmit"
                      @action="handleFormAction"
                      :disabled="submitting"
                  />
                
                          
            </AlertModal>
            
            <!-- Global Toaster -->
            <Toaster />
        </div>
    </AppLayout>
</template>
