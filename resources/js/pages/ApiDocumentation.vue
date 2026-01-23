<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    api_type: String,
    instance_id: String,
});

const documentation = ref(null);
const loading = ref(true);
const error = ref(null);

onMounted(async () => {
    try {
        const response = await fetch(`/api/api_docs/${props.api_type}/${props.instance_id}`);
        if (!response.ok) throw new Error('Failed to load documentation');
        documentation.value = await response.json();
    } catch (err) {
        error.value = err.message;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head :title="`API Documentation - ${api_type}`" />
    
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h1 class="text-3xl font-bold mb-6">API Documentation</h1>
                
                <div v-if="loading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
                </div>
                
                <div v-else-if="error" class="text-red-600 p-4 bg-red-50 rounded">
                    {{ error }}
                </div>
                
                <div v-else class="prose max-w-none">
                    <!-- Render your documentation here -->
                    <pre class="bg-gray-100 p-4 rounded overflow-auto">{{ documentation }}</pre>
                </div>
            </div>
        </div>
    </div>
</template>