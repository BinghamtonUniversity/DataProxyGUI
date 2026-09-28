<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import AlertModal from '@/components/AlertModal.vue';
import { useToaster } from '@/composables/useToaster';
import { ref, computed, onMounted } from 'vue';
import { getCsrfToken } from '@/lib/utils';
import { CodeDiff } from 'v-code-diff';

// Use Laravel API routes instead of direct Django calls to avoid CORS
const apiBaseUrl = '/api';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Activity Log',
        href: '/activity-log',
    },
];

// Data state
const activityLogs = ref<any[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

// Diff dialog state
const diffModalOpen = ref(false);
const selectedDiffRow = ref<any | null>(null);

// Toaster
const { success, error: showError, warning, info } = useToaster();

// Form configuration for activity logs
const activityLogsSchema = {
    label: '',
    description: '',
    name: "activity-logs-schema",
    files: false,
    fields: [
        {
            name: "event_type",
            label: "Event Type",
            type: "text",
            placeholder: "Event type",
            value: "",
            help: "Type of event that was performed",
            info: "Type of event that was performed",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "event_id",
            label: "Event ID",
            type: "text",
            placeholder: "Event ID",
            value: "",
            help: "ID of the event that was performed",
            info: "ID of the event that was performed",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "action",
            label: "Type",
            type: "text",
            placeholder: "Type of event that was performed",
            value: "",
            help: "Type of event that was performed",
            info: "Type of event that was performed",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "user_id",
            label: "User",
            type: "text",
            placeholder: "User ID",
            value: "",
            help: "ID of the user who performed the action",
            info: "ID of the user who performed the action",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "comment",
            label: "Comment",
            type: "text",
            placeholder: "Comment",
            value: "",
            help: "Comment of the event that was performed",
            info: "Comment of the event that was performed",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "old",
            label: "Old",
            type: "text",
            placeholder: "Old",
            value: "",
            help: "Old of the event that was performed",
            info: "Old of the event that was performed",
            width: "12",
            offset: "0",
            required: false,
            showColumn: true
        },
        {
            name: "new",
            label: "New",
            type: "text",
            placeholder: "New",
            value: "",
            help: "New of the event that was performed",
            info: "New of the event that was performed",
            width: "12",
            offset: "0",
            required: false,
            showColumn: true
        },
        {
            name: "created_at",
            label: "Time Stamp",
            type: "date",
            placeholder: "Time stamp",
            value: "",
            help: "Time stamp of the event that was performed",
            info: "Time stamp of the event that was performed",
            width: "12",
            offset: "0",
            required: false,
            showColumn: true
        }
    ]
};

/** Pretty-print old/new values for preview and diff. */
const formatJsonPreview = (value: unknown): string => {
    if (value === undefined || value === null) {
        return 'null';
    }
    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (trimmed === '') {
            return '""';
        }
        try {
            return JSON.stringify(JSON.parse(trimmed), null, 2);
        } catch {
            return value;
        }
    }
    try {
        return JSON.stringify(value, null, 2);
    } catch {
        return String(value);
    }
};

const diffOldString = computed(() =>
    selectedDiffRow.value ? formatJsonPreview(selectedDiffRow.value.old) : ''
);
const diffNewString = computed(() =>
    selectedDiffRow.value ? formatJsonPreview(selectedDiffRow.value.new) : ''
);

const openDiffModal = (row: any) => {
    selectedDiffRow.value = row;
    diffModalOpen.value = true;
};

const closeDiffModal = () => {
    diffModalOpen.value = false;
    selectedDiffRow.value = null;
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
            warning('formatTimestamp: Invalid date:', timestamp);
            return '';
        }
        const formatted = date.toLocaleString();
       
        return formatted;
    } catch (error: any) {
        warning('formatTimestamp: Error formatting timestamp:',  error.message || '');
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

// Fetch schedules from API
const fetchActivityLogs = async () => {
    try {
        loading.value = true;
        error.value = null;
        
        const response = await fetch(`api/activity_log`, {
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
        activityLogs.value = data;
        
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch activity logs';
        showError('Failed to fetch activity logs. Please try again.', 'Error');
        console.error('Error fetching activity logs:', err);
    } finally {
        loading.value = false;
    }
};

// Fetch data on component mount
onMounted(async () => {
    await fetchActivityLogs();
});
</script>

<template>

    <Head title="Activity Logs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">


            <!-- Loading State -->
            <div v-if="loading" class="flex justify-center items-center py-12">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="ml-3 text-gray-600 dark:text-gray-300">Loading activity logs...</span>
            </div>

   

            <!-- DataGrid -->
            <DataGrid 
                v-else
                :schema="activityLogsSchema"
                :data="activityLogs"
                :upload="false"
                theme="default"
                :showNew="false"
                :showEdit="false"
                :showDelete="false"
            >
                <template #cell-old="{ row, value }">
                    <pre
                        class="max-w-[240px] max-h-24 overflow-auto rounded-md border border-gray-200 bg-gray-50 p-2 font-mono text-xs text-gray-800 cursor-pointer hover:border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-gray-600 dark:hover:bg-gray-800 whitespace-pre"
                        title="Click to view diff"
                        @click.stop="openDiffModal(row)"
                    >{{ formatJsonPreview(value) }}</pre>
                </template>
                <template #cell-new="{ row, value }">
                    <pre
                        class="max-w-[240px] max-h-24 overflow-auto rounded-md border border-gray-200 bg-gray-50 p-2 font-mono text-xs text-gray-800 cursor-pointer hover:border-gray-300 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-gray-600 dark:hover:bg-gray-800 whitespace-pre"
                        title="Click to view diff"
                        @click.stop="openDiffModal(row)"
                    >{{ formatJsonPreview(value) }}</pre>
                </template>
             </DataGrid>

            <AlertModal
                :isOpen="diffModalOpen"
                title="Diff"
                width="sm:max-w-4xl"
                @close="closeDiffModal"
            >
                <div class="overflow-auto max-h-[70vh] rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                    <CodeDiff
                        v-if="selectedDiffRow"
                        :old-string="diffOldString"
                        :new-string="diffNewString"
                        language="json"
                        :context="10"
                        output-format="line-by-line"
                    />
                </div>
                <template #footer>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors dark:text-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700"
                        @click="closeDiffModal"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Close
                    </button>
                </template>
            </AlertModal>

            
            <!-- Global Toaster -->
            <Toaster />
        </div>
    </AppLayout>
</template>
