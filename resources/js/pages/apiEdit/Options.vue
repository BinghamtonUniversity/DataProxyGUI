<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
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

// Form state
const formData = ref({
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
const handleSave = async () => {
    if (!formData.value.fields || formData.value.fields.length === 0) {
        saveError.value = 'Please add at least one field to the form';
        return;
    }

    isSaving.value = true;
    saveError.value = null;
    saveSuccess.value = false;

    try {
        const response = await fetch(`/api/apis/${props.api_id}/options`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(formData.value)
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        console.log('Options saved successfully:', result);
        
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

// Load existing options if available
const loadExistingOptions = async () => {
    try {
        const response = await fetch(`/api/apis/${props.api_id}/options`);
        if (response.ok) {
            const data = await response.json();
            if (data.form_config) {
                formData.value = data.form_config;
            } else {
                // Initialize with default structure if no existing data
                formData.value = {
                    name: 'options',
                    fields: []
                };
            }
        } else {
            // Initialize with default structure if no existing data
            formData.value = {
                name: 'options',
                fields: []
            };
        }
    } catch (error) {
        console.log('No existing options found or error loading:', error);
        // Initialize with default structure on error
        formData.value = {
            name: 'options',
            fields: []
        };
    }
};

onMounted(() => {
    loadExistingOptions();
});
</script>

<template>
    <Head title="API Options" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <APILayout :api_id="props.api_id" :api_type="props.api_type">
            <template #default="{ apiData, loadingApiData, apiError }:
            {
                apiData: ApiData | null, 
                loadingApiData: boolean, 
                apiError: string 
            }">
                <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                    <div class="">
                        
                        <!-- Save Button -->
                        <div class="flex justify-end mb-4">
                            <Button 
                                @click="handleSave" 
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
                            <FormBuilder 
                                :form-data="formData"
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
