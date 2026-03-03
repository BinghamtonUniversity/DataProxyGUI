<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'FormViewer Example',
        href: '/formviewer-example',
    },
];

// Default example form config
const defaultFormConfig = {
    label: 'Demo Form',
    description: 'This is a demo form rendered by FormViewer.',
    fields: [
        { name: 'name', label: 'Name', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true },
        { name: 'age', label: 'Age', type: 'number' },
        { name: 'subscribe', label: 'Subscribe to newsletter', type: 'checkbox' }
    ]
};

const jsonInput = ref(JSON.stringify(defaultFormConfig, null, 2));
const jsonError = ref('');

const formConfig = computed(() => {
    try {
        const parsed = JSON.parse(jsonInput.value);
        jsonError.value = '';
        return parsed;
    } catch (error) {
        jsonError.value = 'Invalid JSON format';
        return defaultFormConfig;
    }
});

const resetToDefault = () => {
    jsonInput.value = JSON.stringify(defaultFormConfig, null, 2);
    jsonError.value = '';
};

const loadExample = (exampleType: 'user-profile' | 'product-form') => {
    const examples = {
        'user-profile': {
            label: 'User Profile Form',
            description: 'A comprehensive user profile form with various field types.',
            fields: [
                { name: 'firstName', label: 'First Name', type: 'text', required: true },
                { name: 'lastName', label: 'Last Name', type: 'text', required: true },
                { name: 'email', label: 'Email Address', type: 'email', required: true },
                { name: 'phone', label: 'Phone Number', type: 'tel' },
                { name: 'dateOfBirth', label: 'Date of Birth', type: 'date' },
                { name: 'gender', label: 'Gender', type: 'select', options: ['Male', 'Female', 'Other', 'Prefer not to say'] },
                { name: 'bio', label: 'Biography', type: 'textarea', rows: 4 },
                { name: 'newsletter', label: 'Subscribe to newsletter', type: 'checkbox' },
                { name: 'terms', label: 'I agree to the terms and conditions', type: 'checkbox', required: true }
            ]
        },
        'product-form': {
            label: 'Product Information',
            description: 'Product details form for e-commerce applications.',
            fields: [
                { name: 'productName', label: 'Product Name', type: 'text', required: true },
                { name: 'category', label: 'Category', type: 'select', options: ['Electronics', 'Clothing', 'Books', 'Home & Garden', 'Sports'] },
                { name: 'price', label: 'Price', type: 'number', min: 0, step: 0.01, required: true },
                { name: 'description', label: 'Description', type: 'textarea', rows: 6 },
                { name: 'sku', label: 'SKU', type: 'text' },
                { name: 'inStock', label: 'In Stock', type: 'checkbox', default: true },
                { name: 'tags', label: 'Tags', type: 'text', placeholder: 'Separate with commas' }
            ]
        }
    } as const;
    
    jsonInput.value = JSON.stringify(examples[exampleType], null, 2);
    jsonError.value = '';
};
</script>

<template>
    <Head title="FormViewer Example" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 gap-6 rounded-xl p-4 overflow-x-auto">
            <!-- JSON Input Section - Left Side -->
            <div class="w-1/2 space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Form Configuration JSON</h2>
                    <div class="flex gap-2">
                        <button 
                            @click="loadExample('user-profile')"
                            class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors"
                        >
                            Load User Profile Example
                        </button>
                        <button 
                            @click="loadExample('product-form')"
                            class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors"
                        >
                            Load Product Form Example
                        </button>
                        <button 
                            @click="resetToDefault"
                            class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                        >
                            Reset to Default
                        </button>
                    </div>
                </div>
                
                <div class="relative">
                    <textarea
                        v-model="jsonInput"
                        class="w-full h-96 p-3 border rounded-lg font-mono text-sm resize-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Enter your form configuration JSON here..."
                    ></textarea>
                    <div v-if="jsonError" class="absolute top-2 right-2 text-red-500 text-sm bg-red-50 px-2 py-1 rounded">
                        {{ jsonError }}
                    </div>
                </div>
                
                <div class="text-sm text-gray-600">
                    <p>Modify the JSON above to customize the form. The form will update automatically as you type.</p>
                    <p class="mt-1">Supported field types: text, email, number, checkbox, select, textarea, date, tel</p>
                </div>
            </div>

            <!-- Form Preview Section - Right Side -->
            <div class="w-1/2 space-y-4">
                <h2 class="text-lg font-semibold">Form Preview</h2>
                <div class="border rounded-lg p-6 h-96 overflow-y-auto">
                    <FormViewer :formConfig="formConfig" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
