<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import APILayout from '@/layouts/api/Layout.vue';
import Editor from '@/pages/Editor.vue';
import { type BreadcrumbItem, type ApiData, type ApiVersionFunction } from '@/types';

interface Props {
    api_id: string;
    api_type: string;
}

const props = defineProps<Props>();
const selectedFunction = ref<ApiVersionFunction | null>(null);
const isSaving = ref(false);
const saveError = ref<string | null>(null);
const saveSuccess = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/functions`,
    },
];

const page = usePage();

const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL || '';

const handleSave = async (updatedCode: string, apiData: ApiData | null) => {
    if (!selectedFunction.value || !apiData) {
        saveError.value = 'No function selected or API data not available';
        return;
    }

    isSaving.value = true;
    saveError.value = null;
    saveSuccess.value = false;

    try {
        // Update the selected function's content
        const updatedApiData = {
            ...apiData,
            version_views: apiData.version_views.map(func => 
                func.name === selectedFunction.value?.name 
                    ? { ...func, content: updatedCode }
                    : func
            )
        };
        console.log('Updated API Data:', updatedApiData);

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

        const result = await response.json();
        
        // Update the local selectedFunction with the new content
        if (selectedFunction.value) {
            selectedFunction.value.content = updatedCode;
        }
        
        saveSuccess.value = true;
        setTimeout(() => {
            saveSuccess.value = false;
        }, 3000);

    } catch (error) {
        console.error('Save error:', error);
        saveError.value = error instanceof Error ? error.message : 'Failed to save changes';
    } finally {
        isSaving.value = false;
    }
};

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <APILayout :api_id="props.api_id" :api_type="props.api_type">
            <template #default="{ apiData, loadingApiData, apiError }:
            {
                apiData: ApiData | null, 
                loadingApiData: boolean, 
                apiError: string 
            }">
                <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-8">
                    <aside class="max-w-xs lg:w-40 lg:min-w-40 lg:flex-shrink-0">
                        <nav class="flex flex-col space-y-1">
                            <Button
                                v-for="item in apiData?.version_views || []"
                                :key="item.name"
                                variant="ghost"
                                :class="[
                                    'justify-start', 
                                    'px-3', 
                                    'py-1', 
                                    'w-auto', 
                                    'inline-flex', 
                                    'text-xs',
                                    selectedFunction?.name === item.name ? 'bg-accent' : ''
                                ]" 
                                @click="selectedFunction = item"             
                            >
                                {{ item.name }}
                            </Button>
                        </nav>
                    </aside>
                    <div class="flex-1 min-w-0">
                        <!-- Save status messages -->
                        <div v-if="saveError" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                            {{ saveError }}
                        </div>
                        <div v-if="saveSuccess" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                            Changes saved successfully!
                        </div>
                        
                        <Editor 
                            v-if="selectedFunction" 
                            :code="selectedFunction.content" 
                            :language="props.api_type === 'python' || props.api_type === 'php' ? props.api_type : undefined"
                            :is-saving="isSaving"
                            @save="(code) => handleSave(code, apiData)"
                        />
                        <div v-else class="text-muted-foreground text-sm p-4">
                            Select a function to view its code.
                        </div>
                    </div>
                </div>
            </template>
        </APILayout>
    </AppLayout>
</template>