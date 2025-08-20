<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableHeader, TableBody, TableHead, TableRow, TableCell } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import APILayout from '@/layouts/api/Layout.vue';
import { type BreadcrumbItem, type User } from '@/types';

interface Props {
    api_id: string;
}

interface ApiVersionUrl {
  id: number
  description: string
  path: string
  function_name: string
  verb: string
  parameters: string
  // ...other fields
}

interface ApiData {
  id: number
  api: number
  summary: string | null
  description: string | null
  stable: boolean
  version_urls: ApiVersionUrl[]
  // ...other fields
}

const props = defineProps<Props>();
const apiData = ref<ApiData | null>(null)

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_id}/routes`,
    },
];

const page = usePage();

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="API Management" />

        <APILayout :api_id="props.api_id">
            <template #default="{ apiData, loadingApiData, apiError }:
            {
                apiData: ApiData | null, 
                loadingApiData: boolean, 
                apiError: string 
            }">

                <div class="flex flex-col space-y-6">
                    <HeadingSmall title="Routes" description="List of API routes" />
                    <div v-if="loadingApiData">Loading API data...</div>
                    <div v-else-if="apiError" class="text-red-600">{{ apiError }}</div>
                    <div v-else>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Description</TableHead>
                                <TableHead>Path</TableHead>
                                <TableHead>Function Name</TableHead>
                                <TableHead>Verb</TableHead>
                                <TableHead>Parameters</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                                <TableRow v-for="route in apiData?.version_urls || []" :key="route.id">
                                    <TableCell>{{ route.description }}</TableCell>
                                    <TableCell>{{ route.path }}</TableCell>
                                    <TableCell>{{ route.function_name }}</TableCell>
                                    <TableCell>{{ route.verb }}</TableCell>
                                    <TableCell>{{ route.parameters }}</TableCell>
                                </TableRow>
                            </TableBody>
                    </Table> 
                    </div>   
                </div>
             </template>
        </APILayout>
    </AppLayout>
</template>
