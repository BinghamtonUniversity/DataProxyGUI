<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiData } from '@/types'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'

// Import your components (convert them to pure components)
import Routes from '@/components/apiEdit/Routes.vue'
import Resources from '@/components/apiEdit/Resources.vue'
import Functions from '@/components/apiEdit/Functions.vue'
import Models from '@/components/apiEdit/Models.vue'
import Options from '@/components/apiEdit/Options.vue'


interface Props {
    api_type: string
    api_id: string
    activeTab: string
}

const props = defineProps<Props>()

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/routes`,
    },
]

// Tab configuration
const tabs = [
    { 
        id: 'routes', 
        title: 'Routes', 
        component: Routes,
        routeName: 'apiEdit.index'
    },
    { 
        id: 'resources', 
        title: 'Resources', 
        component: Resources,
        routeName: 'apiEdit.index'
    },
    { 
        id: 'functions', 
        title: 'Functions', 
        component: Functions,
        routeName: 'apiEdit.index'
    },
    { 
        id: 'models', 
        title: 'Models', 
        component: Models,
        routeName: 'apiEdit.index'
    },
    { 
        id: 'options', 
        title: 'Options', 
        component: Options,
        routeName: 'apiEdit.index'
    }
]

// Data fetching logic - runs once when component mounts
const apiData = ref<ApiData | null>(null)
const loadingApiData = ref(true)
const apiError = ref('')
const apiBaseUrl = '/api'

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
}

const fetchApiData = async () => {
    loadingApiData.value = true
    apiError.value = ''
    try {
        const response = await fetch(`${apiBaseUrl}/apis/${props.api_id}/versions/latest`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        })
        if (!response.ok) throw new Error('Failed to fetch API data')
        apiData.value = await response.json()
    } catch (e: any) {
        apiError.value = e.message || 'Error fetching API data'
        apiData.value = null
    } finally {
        loadingApiData.value = false
    }
}

const updateApiData = (updatedApiData: ApiData) => {
    apiData.value = updatedApiData
}

const refreshApiData = () => {
    fetchApiData()
}

// Navigation helper
const navigateToTab = (tabId: string) => {
    router.get(`/apis/${props.api_type}/${props.api_id}/${tabId}`, {}, {
        preserveState: true,
        preserveScroll: true,
        // only: ['activeTab'] // Only update the activeTab prop
    })
}

// Get current active component
const activeComponent = computed(() => {
    return tabs.find(tab => tab.id === props.activeTab)?.component || tabs[0].component
})

// Component props to pass down
const componentProps = computed(() => ({
    api_id: props.api_id,
    api_type: props.api_type,
    apiData: apiData.value,
    loadingApiData: loadingApiData.value,
    apiError: apiError.value,
    updateApiData,
    refreshApiData
}))

// Fetch data on mount
onMounted(() => fetchApiData())
</script>

<template>
    <Head title="API Edit" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="px-4 py-6">
            <Heading :title="`API - ${props.api_id}`" description="Manage your API settings" />

            <div class="flex flex-col space-y-8">
                <!-- Tab navigation -->
                <nav class="flex w-full mb-8">
                    <Button
                        v-for="tab in tabs"
                        :key="tab.id"
                        variant="ghost"
                        :class="[
                            'flex-1 px-4 py-2 rounded-t-md text-center transition-colors',
                            { 'bg-muted font-semibold': props.activeTab === tab.id }
                        ]"
                        @click="navigateToTab(tab.id)"
                    >
                        {{ tab.title }}
                    </Button>
                </nav>

                <div class="flex-1 w-11/12">
                    <section class="w-full space-y-12">
                        <!-- Dynamic component rendering -->
                        <component
                            :is="activeComponent"
                            v-bind="componentProps"
                        />
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>