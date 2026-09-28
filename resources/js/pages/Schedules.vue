<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { ApiData, ApiInstance, type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { ref, onMounted, computed } from 'vue';
import { getCsrfToken, mapDjangoToApiData, mapPhpToApiData } from '@/lib/utils';
import { useProxyServer } from '@/composables/useProxyServer'



const { serverSlug, serverApiType } = useProxyServer();

// Use Laravel API routes instead of direct Django calls to avoid CORS
const apiBaseUrl = 'api';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schedules',
        href: '/schedules',
    },
];
function normalizeApiData(payload: any, backend: 'python' | 'php'): ApiData {
  return backend === 'python'
    ? mapDjangoToApiData(payload)
    : mapPhpToApiData(payload);
}
// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
let selectedInstanceId = ref<number | null>(null);
const editingRow = ref<any>(null);
const loadingRoutes = ref(false);
const scheduleSaving = ref(false);

// Delete confirmation dialog
const showDeleteModal = ref(false);
const pendingDeleteIds = ref<number[]>([]);
const deleting = ref(false);

// Comment dialog (required before create/update schedule request — not arguments)
const commentDialogOpen = ref(false);
const commentFormRef = ref<InstanceType<typeof FormViewer> | null>(null);
const commentForm = ref({ comment: '' });
const pendingSchedulePayload = ref<Record<string, any> | null>(null);

