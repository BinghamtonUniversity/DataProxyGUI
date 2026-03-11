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
import { getCsrfToken } from '@/lib/utils';

// Use Laravel API routes instead of direct Django calls to avoid CORS
const apiBaseUrl = 'api';

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
const environmentFormRef = ref<InstanceType<typeof FormViewer> | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// Form configuration for environments
const formConfig = {
    label: 'Environment Form',
    description: '',
    name: "environment_form",
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
        warning('formatTimestamp: No timestamp provided:', timestamp || '');
        return '';
    }
    
    try {
        const date = new Date(timestamp);
        if (isNaN(date.getTime())) {
            warning('formatTimestamp: Invalid date:', timestamp || '');
            return '';
        }
        const formatted = date.toLocaleString();

        return formatted;
    } catch (error: any) {
        warning('formatTimestamp: Error formatting timestamp:', error.message || '');
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
// const getCsrfToken = () => {
//     const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
//     return token;
// };

// Fetch environments from API
const fetchEnvironments = async () => {
    try {
        loading.value = true;
        error.value = null;
 
        
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
    if (!environmentFormRef.value) {
        warning('Form is not ready. Please try again.', 'Validation Error');
        return;
    }
    const isValid = environmentFormRef.value?.validateForm();
    if (!isValid) {
        warning('Please fix validation errors before saving.', 'Validation Error');
        return;
    }
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
        
            
            // Add to local state with server-provided data and formatted timestamps
            const formattedNewEnv = {
                ...newEnv,
                // Use server-provided timestamps, not user input
                created_at: newEnv.created_at ? new Date(newEnv.created_at).toLocaleDateString() : new Date().toLocaleDateString(),
                updated_at: newEnv.updated_at ? new Date(newEnv.updated_at).toLocaleDateString() : new Date().toLocaleDateString()
            };
        
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
          
            
            // Update local state with server-provided data
            const index = environments.value.findIndex((env: any) => env.id === editingRow.value.id);
            if (index !== -1) {
                const formattedEnv = {
                    ...updatedEnv,
                    // Use server-provided timestamps, not user input
                    created_at: updatedEnv.created_at ? new Date(updatedEnv.created_at).toLocaleDateString() : new Date().toLocaleDateString(),
                    updated_at: updatedEnv.updated_at ? new Date(updatedEnv.updated_at).toLocaleDateString() : new Date().toLocaleDateString()
                };
            
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
        default:
            warning('Unknown action type:', actionData.action);
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
                :actions="[
                    { name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus' },
                    { name: 'edit', type: 'primary', min: 1, max: 1, label: 'Edit', icon: 'edit', loc: 'right' },
                    { name: 'delete', type: 'danger', min: 1, max: 1, label: 'Delete', icon: 'trash', loc: 'right' }
                ]"
                @actionHandler="handleDataGridActionHandler"
                         >
             </DataGrid>

            <!-- Modal for New/Edit Environment -->
            <AlertModal 
                :isOpen="showModal"
                :title="modalMode === 'new' ? 'Add New Environment' : 'Edit Environment'"
                @close="closeModal"
            >
            <FormViewer 
                      ref="environmentFormRef"
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
            
            <!-- Global Toaster -->
            <Toaster />
        </div>
    </AppLayout>
</template>
