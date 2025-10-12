<script setup lang="ts">
import { onMounted, ref, onUnmounted, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiData, ApiInstance, ApiUser, Resource } from '@/types'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'

// Import your components (convert them to pure components)
import Main from '@/components/apiInstanceDetails/Main.vue'
import Resources from '@/components/apiInstanceDetails/Resources.vue'
import Options from '@/components/apiInstanceDetails/Options.vue'
import Permissions from '@/components/apiInstanceDetails/Permissions.vue'
import AlertModal from '@/components/AlertModal.vue'
import FormViewer from '@/components/formviewer/FormViewer.vue'
import { useToaster } from '@/composables/useToaster'
import { getCsrfToken } from '@/lib/utils'

interface Props {
    instance_id: string
    activeTab: string
}

const props = defineProps<Props>()

// Toaster
const { success, error: showError, warning, info } = useToaster()

// Version management state
const showVersionModal = ref(false)
const versions = ref<any[]>([])
const loadingVersions = ref(false)
const versionsError = ref('')


const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Instance',
        href: `/api_instances/${props.instance_id}/main`,
    },
]

// Tab configuration
const tabs = [
    { 
        id: 'main', 
        title: 'Main', 
        component: Main,
        routeName: 'apiInstanceEdit.index'
    },
    { 
        id: 'resources', 
        title: 'Resources', 
        component: Resources,
        routeName: 'apiInstanceEdit.index'
    },
    { 
        id: 'permissions', 
        title: 'Permissions', 
        component: Permissions,
        routeName: 'apiInstanceEdit.index'
    },
    { 
        id: 'options', 
        title: 'Options', 
        component: Options,
        routeName: 'apiInstanceEdit.index'
    }
]

// Data fetching logic - runs once when component mounts
const apiInstanceData = ref<ApiInstance | null>(null)
const apiUsers = ref<ApiUser | null>(null)
const resources = ref<Resource | null>(null)


const loading = ref(true)
const apiInstanceError = ref('')


// Track unsaved changes
const hasUnsavedChanges = ref(false)
const originalApiInstanceData = ref<ApiInstance | null>(null)

// Not being used currently, but might be useful later
// Using fetchAllData instead
const fetchApiInstanceData = async () => {
    loading.value = true
    apiInstanceError.value = ''
    try {
        const response = await fetch(`/ajax/api_instances/${props.instance_id}`)
        // console.log('Fetch response:', response)
        if (!response.ok) throw new Error('Failed to fetch API Instance data')
        const data = await response.json()
        apiInstanceData.value = data

        originalApiInstanceData.value = JSON.parse(JSON.stringify(data)) // Deep clone
        hasUnsavedChanges.value = false

    } catch (e: any) {
        apiInstanceError.value = e.message || 'Error fetching API Instance data'
        apiInstanceData.value = null
    } finally {
        loading.value = false
    }
}

const fetchAllData = async () => {
  loading.value = true
  try {
    // Fetch the API instance to get the environment type
    const apiInstancesResponse = await fetch(`/ajax/api_instances/${props.instance_id}`)
    if (!apiInstancesResponse.ok) throw new Error('Failed to fetch API instances')
    
    const apiInstancesData = await apiInstancesResponse.json()
    apiInstanceData.value = apiInstancesData

    originalApiInstanceData.value = JSON.parse(JSON.stringify(apiInstancesData)) // Deep clone
    hasUnsavedChanges.value = false
    
    const environmentType = apiInstancesData.environment?.type || 'dev' // fallback to 'dev'
    
    const [
      apiUsersResponse,
      resourcesResponse,
    ] = await Promise.all([
      fetch(`/api/api_users`),
      fetch(`/ajax/resources/type/${environmentType}`), // Now dynamic!
    ])

    if (!apiUsersResponse.ok) throw new Error('Failed to fetch API users')
    if (!resourcesResponse.ok) throw new Error('Failed to fetch resources')

    const [
      apiUsersData,
      resourcesData,
    ] = await Promise.all([
      apiUsersResponse.json(),
      resourcesResponse.json(),
    ])

    apiUsers.value = apiUsersData
    resources.value = resourcesData

  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    loading.value = false
  }
}

// Watch for changes in apiInstanceData to track unsaved changes
// watch(apiInstanceData, (newVal) => {
//     if (originalApiInstanceData.value && newVal) {
//         // Compare to detect changes
//         hasUnsavedChanges.value = JSON.stringify(newVal) !== JSON.stringify(originalApiInstanceData.value)
//     }
// }, { deep: true })

const updateApiInstanceData = (updatedApiInstanceData: Partial<ApiInstance>) => {
    if(!apiInstanceData) return
    
    apiInstanceData.value = { 
        ...apiInstanceData.value, 
        ...updatedApiInstanceData,
    } as ApiInstance
}

const refreshApiInstanceData = () => {
    fetchApiInstanceData()
}

