<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Button } from '@/components/ui/button'
import Editor from '@/pages/Editor.vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Plus, Trash2, Upload, Download } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import APILayout from '@/layouts/api/Layout.vue';
import FormBuilder from '@/components/formbuilder/FormBuilder.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import { type BreadcrumbItem, type ApiData, Api } from '@/types';
interface Props {
    api_id: string
    api_type: string
    api: Api | null
    apiData: ApiData | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
    highlightQuery?: string
    highlightTarget?: string
    isVersionSwitch?: boolean
}

const props = defineProps<Props>()


const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_id}/options`,
    },
];

const page = usePage();
const apiBaseUrl = '/api';

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
};

// Form state
const formData = ref<{
    name: string;
    fields: any[];
}>({
    name: 'options',
    fields: []
});

// Import modal state
const showImportModal = ref(false);
const importJsonData = ref('');

// Form changes update local state only - database save via Ctrl+S

// Handle form builder changes - update local state only
const handleFormChange = (newFormData: any) => {
    if (isUpdatingFromApiData.value) return;
    
    formData.value = newFormData;
    
    if (props.apiData) {
        const updatedApiData: ApiData = {
            ...props.apiData,
            options: formData.value // Replace options with just the fields array
        };

        // Update the local state using the updateApiData function
        props.updateApiData(updatedApiData);
    }
};

// Load existing options from apiData
const loadExistingOptions = (apiData: ApiData | null) => {
    if (apiData && apiData.options ) {
        // If options exist in apiData, check if it's an array of fields or objects with form_config
        formData.value = apiData.options;
        
    } else {
        // Initialize with default structure if no options exist
        formData.value = {
            name: 'options',
            fields: []
        };
    }
};

// Initialize form data when component loads
const initializeFormData = (apiData: ApiData | null) => {
    if (apiData && !formData.value.fields.length) {
        loadExistingOptions(apiData);
        // hasUnsavedChanges.value = false;
    }
};

// Flag to prevent recursive updates
const isUpdatingFromApiData = ref(false);

// Watch for changes in apiData to update form data
watch(() => props.apiData, (newApiData: ApiData | null) => {
    if (isUpdatingFromApiData.value) return;
    

    isUpdatingFromApiData.value = true;
    
    if (newApiData && newApiData.options) {
   
        formData.value = newApiData.options;
    }
    else{
       
        formData.value = {
            name: 'options',
            fields: []
        };
    }
    
    // Reset flag after a short delay
    setTimeout(() => {
        isUpdatingFromApiData.value = false;
    }, 100);
}, { deep: true });

// Event listeners for dropdown actions
const handleImportEvent = () => {
    openImportModal();
};

const handleExportEvent = () => {
    exportOptions();
};

// Mount/unmount event listeners
onMounted(() => {
    window.addEventListener('openOptionsImport', handleImportEvent);
    window.addEventListener('exportOptions', handleExportEvent);
});

onUnmounted(() => {
    window.removeEventListener('openOptionsImport', handleImportEvent);
    window.removeEventListener('exportOptions', handleExportEvent);
});

// Import modal functions
const openImportModal = () => {
    showImportModal.value = true;
    importJsonData.value = JSON.stringify(formData.value, null, 2);
};

const closeImportModal = () => {
    showImportModal.value = false;
    importJsonData.value = '';
};

const handleImportSubmit = (formValues: any) => {
    try {
        const importedData = JSON.parse(formValues.jsonData);
        
        // Validate the imported data structure
        if (!importedData || typeof importedData !== 'object') {
            throw new Error('Invalid JSON structure');
        }
        
        if (!importedData.fields || !Array.isArray(importedData.fields)) {
            throw new Error('JSON must contain a "fields" array');
        }
        
        // Update the form data with imported data
        formData.value = {
            name: importedData.name || 'options',
            fields: importedData.fields
        };
        
        // Update the API data
        if (props.apiData) {
            const updatedApiData: ApiData = {
                ...props.apiData,
                options: formData.value
            };
            props.updateApiData(updatedApiData);
        }
        
        closeImportModal();
        
    } catch (error) {
        console.error('Import error:', error);
        alert('Invalid JSON format. Please check your JSON and try again.');
    }
};

const handleImportAction = (actionData: { type: string; action: string; formData: any }) => {
    if (actionData.type === 'close') {
        closeImportModal();
    }
};

// Export current options as JSON
const exportOptions = () => {
    const dataStr = JSON.stringify(formData.value, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'options.json';
    link.click();
    URL.revokeObjectURL(url);
};

// Form configuration for import modal
const importFormConfig = {
    label: '',
    description: '',
    name: "import-options-form",
    files: false,
    fields: [
        {
            name: "jsonData",
            label: "JSON Data",
            type: "monaco",
            placeholder: "Paste your JSON options here...",
            value: "",
            help: "Paste the JSON configuration for your options",
            info: "The JSON should contain a 'fields' array with your form configuration",
            width: "12",
            offset: "0",
            required: true,
            language: "json",
            height: 600
        }
    ]
};
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
            <template v-if="loadingApiData">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
                        <p class="mt-2">Loading API data...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading API data: {{ apiError }}</p>
                    </div>
                </div>
            </template>

            <!-- Form Builder -->
            <template v-else>
                <!-- Initialize form data when apiData is available -->
                <div v-if="apiData && !formData.fields.length" style="display: none;">
                    {{ initializeFormData(apiData) }}
                </div>
                <FormBuilder 
                    :form-data="formData"
                    :allowFormNameEdit="false"
                    @update:form-data="handleFormChange"
                />

 
            </template>
        </div>
        
        <!-- Import Modal -->
        <AlertModal 
            :isOpen="showImportModal"
            title="Import Options"
            @close="closeImportModal"
        >
            <FormViewer 
                :formConfig="importFormConfig" 
                :initialData="{ jsonData: importJsonData }"
                :cancelAction="'close'"
                @submit="handleImportSubmit"
                @action="handleImportAction"
            />
        </AlertModal>
    </div>
</template>
