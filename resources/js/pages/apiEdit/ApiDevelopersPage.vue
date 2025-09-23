<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import ApiDevelopers from '@/components/apiEdit/ApiDevelopers.vue';
import { type ApiData } from '@/types';

interface Props {
    api_type: string;
    api_id: string;
}

const props = defineProps<Props>();

// API data state
const apiData = ref<ApiData | null>(null);
const loadingApiData = ref(true);
const apiError = ref('');

// Use Laravel API routes
const apiBaseUrl = '/api';

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
};

// Fetch API data
const fetchApiData = async () => {
    try {
        loadingApiData.value = true;
        apiError.value = '';
        
        const response = await fetch(`${apiBaseUrl}/apis/${props.api_id}/versions/latest`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        apiData.value = data;
        
    } catch (err: any) {
        apiError.value = err.message || 'Failed to fetch API data';
        console.error('Error fetching API data:', err);
    } finally {
        loadingApiData.value = false;
    }
};

// Update API data function
const updateApiData = (updatedApiData: ApiData) => {
    apiData.value = updatedApiData;
};

// Refresh API data function
const refreshApiData = () => {
    fetchApiData();
};

// Breadcrumb items
const breadcrumbItems = [
    { title: `API ${props.api_id}`, href: `/apis/${props.api_type}/${props.api_id}` },
    { title: 'Developers', href: '#' }
];

// Fetch data on component mount
onMounted(() => {
    fetchApiData();
});
</script>

<template>
    <Head title="API Developers" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="px-4 py-6 overflow-x-auto h-full bg-gray-50 dark:bg-gray-900">


            <div class="flex-1 w-full">
                <section class="w-full space-y-6 h-full flex-1">
                    <!-- API Developers Component -->
                    <ApiDevelopers 
                        :api_id="props.api_id"
                        :api_type="props.api_type"
                        :apiData="apiData"
                        :loadingApiData="loadingApiData"
                        :apiError="apiError"
                        :updateApiData="updateApiData"
                        :refreshApiData="refreshApiData"
                    />
                </section>
            </div>
        </div>
    </AppLayout>
</template>
