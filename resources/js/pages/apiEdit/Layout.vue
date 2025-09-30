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
import ApiDevelopers from '@/components/apiEdit/ApiDevelopers.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import AlertModal from '@/components/AlertModal.vue'
import FormViewer from '@/components/formviewer/FormViewer.vue'
import { useToaster } from '@/composables/useToaster'


interface Props {
    api_type: string
    api_id: string
    activeTab: string
}

const props = defineProps<Props>()

// Toaster
const { success, error: showError, warning, info } = useToaster()

// Versions modal state
const showVersionsModal = ref(false)
const versions = ref<any[]>([])
const loadingVersions = ref(false)
const versionsError = ref('')

// View version modal state
const showViewVersionModal = ref(false)
const selectedVersion = ref<any>(null)
const loadingVersionDetails = ref(false)
const versionDetailsError = ref('')

// Diff view state

// Publish modal state
const showPublishModal = ref(false)
const publishFormData = ref({
    summary: '',
    description: ''
})
const publishFormConfig = ref({
    label: 'Publish New Version',
    description: 'Enter details for the new version',
    files: false,
    fields: [
        {
            name: 'summary',
            label: 'Summary',
            type: 'text',
            required: true,
            width: '12',
            placeholder: 'Brief summary of this version'
        },
        {
            name: 'description',
            label: 'Description',
            type: 'textarea',
            required: true,
            width: '12',
            placeholder: 'Detailed description of changes in this version'
        }
    ]
})

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
        console.log('Fetch response:', response)

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

