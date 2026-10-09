<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { type BreadcrumbItem, type Api, ApiUser, User } from '@/types';
import { getCsrfToken } from '@/lib/utils';
import { useProxyServer } from '@/composables/useProxyServer';
import BottomSheet from '@/components/BottomSheet.vue';
import ApiDevelopers from '@/components/apiEdit/ApiDevelopers.vue';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'APIs',
    href: '/apis',
  },
]

const { serverApiType, serverSlug } = useProxyServer();


// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);
const submitting = ref(false);
const apiFormRef = ref<InstanceType<typeof FormViewer> | null>(null);

// Comment dialog (required before create/update request)
const commentDialogOpen = ref(false);
const commentFormRef = ref<InstanceType<typeof FormViewer> | null>(null);
const commentForm = ref({ comment: '' });
const pendingApiPayload = ref<Record<string, any> | null>(null);

const commentFormConfig = {
    label: '',
    description: '',
    name: 'api-comment-form',
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

// Delete confirmation dialog
const showDeleteModal = ref(false);
const pendingDeleteIds = ref<number[]>([]);
const deleting = ref(false);

// Data state
const apis = ref<Api[]>([]);
const users = ref<any[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// API Developers sheet
const showDevelopersSheet = ref(false);
const developersApi = ref<Api | null>(null);

const openDevelopersSheet = (row: Api) => {
    developersApi.value = row;
    showDevelopersSheet.value = true;
};

const closeDevelopersSheet = () => {
    showDevelopersSheet.value = false;
};

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
            parse: false,
            showColumn: false
        },
        {
            name: "user_name",
            label: "Lead Developer",
            type: "text",
            placeholder: "",
            value: "",
            help: "Lead developer name",
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
const formConfig = computed(() => ({
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
            required: true,
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
            name: "user_id",
            label: "Lead Developer",
            type: "combobox",
            placeholder: "Select lead developer",
            value: "",
            help: "Primary developer responsible for this API",
            info: "User assigned as lead developer for this API",
            width: "12",
            offset: "0",
            required: true,
            options: users.value.map((user: any) => ({
                label: user.name,
                value: user.id
            })),
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
            required: true,
            show: true,
            edit: true,
            parse: true
        }
    ]
}));


// Clean form data for API submission
const cleanFormData = (formData: any) => {
    const cleaned = { ...formData };
    
    // Remove server-managed fields that shouldn't be sent to API
    if (modalMode.value === 'new') {
        delete cleaned.id;
    }
    
    delete cleaned.user_name;

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
        apis.value = apiArray.map((api: any) => {
            const resolvedUserId = api.user_id ?? api.user;
            return {
                ...api,
                api_type: api.api_type || 'php', // Default to 'php' if api_type is missing 
                created_at: api.created_at ? new Date(api.created_at).toLocaleDateString() : '',
                user_id: resolvedUserId,
                user_name: (resolvedUserId ? users.value.find((user: User) => user.id === resolvedUserId)?.name : "Unknown" ) as any
            };
        }) as Api[];
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch APIs';
        // showError('Failed to fetch APIs. Please try again.', 'Error');
        console.error('Error fetching APIs:', err);
    } finally {
        loading.value = false;
    }
};

// Modal functions
const clearPendingApiSave = () => {
    pendingApiPayload.value = null;
    commentForm.value = { comment: '' };
};

const openNewModal = () => {
    modalMode.value = 'new';
    editingRow.value = {
        name: '',
        description: '',
        api_type: serverApiType.value || 'python',
        tags: ''
    };
    clearPendingApiSave();
    commentDialogOpen.value = false;
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
    clearPendingApiSave();
    commentDialogOpen.value = false;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    if (!commentDialogOpen.value) {
        editingRow.value = null;
        clearPendingApiSave();
    }
};

const closeCommentDialog = () => {
    commentDialogOpen.value = false;
    editingRow.value = null;
    clearPendingApiSave();
};

const prepareApiSave = (formValues: any) => {
    if (!apiFormRef.value) {
        warning('Form is not ready. Please try again.', 'Validation Error');
        return;
    }
    const isValid = apiFormRef.value?.validateForm();
    if (!isValid) {
        warning('Please fix validation errors before saving.', 'Validation Error');
        return;
    }

    if (modalMode.value === 'edit' && !editingRow.value) {
        warning('Please select exactly one row to edit.', 'Selection Required');
        return;
    }

    const cleanedData = cleanFormData(formValues);

    if (cleanedData.api_type === 'php' && cleanedData.name && cleanedData.name.includes(' ')) {
        showError('API name cannot contain spaces. Please use underscores or hyphens instead.', 'Validation Error');
        return;
    }

    pendingApiPayload.value = cleanedData;
    commentForm.value = { comment: '' };
    commentDialogOpen.value = true;
    showModal.value = false;
};