const commentFormConfig = {
    label: '',
    description: '',
    name: 'schedule-comment-form',
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

// Data state
const schedules = ref<any[]>([]);
const loading = ref(false); //TODO: change to true
const error = ref<string | null>(null);
const apiInstances = ref<any[]>([]);
const environmentsData = ref<any[]>([]);
const formRef = ref<InstanceType<typeof FormViewer> | null>(null);
const argumentsFormRef = ref<InstanceType<typeof FormViewer> | null>(null);
const showArgumentsModal = ref<boolean>(false);
const showReportModal = ref<boolean>(false);
// Avoid duplicate versions/latest fetches when Combobox emits select + blur
const lastFetchedApiInstanceId = ref<number | string | null>(null);
const routeFetchToken = ref(0);
// Toaster
const { success, error: showError, warning, info } = useToaster();

/** Build unique route select options from version_urls (dedupe by path). */
const uniqueRouteOptions = (versionUrls: any[] | null | undefined) => {
    const seen = new Set<string>();
    const options: { label: string; value: string }[] = [];
    for (const route of versionUrls || []) {
        const path = route?.path;
        if (path == null || path === '' || seen.has(path)) {
            continue;
        }
        seen.add(path);
        options.push({ label: `${path}`, value: path });
    }
    return options;
};

const argumentsFormConfig = computed(() => ({
    label: 'Arguments',
    description: 'A list of arguments with their information.',
    name: "arguments-form",
    files: false,
    fields: [
        {
            name: "args",
            label: "Args",
            type: "fieldset",
            array: {
                min: 0,

            },
            fields: [
                {
                    name: "name",
                    label: "Name",
                    type: "text",
                    placeholder: "Enter the name of the argument",
                    value: "",
                    width: "6",
                    offset: "0",
                    required: true
                },
                {
                    name: "value",
                    label: "Value",
                    type: "text",
                    placeholder: "Enter the value of the argument",
                    value: "",
                    width: "6",
                    required: true
                }
            ]
        }
    ]
}));

// Computed options for API instances in the schema
const apiInstanceOptions = computed(() => {
    return apiInstances.value.map(instance => ({
        label: `${instance.name} (${environmentsData.value.find((environment: any) => environment.id === instance.environment_id)?.name || 'Unknown Environment'})`,
        value: instance.id
    }));
});

// Form configuration for schedules
const scheduleSchema = computed(() => ({
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
                    value: "GET",
                    color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                },
                {
                    label: "POST",
                    value: "POST",
                    color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                },
                {
                    label: "PUT",
                    value: "PUT",
                    color: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
                },
                {
                    label: "DELETE",
                    value: "DELETE",
                    color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                },
                {
                    label: "PATCH",
                    value: "PATCH",
                    color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
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
            options: apiInstanceOptions.value,
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
                    value: true,
                    color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                },
                {
                    label: "false",
                    value: false,
                    color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
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
            required: true,
            isArrayObject: true,
            targetObjectAttribute: "name",
            targetColor: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200"
        },
        {
            name: "status",
            label: "Status",
            type: "text",
            placeholder: "Enter the status of the schedule",
            value: "",
            help: "status of the schedule",
            info: "status of the schedule",
            showColumn: true,
            template: "Last executed: {{#formatRelative}}{{last_exec_start}}{{/formatRelative}}\nRan for {{#formatDuration}}{{last_exec_start}}|{{last_exec_stop}}{{/formatDuration}}",
        },
        {
            name: "last_exec_cron",
            label: "Last Exec Cron",
            type: "text",
            placeholder: "Enter the last exec cron of the schedule",
            value: "",
            help: "last exec cron of the schedule",
            info: "last exec cron of the schedule",
            width: "12",
            offset: "0",
            required: true,
            showColumn: false,
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
            showColumn: false,

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
            showColumn: false,
 
        },
        {
            name: "last_response",
            label: "Last Response",
            type: "text",
            placeholder: "Enter the last response of the schedule",
            value: "",
            help: "last response of the schedule",
            info: "last response of the schedule",
            width: "12",
            offset: "0",
            required: true,
            showColumn: false,
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
}));

const reportFormConfig = computed(() => ({
    label: 'Report',
    description: '',
    name: "report-schema",
    files: false,
    fields: [
        {
            name: "status",
            label: "",
            type: "output",
            placeholder: "Enter the status of the report",
            value: "",
            help: "status of the report",
            info: "status of the report",
            template: "Last executed: {{#formatRelative}}{{last_exec_start}}{{/formatRelative}}\nRan for {{#formatDuration}}{{last_exec_start}}|{{last_exec_stop}}{{/formatDuration}}",
       
        },
        {
            name: "last_response",
            label: "Last Results:",
            type: "textarea",
            placeholder: "Enter the id of the report",
            value: "",

            info: "last response of the report",
            width: "12",
            offset: "0",
            edit: false,
            isObject: true,

        },
        {
            name: "next_runtimes",
            label: "Scheduled to run:",
            type: "textarea",
            placeholder: "Enter the next runtimes of the report",
            value: "",
            info: "next runtimes of the report",
            edit: false,
            rows: 5,
        }
    ]
}));

const pad2 = (n: number) => String(n).padStart(2, '0');

const formatAbsoluteDateTime = (timestamp: string | number | Date) => {
    const date = new Date(timestamp);
    if (Number.isNaN(date.getTime())) {
        return String(timestamp);
    }
    return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())} ${pad2(date.getHours())}:${pad2(date.getMinutes())}:${pad2(date.getSeconds())}`;
};

const formatFutureRelative = (timestamp: string | number | Date) => {
    const date = new Date(timestamp);
    if (Number.isNaN(date.getTime())) {
        return String(timestamp);
    }
    const sec = Math.floor((date.getTime() - Date.now()) / 1000);
    if (sec <= 0) {
        return 'now';
    }
    if (sec < 60) {
        return `in ${sec} second${sec === 1 ? '' : 's'}`;
    }
    if (sec < 3600) {
        const minutes = Math.floor(sec / 60);
        return `in ${minutes} minute${minutes === 1 ? '' : 's'}`;
    }
    if (sec < 86400) {
        const hours = Math.floor(sec / 3600);
        return `in ${hours} hour${hours === 1 ? '' : 's'}`;
    }
    const days = Math.floor(sec / 86400);
    if (days < 7) {
        return `in ${days} day${days === 1 ? '' : 's'}`;
    }
    if (days < 30) {
        const weeks = Math.floor(days / 7);
        return `in ${weeks} week${weeks === 1 ? '' : 's'}`;
    }
    if (days < 365) {
        const months = Math.floor(days / 30);
        return `in ${months} month${months === 1 ? '' : 's'}`;
    }
    const years = Math.floor(days / 365);
    return `in ${years} year${years === 1 ? '' : 's'}`;
};

const formatNextRuntimes = (runtimes: unknown): string => {
    if (runtimes === undefined || runtimes === null || runtimes === '') {
        return '';
    }

    let list: unknown[] = [];
    if (Array.isArray(runtimes)) {
        list = runtimes;
    } else if (typeof runtimes === 'string') {
        const trimmed = runtimes.trim();
        if (!trimmed) {
            return '';
        }
        try {
            const parsed = JSON.parse(trimmed);
            list = Array.isArray(parsed) ? parsed : [trimmed];
        } catch {
            list = trimmed.split(/\r?\n/).map((s) => s.trim()).filter(Boolean);
        }
    } else {
        list = [runtimes];
    }

    return list
        .filter((ts) => ts !== undefined && ts !== null && String(ts).trim() !== '')
        .map((ts) => `${formatFutureRelative(ts as string | number | Date)} (${formatAbsoluteDateTime(ts as string | number | Date)})`)
        .join('\n');
};

const reportInitialData = computed(() => {
    if (!editingRow.value) {
        return null;
    }
    return {
        ...editingRow.value,
        next_runtimes: formatNextRuntimes(editingRow.value.next_runtimes),
    };
});

// Form configuration for environments
const formConfig = computed(() => ({
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
            options: apiInstances.value.map(instance => ({
                label: `${instance.name} (${environmentsData.value.find((environment: any) => environment.id === instance.environment_id)?.name || 'Unknown Environment'})`,
                value: instance.id
            })),
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
            value: false,
            options: [
              
                {
                    label: "False",
                    value: false,
                },
                {
                    label: "True",
                    value: true
                },
            ],
            width: "12",
            offset: "0",
            required: false
        }
    ]
}));
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

// // Get CSRF token from meta tag
// const getCsrfToken = () => {
//     const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
//     return token;
// };

// Fetch schedules from API
const fetchSchedules = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`api/scheduler`, {
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
        // Ensure api_instance_id is a number for proper option matching
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
        const response = await fetch(`api/api_instances`, {
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
        const environmentsResponse = await fetch(`api/environments`, {
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

        
        formConfig.value.fields[3].options = apiInstances.value.map(instance => ({
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
    pendingSchedulePayload.value = null;
    commentDialogOpen.value = false;
    lastFetchedApiInstanceId.value = null;
    formConfig.value.fields[4].options = [];
    showModal.value = true;
};

const openEditModal = async (row?: any) => {

    if (row) {
        
        modalMode.value = 'edit';
        loadingRoutes.value = true;
        // Create a clean copy for editing, preserving original data
        editingRow.value = { 
            id: row.id,
            name: row.name,
            cron: row.cron,
            verb: row.verb,
            api_instance_id: row.api_instance_id,
            route: row.route,
            enabled: row.enabled
            // Don't include created_at, updated_at as they're server-managed
        };
        const apiVersion = await fetchAPIVersion(apiInstances.value.find(instance => instance.id === editingRow.value.api_instance_id));
        const normalizedApiVersion = normalizeApiData(apiVersion, serverApiType.value as 'python' | 'php');
        
        formConfig.value.fields[4].options = uniqueRouteOptions(normalizedApiVersion.version_urls);
        formConfig.value.fields[4].placeholder = 'Select a route';
        lastFetchedApiInstanceId.value = editingRow.value.api_instance_id;
        
        loadingRoutes.value = false;
        pendingSchedulePayload.value = null;
        commentDialogOpen.value = false;
        showModal.value = true;
    } else {
        warning('Please select exactly one row to edit.', 'Selection Required');
    }
};

const clearPendingScheduleSave = () => {
    pendingSchedulePayload.value = null;
    commentForm.value = { comment: '' };
};

const closeModal = () => {
    showModal.value = false;
    if (!commentDialogOpen.value) {
        editingRow.value = null;
        clearPendingScheduleSave();
    }
};

const closeCommentDialog = () => {
    commentDialogOpen.value = false;
    editingRow.value = null;
    clearPendingScheduleSave();
};

const prepareScheduleSave = (formValues: any) => {
    try {
        if (formRef.value) {
            const isValid = formRef.value.validateForm();
            if (!isValid) {
                warning('Please fix validation errors before saving.', 'Validation Error');
                return;
            }
        }
    } catch (err: any) {
        showError(err.message || 'Failed to save schedule. Please try again.', 'Error');
        console.error('Form submission error:', err);
        return;
    }

    if (modalMode.value === 'edit' && !editingRow.value) {
        warning('Please select exactly one row to edit.', 'Selection Required');
        return;
    }

    pendingSchedulePayload.value = { ...formValues };
    commentForm.value = { comment: '' };
    commentDialogOpen.value = true;
    showModal.value = false;
};

const submitScheduleWithComment = async (formData: any) => {
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

    if (!pendingSchedulePayload.value) {
        showError('Nothing to save. Please try again.', 'Error');
        closeCommentDialog();
        return;
    }

    const formValues: Record<string, any> = {
        ...pendingSchedulePayload.value,
        comment,
    };

    scheduleSaving.value = true;
    try {
        if (modalMode.value === 'new') {
            const response = await fetch(`${apiBaseUrl}/scheduler`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(formValues)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const newSchedule = await response.json();

            newSchedule.api_instance_id = newSchedule.api_instance != null ? Number(newSchedule.api_instance) : newSchedule.api_instance_id!=null ? Number(newSchedule.api_instance_id) : null;

            schedules.value.unshift(newSchedule);
            commentDialogOpen.value = false;
            editingRow.value = null;
            clearPendingScheduleSave();
            success(`Schedule "${formValues.name}" added successfully!`, 'Schedule Added');
        } else if (modalMode.value === 'edit' && editingRow.value) {
            const response = await fetch(`${apiBaseUrl}/scheduler/${editingRow.value.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(formValues)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }

            const updatedSchedule = await response.json();
            updatedSchedule.api_instance_id = updatedSchedule.api_instance_id!=null ? Number(updatedSchedule.api_instance_id) : null;

            const index = schedules.value.findIndex((schedule: any) => schedule.id === editingRow.value.id);
            if (index !== -1) {
                schedules.value[index] = updatedSchedule;
            }

            success(`Schedule "${formValues.name}" updated successfully!`, 'Schedule Updated');
            commentDialogOpen.value = false;
            editingRow.value = null;
            clearPendingScheduleSave();
        } else {
            warning('Please select exactly one row to edit.', 'Selection Required');
        }
    } catch (err: any) {
        showError(err.message || 'Failed to save schedule. Please try again.', 'Error');
        console.error('Form submission error:', err);
    } finally {
        scheduleSaving.value = false;
    }
};

