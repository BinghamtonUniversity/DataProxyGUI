<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Save, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import APILayout from '@/layouts/api/Layout.vue';
import FormBuilder from '@/components/formbuilder/FormBuilder.vue';
import { type BreadcrumbItem, type ApiData } from '@/types';

interface Props {
    api_id: string;
    api_type: string;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/options`,
    },
];

const page = usePage();
const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL || '';

// Form state
const formData = ref<{
    name: string;
    fields: any[];
}>({
    name: 'options',
    fields: []
});

const isSaving = ref(false);
const saveError = ref<string | null>(null);
const saveSuccess = ref(false);

// Handle form builder changes
const handleFormChange = (newFormData: any) => {
    formData.value = newFormData;
    console.log('Form data updated:', formData.value);
};

// Save options to database
const handleSave = async (apiData: ApiData | null, updateApiData: (updatedApiData: ApiData) => void) => {
    if (!formData.value.fields || formData.value.fields.length === 0) {
        saveError.value = 'Please add at least one field to the form';
        return;
    }

    if (!apiData) {
        saveError.value = 'API data not available';
        return;
    }

    isSaving.value = true;
    saveError.value = null;
    saveSuccess.value = false;

    try {
        // Create updated API data with new options
        const updatedApiData: ApiData = {
            ...apiData,
            options: formData.value.fields // Replace options with just the fields array
        };

        // Update the local state using the updateApiData function
        updateApiData(updatedApiData);

        // Send the updated API data to the backend
        const response = await fetch(`${djangoBaseUrl}/api/apis/${props.api_id}/code`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(updatedApiData)
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const responseData = await response.json();
        updateApiData(responseData || updatedApiData);
        
        saveSuccess.value = true;
        setTimeout(() => {
            saveSuccess.value = false;
        }, 3000);

    } catch (error) {
        console.error('Save error:', error);
        saveError.value = error instanceof Error ? error.message : 'Failed to save options';
    } finally {
        isSaving.value = false;
    }
};

// Load existing options from apiData
const loadExistingOptions = (apiData: ApiData | null) => {
    if (apiData && apiData.options && apiData.options.length > 0) {
        // If options exist in apiData, check if it's an array of fields or objects with form_config
        const existingOptions = apiData.options;
        
        // Check if the first option has form_config (old format) or is directly fields (new format)
        if (existingOptions[0] && existingOptions[0].form_config) {
            // Old format: options contains objects with form_config
            formData.value = existingOptions[0].form_config;
        } else {
            // New format: options is directly an array of fields
            formData.value = {
                name: 'options',
                fields: existingOptions
            };
        }
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
    }
};
</script>

<template>
    <Head title="API Options" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <APILayout :api_id="props.api_id" :api_type="props.api_type">
            <template #default="{ apiData, loadingApiData, apiError, updateApiData }:
            {
                apiData: ApiData | null, 
                loadingApiData: boolean, 
                apiError: string,
                updateApiData: (updatedApiData: ApiData) => void
            }">
                <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                    <div class="">
                        
                        <!-- Save Button -->
                        <div class="flex justify-end mb-4">
                            <Button 
                                @click="() => handleSave(apiData, updateApiData)" 
                                :disabled="isSaving"
                                class="flex items-center gap-2"
                            >
                                <Save class="h-4 w-4" />
                                {{ isSaving ? 'Saving...' : 'Save Options' }}
                            </Button>
                        </div>

                        <!-- Save Status Messages -->
                        <div v-if="saveError" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                            {{ saveError }}
                        </div>
                        
                        <div v-if="saveSuccess" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                            Options saved successfully!
                        </div>

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

                            <!-- Form Preview -->
                            <Card class="mt-6">
                                <CardHeader>
                                    <CardTitle>Form Configuration Preview</CardTitle>
                                    <CardDescription>
                                        This is the JSON configuration that will be saved to the database.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <pre class="bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-auto text-sm">{{ JSON.stringify(formData, null, 2) }}</pre>
                                </CardContent>
                            </Card>
                        </template>
                    </div>
                </div>
            </template>
        </APILayout>
    </AppLayout>
</template>
