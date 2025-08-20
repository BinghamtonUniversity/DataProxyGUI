<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import FormViewer from '@/components/formbuilder/FormBuilder.vue';
import FormBuilder from '@/components/formbuilder/FormBuilder.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'FormBuilder Example',
        href: '/formbuilder-example',
    },
];


</script>

<template>
    <Head title="FormBuilder Example" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <FormBuilder />
        </div>
    </AppLayout>
</template>
