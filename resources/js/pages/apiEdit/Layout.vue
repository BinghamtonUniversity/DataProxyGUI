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
import Files from '@/components/apiEdit/Files.vue'
import ApiDevelopers from '@/components/apiEdit/ApiDevelopers.vue'
import BottomSheet from '@/components/BottomSheet.vue'


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
        id: 'files', 
        title: 'Files', 
        component: Files,
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

// Modal state for API Developers
const showApiDevelopersModal = ref(false)

// Dropdown state for Developers button
const showDevelopersDropdown = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
}

const fetchApiData = async () => {
    loadingApiData.value = true
    apiError.value = ''
    try {

        const response = await fetch(`/ajax/apis/${props.api_id}/versions/latest`)
        // console.log('Fetch response:', response)

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

// Modal functions for API Developers
const openApiDevelopersModal = () => {
    showApiDevelopersModal.value = true
}

const closeApiDevelopersModal = () => {
    showApiDevelopersModal.value = false
}

// Dropdown functions for Developers button
const toggleDevelopersDropdown = () => {
    showDevelopersDropdown.value = !showDevelopersDropdown.value
    console.log('Toggle developers dropdown', showDevelopersDropdown.value)
}

const handleDevelopersAction = (action: string) => {
    showDevelopersDropdown.value = false
    
    switch (action) {
        case 'export':
            console.log('Export developers')
            // Implement export functionality
            break
        case 'import':
            console.log('Import developers')
            // Implement import functionality
            break
        case 'versions':
            console.log('Show versions')
            // Navigate to versions or show versions modal
            break
        case 'instances':
            console.log('Show instances')
            // Navigate to instances or show instances modal
            break
        case 'publish':
            console.log('Publish new version')
            // Implement publish functionality
            break
        default:
            console.log('Unknown action:', action)
    }
}

// Save function
const handleSave = () => {
    console.log('Save API data')
    // Implement save functionality
    // This could save the current API configuration, settings, etc.
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
onMounted(() => {
    fetchApiData()
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
        const target = event.target as HTMLElement
        
        // Check if click is outside the dropdown container
        if (dropdownRef.value && !dropdownRef.value.contains(target)) {
            showDevelopersDropdown.value = false
        }
    })
})
</script>

<template>
    <Head title="API Edit" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="px-4 py-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">API - {{ props.api_id }}</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Manage your API settings</p>
                </div>
                
                
            </div>
            <div class="flex justify-end items-center gap-2 mb-6">
                <!-- Save Button -->
                <Button 
                    @click="handleSave"
                    variant="outline"
                    class="flex items-center gap-2"
                >   
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                    </svg>
                    Save
                </Button>
                
                <!-- Dropdown Button -->
                <div ref="dropdownRef" class="relative">
                    <Button 
                        @click="toggleDevelopersDropdown"
                        variant="outline"
                        class="flex items-center gap-2"
                    >   
                     Options
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </Button>
                    
                    <!-- Dropdown Menu -->
                    <div v-if="showDevelopersDropdown" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50">
                        <div class="py-1">
                            <button 
                                @click="handleDevelopersAction('export')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Export
                            </button>
                            <button 
                                @click="handleDevelopersAction('import')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Import
                            </button>
                            <div class="border-t border-gray-200 dark:border-gray-700"></div>
                            <button 
                                @click="handleDevelopersAction('versions')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Versions
                            </button>
                            <button 
                                @click="handleDevelopersAction('instances')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Instances
                            </button>
                            <div class="border-t border-gray-200 dark:border-gray-700"></div>
                            <button 
                                @click="handleDevelopersAction('publish')"
                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                Publish (new version)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center mb-6">
                <Button 
                    @click="openApiDevelopersModal"
                    variant="outline"
                    class="flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Manage Developers
                </Button>
            </div>

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

        <!-- API Developers Bottom Sheet Modal -->
        <BottomSheet 
            :isOpen="showApiDevelopersModal"
            title="API Developers"
            maxHeight="85vh"
            @close="closeApiDevelopersModal"
        >
            <ApiDevelopers 
                :api_id="props.api_id"
                :api_type="props.api_type"
                :apiData="apiData"
                :loadingApiData="loadingApiData"
                :apiError="apiError"
                :updateApiData="updateApiData"
                :refreshApiData="refreshApiData"
            />
        </BottomSheet>
    </AppLayout>
</template>