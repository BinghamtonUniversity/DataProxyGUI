<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
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


const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/functions`,
    },
];

const page = usePage();

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <APILayout :api_id="props.api_id" :api_type="props.api_type" >
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
                                :class="['justify-start', 'px-3', 'py-1', 'w-auto', 'inline-flex', 'text-xs']" 
                                @click="selectedFunction = item"             
                            >
                                {{ item.name }}
                            </Button>
                        </nav>
                    </aside>
                    <div class="flex-1 min-w-0">
                        <Editor v-if="selectedFunction" :code="selectedFunction.content" :language="props.api_type === 'python' || props.api_type === 'php' ? props.api_type : undefined" />
                        <div v-else class="text-muted-foreground text-sm p-4">Select a function to view its code.</div>
                    </div>
                </div>

                
            </template>
        </APILayout>
    </AppLayout>
</template>