const submitApiWithComment = async (formData: any) => {
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

    if (!pendingApiPayload.value) {
        showError('Nothing to save. Please try again.', 'Error');
        closeCommentDialog();
        return;
    }

    const cleanedData = {
        ...pendingApiPayload.value,
        comment,
    };

    try {
        submitting.value = true;

        if (modalMode.value === 'new') {
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

            const resolvedUserId = newApi.user_id ?? newApi.user;
            apis.value.push({
                ...newApi,
                api_type: newApi.api_type || 'php',
                created_at: newApi.created_at ? new Date(newApi.created_at).toLocaleDateString() : new Date().toLocaleDateString(),
                user_name: resolvedUserId ? users.value.find((user: User) => user.id === resolvedUserId)?.name : "Unknown",
                user_id: resolvedUserId
            });

            success('API created successfully!', 'API Created');
        } else if (modalMode.value === 'edit' && editingRow.value) {
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

            const index = apis.value.findIndex(api => api.id === editingRow.value.id);
            if (index !== -1) {
                const resolvedUserId = updatedApi.user_id ?? updatedApi.user;
                apis.value[index] = {
                    ...updatedApi,
                    api_type: updatedApi.api_type || 'php',
                    created_at: updatedApi.created_at ? new Date(updatedApi.created_at).toLocaleDateString() : apis.value[index].created_at,
                    user_name: resolvedUserId ? users.value.find((user: User) => user.id === resolvedUserId)?.name : "Unknown",
                    user_id: resolvedUserId
                };
            }

            success('API updated successfully!', 'API Updated');
        }

        commentDialogOpen.value = false;
        editingRow.value = null;
        clearPendingApiSave();
    } catch (err: any) {
        showError(err.message || 'Failed to save API. Please try again.', 'Error');
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
            submitApiWithComment(actionData.formData);
            break;
        default:
            warning('Unknown FormViewer action type:', actionData.type);
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
        case 'developers':
            // Navigate to API routes page
            openDevelopersSheet(actionData.payload);
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
            if (!submitting.value) {
                prepareApiSave(actionData.formData);
            }
            break;
        default:
            warning('Unknown FormViewer action type:', actionData.type);
    }
};

const handleDelete = (selectedRowIds?: number[]) => {
    if (!selectedRowIds || selectedRowIds.length === 0) {
        warning('Please select at least one API to delete.', 'Selection Required');
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

    const apisToDelete = apis.value.filter(api => selectedRowIds.includes(api.id));
    deleting.value = true;
    try {
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

        apisToDelete.forEach(api => {
            const index = apis.value.findIndex(a => a.id === api.id);
            if (index !== -1) {
                apis.value.splice(index, 1);
            }
        });

        if (apisToDelete.length === 1) {
            success(`API "${apisToDelete[0].name}" deleted successfully!`, 'API Deleted');
        } else {
            success(`${apisToDelete.length} APIs deleted successfully!`, 'APIs Deleted');
        }

        showDeleteModal.value = false;
        pendingDeleteIds.value = [];
    } catch (err: any) {
        showError(err.message || 'Failed to delete APIs. Please try again.', 'Error');
        console.error('Delete error:', err);
    } finally {
        deleting.value = false;
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
                        { type: 'developers', label: 'Manage Developers', icon: 'user', colorClass: 'text-blue-600 hover:bg-blue-50' },
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
                        :disabled="submitting"
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

                <!-- API Developers Bottom Sheet -->
                <BottomSheet
                    :isOpen="showDevelopersSheet"
                    :title="developersApi ? `API Developers – ${developersApi.name}` : 'API Developers'"
                    maxHeight="85vh"
                    @close="closeDevelopersSheet"
                >
                    <ApiDevelopers
                        v-if="developersApi && serverSlug"
                        :key="developersApi.id"
                        :api_id="String(developersApi.id)"
                        :server_slug="serverSlug"
                        :apiData="null"
                        :loadingApiData="false"
                        apiError=""
                        :updateApiData="() => {}"
                        :refreshApiData="() => {}"
                    />
                </BottomSheet>

                <ConfirmDeleteModal
                    :isOpen="showDeleteModal"
                    :count="pendingDeleteIds.length"
                    :deleting="deleting"
                    @confirm="confirmDelete"
                    @close="closeDeleteModal"
                />
                
                <!-- Global Toaster -->
                <Toaster />
            </div>
        </div>
    </AppLayout>
</template>