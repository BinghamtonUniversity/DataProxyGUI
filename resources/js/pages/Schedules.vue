<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { ref, onMounted, computed } from 'vue';

// Use Laravel API routes instead of direct Django calls to avoid CORS
const apiBaseUrl = '/api';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schedules',
        href: '/schedules',
    },
];

// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);

// Data state
const schedules = ref<any[]>([]);
const loading = ref(false); //TODO: change to true
const error = ref<string | null>(null);
const apiInstances = ref<any[]>([]);
const environmentsData = ref<any[]>([]);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// Form configuration for schedules
const scheduleSchema = {
    label: 'Schedules',
    description: 'A list of schedules with their information.',
    name: "schedule-schema",
    files: false,
    fields: [
        {
            name: "name",
            label: "Name",
            type: "text",
            placeholder: "Enter the name of the schedule",
            value: "",
            help: "Name of the schedule",
            info: "Name of the schedule",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "cron",
            label: "Schedule",
            type: "text",
            placeholder: "Enter the cron job of the schedule",
            value: "",
            help: "cron job of the schedule",
            info: "cron job of the schedule",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "verb",
            label: "Verb",
            type: "select",
            placeholder: "Select the verb of the schedule",
            value: "",
            help: "verb of the schedule",
            info: "verb of the schedule",
            width: "12",
            offset: "0",
            required: true,
            options: [
                {
                    label: "GET",
                    value: "GET"
                },
                {
                    label: "POST",
                    value: "POST"
                },
                {
                    label: "PUT",
                    value: "PUT"
                },
                {
                    label: "DELETE",
                    value: "DELETE"
                },
                {
                    label: "PATCH",
                    value: "PATCH"
                }
            ]
        },
        {
            name: "api_instance_id",
            label: "Api Instance",
            type: "select",
            placeholder: "Enter the api instance id of the schedule",
            value: "",
            help: "api instance id of the schedule",
            info: "verb of the schedule",
            width: "12",
            offset: "0",
            options: [],
            required: true
        },
        {
            name: "route",
            label: "Route",
            type: "text",
            placeholder: "Enter the api instance id of the schedule",
            value: "",
            help: "api instance id of the schedule",
            info: "verb of the schedule",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "enabled",
            label: "Enabled",
            type: "checkbox",
            placeholder: "Enter the api instance id of the schedule",
            value: false,
            help: "Enabled of the schedule",
            info: "Enabled of the schedule",
            options: [
                {
                    label: "true",
                    value: true
                },
                {
                    label: "false",
                    value: false
                }
            ],
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "args",
            label: "Arguments",
            type: "text",
            placeholder: "Enter the arguments of the schedule",
            value: "",
            help: "arguments of the schedule",
            info: "arguments of the schedule",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "last_exec_cron",
            label: "Status",
            type: "text",
            placeholder: "Enter the last exec cron of the schedule",
            value: "",
            help: "last exec cron of the schedule",
            info: "last exec cron of the schedule",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "last_exec_start",
            label: "Last Exec Start",
            type: "text",
            placeholder: "Enter the last exec start of the schedule",
            value: "",
            help: "last exec start of the schedule",
            info: "last exec start of the schedule",
            width: "12",
            offset: "0",
            required: true,
            showColumn: false
        },
        {
            name: "last_exec_stop",
            label: "Last Exec Stop",
            type: "text",
            placeholder: "Enter the last exec stop of the schedule",
            value: "",
            help: "last exec stop of the schedule",
            info: "last exec stop of the schedule",
            width: "12",
            offset: "0",
            required: true,
            showColumn: false
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
            required: false,
            showColumn: false
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
            required: false,
            showColumn: false
        }
    ]
};