const handleCommentFormAction = (action: { type: string; action: string; formData: any }) => {
    switch (action.type) {
        case 'close':
        case 'cancel':
            closeCommentDialog();
            break;
        case 'save':
            submitScheduleWithComment(action.formData);
            break;
        default:
            warning('Unknown FormViewer action type:', action.type);
    }
};

const handleDelete = (selectedRowIds?: number[]) => {
    if (!selectedRowIds || selectedRowIds.length === 0) {
        warning('Please select at least one schedule to delete.', 'Selection Required');
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

    deleting.value = true;
    try {
        for (const id of selectedRowIds) {
            const response = await fetch(`${apiBaseUrl}/scheduler/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin'
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
            }
            const index = schedules.value.findIndex((schedule: any) => schedule.id === id);
            if (index !== -1) {
                schedules.value.splice(index, 1);
            }
        }

        success(`${selectedRowIds.length} schedule(s) deleted successfully!`, 'Schedules Deleted');
        showDeleteModal.value = false;
        pendingDeleteIds.value = [];
    } catch (err: any) {
        showError(err.message || 'Failed to delete schedules. Please try again.', 'Error');
        console.error('Delete error:', err);
    } finally {
        deleting.value = false;
    }
};  
// Fetch api version details by selected api instance's version
const fetchAPIVersion = async (api_instance : ApiInstance)=>{
    let response;
    try {
        if (api_instance.api_version_id === null) {
         response = await fetch(`ajax/apis/${api_instance.api_id}/versions/latest`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
    } else {
         response = await fetch(`ajax/apis/versions/${api_instance.api_version_id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
    }
    if (!response || !response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();

        return data;
    } catch (err: any) {
        console.error('Error fetching latest version:', err);
}   
}

const handleArgumentsFormActionHandler = async (action: { type: string; action: string; formData: any }) => {

    switch (action.type) {
        case 'close':
        case 'cancel':
            showArgumentsModal.value = false;
            break;
        case 'save':
            await handleArgumentsFormSubmit(action.formData);
            break;
        default:
            warning('Unknown arguments form action type:', action.type);
    }
};

const handleArgumentsFormSubmit = async (formData: any) => {
    if (!argumentsFormRef.value) {
        warning('Form is not ready. Please try again.', 'Validation Error');
        return;
    }
    const isValid = argumentsFormRef.value.validateForm();
    if (!isValid) {
        warning('Please fix validation errors before saving.', 'Validation Error');
        return;
    }

    for (const arg of formData.args) {
        if (!arg.name || !arg.value) {
            warning('Please fill in all fields.', 'Validation Error');
            return;
        }
    }
 
    try {
        const response = await fetch(`${apiBaseUrl}/scheduler/${editingRow.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(formData)
        });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }
        const updatedSchedule = await response.json();
        updatedSchedule.api_instance_id =  updatedSchedule.api_instance_id!=null ? Number(updatedSchedule.api_instance_id) : null;

        
        const index = schedules.value.findIndex((schedule: any) => schedule.id === editingRow.value.id);
        if (index !== -1) {
            schedules.value[index] = updatedSchedule;
        }
        success(`Schedule "${editingRow.value.name}" updated successfully!`, 'Schedule Updated');
        closeArgumentsModal();
    } catch (err: any) {
        showError(err.message || 'Failed to save schedule. Please try again.', 'Error');
        console.error('Form submission error:', err);
    }
};
const closeArgumentsModal = () => {
    showArgumentsModal.value = false;
    editingRow.value = null;
};
const handleFormDataChange = async (data: any, field: string) => {
    if (field === 'api_instance_id') {
        const instanceId = data.api_instance_id ?? null;

        // Combobox often emits update:value twice (select + blur) for the same value
        if (instanceId === lastFetchedApiInstanceId.value) {
            return;
        }

        if (!instanceId) {
            lastFetchedApiInstanceId.value = null;
            formConfig.value.fields[4].options = [];
            return;
        }

        const token = ++routeFetchToken.value;
        lastFetchedApiInstanceId.value = instanceId;
        loadingRoutes.value = true;
        try {
            const instance = apiInstances.value.find(inst => inst.id === instanceId);
            if (!instance) {
                formConfig.value.fields[4].options = [];
                return;
            }

            let apiVersion = await fetchAPIVersion(instance);
            if (token !== routeFetchToken.value) {
                return;
            }

            apiVersion = normalizeApiData(apiVersion, serverApiType.value as 'python' | 'php');
            formConfig.value.fields[4].options = uniqueRouteOptions(apiVersion.version_urls);
        } catch (err: any) {
            if (token === routeFetchToken.value) {
                lastFetchedApiInstanceId.value = null;
                formConfig.value.fields[4].options = [];
                console.error('Error loading routes for API instance:', err);
            }
        } finally {
            if (token === routeFetchToken.value) {
                loadingRoutes.value = false;
            }
        }
    }
};

const handleDataGridActionHandler = (action: { action: string; selectedRows: any[]; selectedData: any[], selectedIndex: any[] }) => {
    switch (action.action) {
        case 'create':
            openNewModal();
            break;
        case 'edit':
            openEditModal(action.selectedData[0]);
            break;
        case 'arguments':
            openArgumentsModal(action.selectedData[0]);
            break;
        case 'manual_run':
            handleManualRun(action.selectedData[0].id);
            break;
        case 'view_report':
            openReportModal(action.selectedData[0]);
            break;
        case 'delete':
            handleDelete([action.selectedData[0].id]);
            break;
    }
};

const openReportModal = (row: any) => {
    showReportModal.value = true;
    editingRow.value = row;
};
const closeReportModal = () => {
    showReportModal.value = false;
    editingRow.value = null;
};

const handleReportFormActionHandler = (action: { type: string; action: string; formData: any }) => {
    switch (action.type) {
        case 'close':
        case 'cancel':
            closeReportModal();
            break;
    }
};

const handleManualRun = async (id: number) => {
    const response = await fetch(`${apiBaseUrl}/scheduler/${id}/run`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken() || '',
        },
    });
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        showError(errorData.details.error || `HTTP error! status: ${response.status}`, 'Error');
        throw new Error(errorData.details.error || `HTTP error! status: ${response.status}`);
    }
    const data = await response.json();
    success('Scheduler run successfully', 'Success');
 
    schedules.value.find((schedule: any) => schedule.id === id)!.last_response=JSON.stringify(data.data);

};


const openArgumentsModal = (row: any) => {
    showArgumentsModal.value = true;
    editingRow.value = row;
};

const handleDataGridRowActionHandler = (action: { type: string; payload: any }) => {
    switch (action.type) {
        case 'single-edit':
            openEditModal(action.payload    );
            break;
        case 'arguments':
            openArgumentsModal(action.payload);
            break;
        case 'single-delete':
            handleDelete([action.payload.id]);
            break;
    }
};

const handleDataGridRowClick = (row: any) => {
    openEditModal(row);
    selectedInstanceId.value = row.api_instance_id;
    formConfig.value.fields[3].options = apiInstances.value.map(instance => ({
        label: `${instance.name} (${environmentsData.value.find((environment: any) => environment.id === instance.environment_id)?.name || 'Unknown Environment'})`,
        value: instance.id
    }));
};

const handleFormActionHandler = (action: { type: string; action: string; formData: any }) => {
    switch (action.type) {
        case 'save':
            prepareScheduleSave(action.formData);
            break;
        case 'close':
        case 'cancel':
            closeModal();
            break;
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
                :clickableRows="true"
                :rowActionDropdown="false"
                :rowActionLabels="false"
                :actions="[
                    { name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus' },
                    { name: 'view_report', type: 'info', min: 1, max: 1, label: 'View Report', loc: 'left', icon: 'chart-bar' },
                    { name: 'manual_run', type: 'primary', min: 1, max: 1, label: 'Manual Run', loc: 'left', icon: 'play-circle' },
                    { name: 'arguments', type: 'info', min: 1, max: 1, label: 'Arguments', icon: 'cog', loc: 'right' },
                    { name: 'edit', type: 'primary', min: 1, max: 1, label: 'Edit', icon: 'edit', loc: 'right' },
                    { name: 'delete', type: 'danger', min: 1, max: 1, label: 'Delete', icon: 'trash', loc: 'right' }
                ]"
                :rowActions="[
                    { type: 'arguments', label: 'Arguments', icon: 'cog', colorClass: 'text-gray-600 hover:bg-gray-50 dark:text-gray dark:hover:bg-gray-900/20' },
                    { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20' }
                ]"
                @actionHandler="handleDataGridActionHandler"
                @rowActionHandler="handleDataGridRowActionHandler"
                @rowClick="handleDataGridRowClick"
                         >
             </DataGrid>

            <!-- Modal for New/Edit Schedule -->
            <AlertModal 
                :isOpen="showModal"
                :title="modalMode === 'new' ? 'Add New Schedule' : 'Edit Schedule'"
                @close="closeModal"
            >
                <div class="relative">
                    <!-- Loading Overlay -->
                    <div 
                        v-if="loadingRoutes" 
                        class="absolute inset-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-lg"
                    >
                        <div class="flex flex-col items-center gap-3">
                            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-600 dark:border-blue-400"></div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Loading routes...</span>
                        </div>
                    </div>
                    
                    <!-- Form Viewer -->
                    <FormViewer
                        ref="formRef"
                        :formConfig="formConfig" 
                        :initialData="editingRow"
                        :cancelAction="'close'"
                        @change="handleFormDataChange"
                        :validateOnSubmit="true"
                        :disabled="loadingRoutes"
                        :actions="[
                            { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors', disabled: loadingRoutes },
                            { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors', disabled: loadingRoutes }
                        ]"
                        :actionHandler="handleFormActionHandler"
                    />
                </div>
            </AlertModal>

            <!-- Required comment before create/update schedule request -->
            <AlertModal
                :isOpen="commentDialogOpen"
                title="Save comment"
                @close="() => { if (!scheduleSaving) closeCommentDialog() }"
            >
                <FormViewer
                    ref="commentFormRef"
                    :formConfig="commentFormConfig"
                    :initialData="commentForm"
                    :cancelAction="'close'"
                    :actionHandler="handleCommentFormAction"
                    :actions="[
                        { type: 'save', action: 'save', label: 'Confirm', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                        { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                    ]"
                    :isSubmitting="scheduleSaving"
                    :disabled="scheduleSaving"
                />
            </AlertModal>
            
            <!-- Modal for Arguments -->
            <AlertModal 
                :isOpen="showArgumentsModal"
                :title="'Arguments'"
                @close="showArgumentsModal = false"
            >
                <FormViewer
                    ref="argumentsFormRef"
                    :formConfig="argumentsFormConfig"
                    :initialData="editingRow"
                    :cancelAction="'close'"
                    :validateOnSubmit="true"
                    :actions="[
                        { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors', disabled: loadingRoutes },
                        { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors', disabled: loadingRoutes }
                    ]"
                    :actionHandler="handleArgumentsFormActionHandler"
                />
            </AlertModal>

            <!-- Modal for Report -->
            <AlertModal 
                :isOpen="showReportModal"
                :title="''"
                @close="showReportModal = false"
            >
            <FormViewer
                :formConfig="reportFormConfig"
                :initialData="reportInitialData"
                :cancelAction="'close'"
                :validateOnSubmit="true"
                :actionHandler="handleReportFormActionHandler"
                :actions="[
                    { type: 'close', action: 'close', label: 'Close', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                ]"
            />
              
            </AlertModal>

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
    </AppLayout>
</template>