// Fetch API versions
const fetchVersions = async () => {
    loadingVersions.value = true
    versionsError.value = ''
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/versions`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        versions.value = await response.json()
    } catch (e: any) {
        versionsError.value = e.message || 'Error fetching versions'
        showError('Failed to fetch API versions. Please try again.', 'Error')
    } finally {
        loadingVersions.value = false
    }
}

// Fetch version details for viewing
const fetchVersionDetails = async (versionId: number) => {
    loadingVersionDetails.value = true
    versionDetailsError.value = ''
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/versions/${versionId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        })

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const versionData = await response.json()
        selectedVersion.value = versionData
        return versionData
    } catch (e: any) {
        versionDetailsError.value = e.message || 'Error fetching version details'
        showError('Failed to fetch version details. Please try again.', 'Error')
        throw e
    } finally {
        loadingVersionDetails.value = false
    }
}

// Open view version modal
const openViewVersionModal = async (version: any) => {
    selectedVersion.value = version
    showViewVersionModal.value = true
    // Fetch detailed version data
    await fetchVersionDetails(version.id)
}


// Open diff modal - navigate to comparison page
const openDiffModal = async (version: any) => {
    // Check if this is the latest version by comparing with the last item in versions array
    const isLatest = versions.value.length > 0 && version.id === versions.value[versions.value.length - 1].id
    
    if (isLatest) {
        // If it's the latest version, just show regular view
        await openViewVersionModal(version)
        return
    }
    
    // Navigate to comparison page
    window.location.href = `/apis/${props.api_type}/${props.api_id}/compare/${version.id}`
}

// Check if latest version is already stable
const isLatestVersionStable = computed(() => {
    if (!apiData.value) return false
    return apiData.value.stable === true
})

// Open publish modal
const openPublishModal = () => {
    // Check if latest version is already stable
    if (isLatestVersionStable.value) {
        warning('The latest version is already published. Please make some changes to create a new version.', 'Version Already Published')
        return
    }
    
    publishFormData.value = {
        summary: '',
        description: ''
    }
    showPublishModal.value = true
}

// Publish API version
const publishApiVersion = async (formData: any) => {
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/publish`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                summary: formData.summary,
                description: formData.description
            })
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`)
        }

        const publishedVersion = await response.json()
        success('API version published successfully!', 'Version Published')
        
        // Close modal and refresh API data
        showPublishModal.value = false
        await fetchApiData()
        
    } catch (e: any) {
        showError(e.message || 'Failed to publish API version. Please try again.', 'Publish Error')
    }
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
            showVersionsModal.value = true
            fetchVersions()
            break
        case 'instances':
            console.log('Show instances')
            // Navigate to instances or show instances modal
            break
        case 'publish':
            console.log('Publish new version')
            openPublishModal()
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
                                :class="[
                                    'block w-full text-left px-4 py-2 text-sm',
                                    isLatestVersionStable 
                                        ? 'text-gray-400 dark:text-gray-500 cursor-not-allowed' 
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                                ]"
                                :disabled="isLatestVersionStable"
                            >
                                <span v-if="isLatestVersionStable">Publish (already published)</span>
                                <span v-else>Publish (new version)</span>
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

        <!-- Publish Version Modal -->
        <AlertModal
            :isOpen="showPublishModal"
            title="Publish New Version"
            @close="showPublishModal = false"
        >
            <FormViewer
                :formConfig="publishFormConfig"
                :initialData="publishFormData"
                :actions="[
                    {
                        type: 'save',
                        action: 'publish',
                        label: 'Publish Version',
                        modifiers: 'px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500/20 transition-colors'
                    },
                    {
                        type: 'cancel',
                        action: 'close',
                        label: 'Cancel',
                        modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors'
                    }
                ]"
                :actionHandler="async ({ type, action, formData }: { type: string, action: string, formData: any }) => {
                    if (action === 'publish') {
                        await publishApiVersion(formData);
                    } else if (action === 'close') {
                        showPublishModal = false;
                    }
                }"
            />
        </AlertModal>

        <!-- View Version Modal -->
        <AlertModal
            :isOpen="showViewVersionModal"
            :title="`Version Details - ${selectedVersion?.summary || 'Latest/Working'}`"
            @close="showViewVersionModal = false"
        >
            <div class="space-y-4">
                <!-- Loading State -->
                <div v-if="loadingVersionDetails" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-300">Loading version details...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="versionDetailsError" class="text-center py-8">
                    <div class="text-red-600 dark:text-red-400">
                        <p class="text-lg font-semibold">Error loading version</p>
                        <p class="text-sm">{{ versionDetailsError }}</p>
                        <button @click="fetchVersionDetails(selectedVersion.id)" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Try Again
                        </button>
                    </div>
                </div>

                <!-- Version Details -->
                <div v-else-if="selectedVersion" class="space-y-4">
                    <!-- Version Info -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <h3 class="font-medium text-gray-900 dark:text-white mb-2">Version Information</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Status:</span>
                                <span v-if="selectedVersion.stable" class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                                    Stable
                                </span>
                                <span v-else class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                    Working
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Created:</span>
                                <span class="ml-2 text-gray-900 dark:text-white">{{ new Date(selectedVersion.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Version Content (Read-only) -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Summary</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md">
                                <p class="text-gray-900 dark:text-white">{{ selectedVersion.summary || 'No summary provided' }}</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                            <div class="p-3 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md">
                                <p class="text-gray-900 dark:text-white whitespace-pre-wrap">{{ selectedVersion.description || 'No description provided' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Note about read-only -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Note:</strong> This is a read-only view of a previous version. You cannot make changes to historical versions.
                        </p>
                    </div>
                </div>
            </div>
        </AlertModal>


        <!-- API Versions Modal -->
        <AlertModal
            :isOpen="showVersionsModal"
            title="API Versions"
            @close="showVersionsModal = false"
        >
            <div class="space-y-4">
                <!-- Loading State -->
                <div v-if="loadingVersions" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-300">Loading versions...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="versionsError" class="text-center py-8">
                    <div class="text-red-600 dark:text-red-400">
                        <p class="text-lg font-semibold">Error loading versions</p>
                        <p class="text-sm">{{ versionsError }}</p>
                        <button @click="fetchVersions" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Try Again
                        </button>
                    </div>
                </div>

                <!-- Versions List -->
                <div v-else-if="versions.length > 0" class="space-y-3">
                    <div 
                        v-for="(version, index) in versions" 
                        :key="version.id"
                        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
                        :class="{
                            'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800': version.stable,
                            'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800': !version.stable && index === versions.length - 1
                        }"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    {{ version.summary || 'Latest/Working' }}
                                    <span v-if="version.stable" class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                                        Stable
                                    </span>
                                    <span v-else-if="index === versions.length - 1" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                        Latest
                                    </span>
                                </h3>
                                <p v-if="version.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ version.description }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ new Date(version.created_at).toLocaleDateString() }}
                                    </p>
                                </div>
                                <button 
                                    @click="openDiffModal(version)"
                                    class="px-3 py-1 text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 rounded-md transition-colors"
                                >
                                    {{ index === versions.length - 1 ? 'View' : 'Compare' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8 text-gray-600 dark:text-gray-300">
                    <p>No versions found for this API.</p>
                </div>
            </div>
        </AlertModal>
    </AppLayout>
</template>