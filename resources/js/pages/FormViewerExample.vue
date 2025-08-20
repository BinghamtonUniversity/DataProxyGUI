<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import FormViewer from '@/components/formviewer/FormViewer.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'FormViewer Example',
        href: '/formviewer-example',
    },
];

// Example form config for demonstration
const formConfig = {
    label: 'Demo Form',
    description: 'This is a demo form rendered by FormViewer.',
    fields: [
        { name: 'name', label: 'Name', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true },
        { name: 'age', label: 'Age', type: 'number' },
        { name: 'subscribe', label: 'Subscribe to newsletter', type: 'checkbox' }
    ]
};
</script>

<template>
    <Head title="FormViewer Example" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <FormViewer :formConfig="formConfig" />
        </div>
    </AppLayout>
</template>