// Fetch API versions for the instance's API
const fetchVersions = async () => {
    if (!apiInstanceData.value?.api?.id) {
        showError('No API associated with this instance', 'Error')
        return
    }
    
    loadingVersions.value = true
    versionsError.value = ''
    try {
        const response = await fetch(`/ajax/apis/${apiInstanceData.value.api.id}/versions`, {
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

// Open version modal
const openVersionModal = () => {
    showVersionModal.value = true
    fetchVersions()
}

// Update API version for the instance
const updateInstanceVersion = async (version: any) => {
    try {
        const response = await fetch(`/ajax/api_instances/${props.instance_id}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                api_version_id: version.id
            })
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`)
        }

        const updatedInstance = await response.json()
        success('API version updated successfully!', 'Version Updated')
        
        // Close modal and refresh data
        showVersionModal.value = false
        await fetchAllData()
        
    } catch (e: any) {
        showError(e.message || 'Failed to update API version. Please try again.', 'Update Error')
    }
}

// Add a method to check for changes
const checkForUnsavedChanges = () => {
    if (originalApiInstanceData.value && apiInstanceData.value) {
        return JSON.stringify(apiInstanceData.value) !== JSON.stringify(originalApiInstanceData.value)
    }
    return false
}

// Navigation helper
const navigateToTab = (tabId: string) => {
    hasUnsavedChanges.value = checkForUnsavedChanges()
    if (hasUnsavedChanges.value) {
        if (confirm('You have unsaved changes. Do you want to leave?')) {
            // Proceed with tab change
            router.get(`/api_instances/${props.instance_id}/${tabId}`, {}, {
                preserveState: true,
                preserveScroll: true,
                // only: ['activeTab'] // Only update the activeTab prop
            })
        } else {
            // Cancel tab change
            return
        }
    }
    router.get(`/api_instances/${props.instance_id}/${tabId}`, {}, {
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
    instance_id: props.instance_id,
    apiInstanceData: apiInstanceData.value,
    apiUsers: apiUsers.value,
    resources: resources.value,
    loading: loading.value,
    apiInstanceError: apiInstanceError.value,
    updateApiInstanceData,
    // refreshApiInstanceData
}))

const handleSave = async() => {

    const requestData = {
            id: apiInstanceData.value?.id,
            name: apiInstanceData.value?.name,
            route: apiInstanceData.value?.route, 
            route_user_map: apiInstanceData.value?.route_user_map,
            resources: apiInstanceData.value?.resources, 
            options: apiInstanceData.value?.options,
            public: apiInstanceData.value?.public,
            api_id: apiInstanceData.value?.api.id,
            api_version_id: apiInstanceData.value?.api_version_id,
            environment_id: apiInstanceData.value?.environment.id
        }
  
    const response = await fetch(`/ajax/api_instances/${props.instance_id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken() || '',
        },
        body: JSON.stringify(requestData)
    })

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        showError(errorData.message || `HTTP error! status: ${response.status}`)
        return
    }
    success('API Instance data saved successfully!')
    const responseData = await response.json()
    apiInstanceData.value = responseData

    // Reset dirty state after successful save
    originalApiInstanceData.value = JSON.parse(JSON.stringify(responseData))
    hasUnsavedChanges.value = false
}

// // Browser/tab close warning
// const handleBeforeUnload = (event: BeforeUnloadEvent) => {
//     if (hasUnsavedChanges.value) {
//         event.preventDefault()
//         // use @ts-ignore to avoid type error
//         // @ts-ignore
//         event.returnValue = '' // Chrome requires returnValue to be set but use @ts-ignore to avoid type error
//     }
// }

// // Inertia navigation warning
// let removeInertiaHook: (() => void) | null = null

let keydownHandler: ((event: KeyboardEvent) => void) | null = null
// let clickHandler: ((event: MouseEvent) => void) | null = null

// Fetch data on mount
onMounted(() => {
        fetchAllData()
        
        keydownHandler = (event: KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault()
                handleSave()
            }
        }

        document.addEventListener('keydown', keydownHandler)
        // Add beforeunload listener
        // window.addEventListener('beforeunload', handleBeforeUnload)
        
        // // Add Inertia navigation hook
        // removeInertiaHook = router.on('before', (event) => {
        //         if (hasUnsavedChanges.value) {
        //             // Show confirmation dialog
        //             const confirmed = confirm('You have unsaved changes. Are you sure you want to leave?')
        //             if (!confirmed) {
        //                 return false // Cancel navigation
        //             }
        //         }
        //     })
        }
)

onUnmounted(() => {
    if (keydownHandler) {
        document.removeEventListener('keydown', keydownHandler)
    }

    // window.removeEventListener('beforeunload', handleBeforeUnload)
    // if (removeInertiaHook) {
    //     removeInertiaHook()
    // }
})

</script>

<template>
    <Head title="API Instace Details" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="px-4 py-6">
            <Heading :title="`API Instance - ${props.instance_id}`" description="Manage your API Instance" />
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

                <!-- Version Management Button -->
                <div class="flex justify-end mb-4">
                    <Button 
                        @click="openVersionModal"
                        variant="outline"
                        class="flex items-center gap-2"
                        :disabled="!apiInstanceData?.api?.id"
                    >   
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2M9 12l2 2 4-4"></path>
                        </svg>
                        Change API Version
                    </Button>
                </div>

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

        <!-- Version Management Modal -->
        <AlertModal
            :isOpen="showVersionModal"
            title="Change API Version"
            @close="showVersionModal = false"
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
                        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                        :class="{
                            'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800': version.stable,
                            'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800': !version.stable && index === versions.length - 1,
                            'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800': apiInstanceData?.api_version_id === version.id
                        }"
                        @click="updateInstanceVersion(version)"
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
                                    <span v-if="apiInstanceData?.api_version_id === version.id" class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                                        Current
                                    </span>
                                </h3>
                                <p v-if="version.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ version.description }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(version.created_at).toLocaleDateString() }}
                                </p>
                                <p v-if="apiInstanceData?.api_version_id === version.id" class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                    Currently selected
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8 text-gray-600 dark:text-gray-300">
                    <p>No versions found for this API.</p>
                </div>

                <!-- Info Note -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Note:</strong> Click on a version to change the API version for this instance. This will update which version of the API this instance uses.
                    </p>
                </div>
            </div>
        </AlertModal>
    </AppLayout>
</template>