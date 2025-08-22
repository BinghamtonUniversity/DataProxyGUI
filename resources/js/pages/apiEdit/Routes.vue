<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import APILayout from '@/layouts/api/Layout.vue';
import { type BreadcrumbItem, type ApiData } from '@/types';

interface Props {
    api_id: string;
    api_type: string;
}

interface RouteParams{
    name: string;
}


const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/routes`,
    },
];

const page = usePage();

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="API Management" />

        <APILayout :api_id="props.api_id" :api_type="props.api_type">
            <template #default="{ apiData, loadingApiData, apiError }:
            {
                apiData: ApiData | null, 
                loadingApiData: boolean, 
                apiError: string 
            }">

                <div class="flex flex-col space-y-6">
                    <div v-if="loadingApiData">Loading API data...</div>
                    <div v-else-if="apiError" class="text-red-600">{{ apiError }}</div>
                    <div v-else>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>View Name</TableHead>
                                <TableHead>Path</TableHead>
                                <TableHead>Verb</TableHead>
                                <TableHead>Parameters</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                                <TableRow v-for="route in apiData?.version_urls || []" :key="route.id">
                                    <TableCell>{{ route.view_name }}</TableCell>
                                    <TableCell>{{ route.path }}</TableCell>
                                    <TableCell>{{ route.verb }}</TableCell>
                                    <TableCell>
                                        <span v-if="route.required?.length">
                                            <strong>{{ route.required.map(e => e.name).join(', ') }}</strong>
                                        </span>
                                        <span v-if="route.optional?.length">
                                            <span v-if="route.required?.length"> | </span>
                                            {{ route.optional.map(e => e.name).join(', ') }}
                                        </span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                    </Table> 
                    </div>   
                </div>
             </template>
        </APILayout>
    </AppLayout>
</template>
