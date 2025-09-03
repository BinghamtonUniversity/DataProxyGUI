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

const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL;

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
    label: 'Envrionments',
    description: 'A list of environments with their information.',
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
const formatTimestamp = (timestamp: string) => {
    if (!timestamp) return '';
    try {
        return new Date(timestamp).toLocaleString();
    } catch {
        return timestamp;
    }
};

// Clean form data for API submission
const cleanFormData = (formData: any) => {
    const cleaned = { ...formData };
    
    // Remove empty strings and convert to null if needed
    Object.keys(cleaned).forEach(key => {
        if (cleaned[key] === '') {
            cleaned[key] = null;
        }
    });
    
    return cleaned;
};

// Fetch environments from API
const fetchEnvironments = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`${djangoBaseUrl}/api/environments`);
        
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
        editingRow.value = { ...row };
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
            const response = await fetch(`${djangoBaseUrl}/api/environments`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(cleanedData)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const newEnv = await response.json();
            
            // Add to local state with formatted timestamps
            environments.value.push({
                ...newEnv,
                created_at: formatTimestamp(newEnv.created_at),
                updated_at: formatTimestamp(newEnv.updated_at)
            });
            
            success(`Environment "${formValues.name}" added successfully!`, 'Environment Added');
        } else if (modalMode.value === 'edit' && editingRow.value) {
            // Update existing environment via API
            const cleanedData = cleanFormData(formValues);
            const response = await fetch(`${djangoBaseUrl}/api/environments/${editingRow.value.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(cleanedData)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const updatedEnv = await response.json();
            
            // Update local state
            const index = environments.value.findIndex((env: any) => env.id === editingRow.value.id);
            if (index !== -1) {
                environments.value[index] = {
                    ...updatedEnv,
                    created_at: formatTimestamp(updatedEnv.created_at),
                    updated_at: formatTimestamp(updatedEnv.updated_at)
                };
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

const handleDelete = async (selectedRowIds?: number[]) => {
    if (selectedRowIds && selectedRowIds.length > 0) {
        const envsToDelete = environments.value.filter(env => selectedRowIds.includes(env.id));
        
        try {
            // Delete environments via API
            for (const env of envsToDelete) {
                const response = await fetch(`${djangoBaseUrl}/api/environments?id=${env.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                    }
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
                :formConfig="formConfig"
                :formData="environments"
                theme="default"
                :showNew="true"
                :showEdit="true"
                :showDelete="true"
                @create="openNewModal"
                @edit="openEditModal"
                @delete="handleDelete"
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
                     @submit="handleFormSubmit"
                     :disabled="submitting"
                 />
                
                                 <template #footer>
                     <div class="flex justify-end space-x-3">
                         <button 
                             @click="closeModal"
                             :disabled="submitting"
                             class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors disabled:opacity-50"
                         >
                             Cancel
                         </button>
                         <div v-if="submitting" class="flex items-center px-4 py-2">
                             <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                             <span class="text-sm text-gray-600 dark:text-gray-300">Saving...</span>
                         </div>
                     </div>
                 </template>
            </AlertModal>
            
            <!-- Global Toaster -->
            <Toaster />
        </div>
    </AppLayout>
</template>