// Form configuration for environments
const formConfig = {
    label: 'Schedules',
    description: 'A list of schedules with their information.',
    name: "schedule-form",
    files: false,
    fields: [
        {
            name: "name",
            label: "Name",
            type: "text",
            placeholder: "Enter the name of the schedule",
            value: "",
            help: "Name of the schedule",
            info: "Name of the schedule",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "cron",
            label: "Schedule",
            type: "cron",
            placeholder: "Enter the cron job of the schedule",
            value: "",
            help: "cron job of the schedule",
            info: "cron job of the schedule",
            width: "12",
            offset: "0",
            required: true
        },
        {
            name: "verb",
            label: "Verb",
            type: "select",
            placeholder: "Select the verb of the schedule",
            value: "GET",
            help: "verb of the schedule",
            info: "verb of the schedule",
            width: "12",
            offset: "0",
            required: true,
            options: [
                {
                    label: "GET",
                    value: "GET"
                },
                {
                    label: "POST",
                    value: "POST"
                },
                {
                    label: "PUT",
                    value: "PUT"
                },
                {
                    label: "DELETE",
                    value: "DELETE"
                },
                {
                    label: "PATCH",
                    value: "PATCH"
                }
            ]
        },
        {
            name: "api_instance_id",
            label: "Api Instance",
            type: "combobox",
            placeholder: "Select an API instance",
            value: "",
            help: "Select the API instance for this schedule",
            info: "API instance for the schedule",
            width: "12",
            offset: "0",
            options: [],
            required: true
        },
        {
            name: "route",
            label: "Route",
            type: "select",
            placeholder: "Select the route of the schedule",
            value: "",
            help: "api instance id of the schedule",
            info: "verb of the schedule",
            width: "12",
            offset: "0",
            required: true,
            options: [],
            show: [
                {
                    "op": "and",
                    "conditions": [
                        {
                            "type": "requires",
                            "name": "api_instance_id"
                        }
                    ]
                }
            ]
        },
        {
            name: "enabled",
            label: "Enabled",
            type: "checkbox",
            placeholder: "Enter the api instance id of the schedule",
            value: "false",
            options: [
              
                {
                    label: "false",
                    value: "false"
                },
                {
                    label: "true",
                    value: "true"
                },
            ],
            width: "12",
            offset: "0",
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

// Fetch schedules from API
const fetchSchedules = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`/api/schedulers`, {
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
        schedules.value = data;
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch schedules';
        showError('Failed to fetch schedules. Please try again.', 'Error');
        console.error('Error fetching schedules:', err);
    } finally {
        loading.value = false;
    }
};

// Fetch API instances for the combobox
const fetchApiInstances = async () => {
    try {
        const response = await fetch(`/api/api_instances`, {
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
        const environmentsResponse = await fetch(`/api/environments`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        
        if (!environmentsResponse.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
         environmentsData.value = await environmentsResponse.json();
        
        const data = await response.json();
        apiInstances.value = data;

        formConfig.fields[3].options = apiInstances.value.map(instance => ({
            label: `${instance.name} (${environmentsData.value.find((environment: any) => environment.id === instance.environment_id)?.name || 'Unknown Environment'})`,
            value: instance.id
        }));
        
    } catch (err: any) {
        console.error('Error fetching API instances:', err);
        showError('Failed to fetch API instances. Please try again.', 'Error');
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
    // try {
    //     submitting.value = true;
        
    //     if (modalMode.value === 'new') {
    //         // Create new environment via API
    //         const cleanedData = cleanFormData(formValues);
    //         const response = await fetch(`${apiBaseUrl}/environments`, {
    //             method: 'POST',
    //             headers: {
    //                 'Content-Type': 'application/json',
    //                 'Accept': 'application/json',
    //                 'X-CSRF-TOKEN': getCsrfToken() || '',
    //             },
    //             credentials: 'same-origin',
    //             body: JSON.stringify(cleanedData)
    //         });

    //         if (!response.ok) {
    //             const errorData = await response.json().catch(() => ({}));
    //             throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    //         }

    //         const newEnv = await response.json();
    //         console.log('Server response for create:', newEnv);
            
    //         // Add to local state with server-provided data and formatted timestamps
    //         const formattedNewEnv = {
    //             ...newEnv,
    //             // Use server-provided timestamps, not user input
    //             created_at: formatTimestamp(newEnv.created_at),
    //             updated_at: formatTimestamp(newEnv.updated_at)
    //         };
    //         console.log('Formatted new environment:', formattedNewEnv);
    //         environments.value.push(formattedNewEnv);
            
    //         success(`Environment "${formValues.name}" added successfully!`, 'Environment Added');
    //     } else if (modalMode.value === 'edit' && editingRow.value) {
    //         // Update existing environment via API
    //         const cleanedData = cleanFormData(formValues);
    //         const response = await fetch(`${apiBaseUrl}/environments/${editingRow.value.id}`, {
    //             method: 'PUT',
    //             headers: {
    //                 'Content-Type': 'application/json',
    //                 'Accept': 'application/json',
    //                 'X-CSRF-TOKEN': getCsrfToken() || '',
    //             },
    //             credentials: 'same-origin',
    //             body: JSON.stringify(cleanedData)
    //         });

    //         if (!response.ok) {
    //             const errorData = await response.json().catch(() => ({}));
    //             throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
    //         }

    //         const updatedEnv = await response.json();
    //         console.log('Server response for edit:', updatedEnv);
            
    //         // Update local state with server-provided data
    //         const index = environments.value.findIndex((env: any) => env.id === editingRow.value.id);
    //         if (index !== -1) {
    //             const formattedEnv = {
    //                 ...updatedEnv,
    //                 // Use server-provided timestamps, not user input
    //                 created_at: formatTimestamp(updatedEnv.created_at),
    //                 updated_at: formatTimestamp(updatedEnv.updated_at)
    //             };
    //             console.log('Formatted environment for update:', formattedEnv);
    //             environments.value[index] = formattedEnv;
    //         }
            
    //         success(`Environment "${formValues.name}" updated successfully!`, 'Environment Updated');
    //     }
    //     closeModal();
    // } catch (err: any) {
    //     showError(err.message || 'Failed to save environment. Please try again.', 'Error');
    //     console.error('Form submission error:', err);
    // } finally {
    //     submitting.value = false;
    // }
};

// Handle DataGrid action events
const handleAction = (actionData: { type: string; payload: any }) => {
    // console.log('DataGrid action:', actionData);
    
    // switch (actionData.type) {
    //     case 'single-edit':
    //         openEditModal(actionData.payload);
    //         break;
    //     case 'single-delete':
    //         handleDelete([actionData.payload.id || actionData.payload.name]);
    //         break;
    //     case 'view':
    //         // Handle view action if needed
    //         console.log('View environment:', actionData.payload);
    //         break;
    //     case 'duplicate':
    //         // Handle duplicate action if needed
    //         console.log('Duplicate environment:', actionData.payload);
    //         break;
    //     default:
    //         console.log('Unknown action type:', actionData.type);
    // }
};

// Handle FormViewer action events
const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {
    // console.log('FormViewer action:', actionData);
    
    // switch (actionData.type) {
    //     case 'close':
    //         closeModal();
    //         break;
    //     default:
    //         console.log('Unknown FormViewer action type:', actionData.type);
    // }
};

const handleDelete = async (selectedRowIds?: number[]) => {
    // if (selectedRowIds && selectedRowIds.length > 0) {
    //     const envsToDelete = environments.value.filter(env => selectedRowIds.includes(env.id));
        
    //     try {
    //         // Delete environments via API
    //         for (const env of envsToDelete) {
    //             const response = await fetch(`${apiBaseUrl}/environments/${env.id}`, {
    //                 method: 'DELETE',
    //                 headers: {
    //                     'Accept': 'application/json',
    //                     'X-CSRF-TOKEN': getCsrfToken() || '',
    //                 },
    //                 credentials: 'same-origin'
    //             });

    //             if (!response.ok) {
    //                 const errorData = await response.json().catch(() => ({}));
    //                 throw new Error(errorData.message || `Failed to delete environment ${env.name}. Status: ${response.status}`);
    //             }
    //         }
            
    //         // Remove from local state after successful API calls
    //         envsToDelete.forEach(env => {
    //             const index = environments.value.findIndex(e => e.id === env.id);
    //             if (index !== -1) {
    //                 environments.value.splice(index, 1);
    //             }
    //         });
            
    //         // Show success message
    //         if (envsToDelete.length === 1) {
    //             success(`Environment "${envsToDelete[0].name}" deleted successfully!`, 'Environment Deleted');
    //         } else {
    //             success(`${envsToDelete.length} environments deleted successfully!`, 'Environments Deleted');
    //         }
    //     } catch (err: any) {
    //         showError(err.message || 'Failed to delete environments. Please try again.', 'Error');
    //         console.error('Delete error:', err);
    //     }
    // } else {
    //     warning('Please select at least one environment to delete.', 'Selection Required');
    // }
};
const handleFormDataUpdate = (data: any) => {
    if (data.api_instance_id) {
        formConfig.fields[4].options = apiInstances.value.find(instance => instance.id === data.api_instance_id)?.route_user_map.map((route: any) => ({
            label: `${route.route}`,
            value: route.route
        }));
    }
};
// Fetch data on component mount
onMounted(async () => {
    await Promise.all([
        fetchSchedules(),
        fetchApiInstances()
    ]);
});
</script>

<template>

    <Head title="Schedules" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">


            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-300">Loading schedules...</span>
            </div>

   

            <!-- DataGrid -->
            <DataGrid 
                v-else
                :schema="scheduleSchema"
                :data="schedules"
                theme="default"
                :showNew="true"
                :showEdit="true"
                :showDelete="true"
                :rowActions="[
                    { type: 'view', label: 'View', icon: 'eye', colorClass: 'text-green-600 hover:bg-green-50' },
                    { type: 'single-edit', label: 'Edit', icon: 'edit', colorClass: 'text-blue-600 hover:bg-blue-50' },
                    { type: 'single-delete', label: 'Delete', icon: 'delete', colorClass: 'text-red-600 hover:bg-red-50' }
                ]"
                @create="openNewModal"
                @edit="openEditModal"
                @delete="handleDelete"
                @action="handleAction"
                         >
             </DataGrid>

            <!-- Modal for New/Edit Schedule -->
            <AlertModal 
                :isOpen="showModal"
                :title="modalMode === 'new' ? 'Add New Schedule' : 'Edit Schedule'"
                @close="closeModal"
            >
                                                                   <FormViewer 
                      :formConfig="formConfig" 
                      :initialData="editingRow"
                      :cancelAction="'close'"
                      @submit="handleFormSubmit"
                      @action="handleFormAction"
                      @update:modelValue="handleFormDataUpdate"
                      :disabled="submitting"
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
