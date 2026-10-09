<script setup lang="ts">
import { onMounted, ref, onUnmounted, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiData, ApiInstance, ApiUser, Resource } from '@/types'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'

import Main from '@/components/apiInstanceDetails/Main.vue'
import Resources from '@/components/apiInstanceDetails/Resources.vue'
import Options from '@/components/apiInstanceDetails/Options.vue'
import Permissions from '@/components/apiInstanceDetails/Permissions.vue'
import AlertModal from '@/components/AlertModal.vue'
import FormViewer from '@/components/formviewer/FormViewer.vue'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'

import { useToaster } from '@/composables/useToaster'
import { getCsrfToken, mapPhpToApiInstance } from '@/lib/utils'
import Toaster from '@/components/toaster/Toaster.vue'
import { useProxyServer } from '@/composables/useProxyServer'


interface Props {
    server_slug: string,
    // api_type: string
    instance_id: string
    activeTab?: string
}

const props = defineProps<Props>()
const currentTab = ref(props.activeTab || 'main')

const { serverApiType } = useProxyServer();

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
const apiUsers = ref<ApiUser[] | null>([])
const resources = ref<Resource[] | null>(null)


const loading = ref(true)
const apiInstanceError = ref('')


// Track unsaved changes
const hasUnsavedChanges = ref(false)
const originalApiInstanceData = ref<ApiInstance | null>(null)

// Comment dialog (required before update request)
const commentDialogOpen = ref(false)
const commentFormRef = ref<InstanceType<typeof FormViewer> | null>(null)
const commentForm = ref({ comment: '' })
const pendingInstancePayload = ref<Record<string, any> | null>(null)
const pendingPreservedNestedData = ref<{
    api: ApiInstance['api'] | undefined
    api_version: ApiInstance['api_version'] | undefined
    environment: ApiInstance['environment'] | undefined
} | null>(null)
const saving = ref(false)

const commentFormConfig = {
    label: '',
    description: '',
    name: 'api-instance-edit-comment-form',
    showLabel: false,
    files: false,
    fields: [
        {
            name: 'comment',
            label: 'Comment',
            type: 'textarea',
            placeholder: 'Describe why this change is being made',
            value: '',
            required: true,
        },
    ],
}

const clearPendingInstanceSave = () => {
    pendingInstancePayload.value = null
    pendingPreservedNestedData.value = null
    commentForm.value = { comment: '' }
}

const closeCommentDialog = () => {
    if (saving.value) return
    commentDialogOpen.value = false
    clearPendingInstanceSave()
}

const handleCommentFormAction = (actionData: { type: string; action: string; formData: any }) => {
    switch (actionData.type) {
        case 'close':
        case 'cancel':
            closeCommentDialog()
            break
        case 'save':
            submitInstanceWithComment(actionData.formData)
            break
        default:
            warning('Unknown FormViewer action type:', actionData.type)
    }
}

const deepClone = <T>(value: T): T => JSON.parse(JSON.stringify(value))

// Not being used currently, but might be useful later
// Using fetchAllData instead
const fetchApiInstanceData = async () => {
    loading.value = true
    apiInstanceError.value = ''
    try {
        const response = await fetch(`/${props.server_slug}/ajax/api_instances/${props.instance_id}`)
 
        if (!response.ok) throw new Error('Failed to fetch API Instance data')
        const data = await response.json()
        apiInstanceData.value = serverApiType.value === 'php'
            ? mapPhpToApiInstance(data)
            : data

        originalApiInstanceData.value = deepClone(apiInstanceData.value)
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
    const apiInstancesResponse = await fetch(`/${props.server_slug}/ajax/api_instances/${props.instance_id}`)
    if (!apiInstancesResponse.ok) throw new Error('Failed to fetch API instances')
    
    const apiInstancesData = await apiInstancesResponse.json()
    apiInstanceData.value = serverApiType.value === 'php'
        ? mapPhpToApiInstance(apiInstancesData)
        : apiInstancesData
    // Clone what's actually in the form, not the raw response
    originalApiInstanceData.value = deepClone(apiInstanceData.value)
    hasUnsavedChanges.value = false
    
    const environmentType = apiInstancesData.environment?.type || 'dev' // fallback to 'dev'
    
    const [
      apiUsersResponse,
      resourcesResponse,
    ] = await Promise.all([
      fetch(`/${props.server_slug}/api/api_users`),
      fetch(`/${props.server_slug}/ajax/resources/type/${environmentType}`), 
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
watch(apiInstanceData, (newVal) => {
    if (originalApiInstanceData.value && newVal) {
        // Compare to detect changes
        hasUnsavedChanges.value = JSON.stringify(newVal) !== JSON.stringify(originalApiInstanceData.value)

    }
}, { deep: true })

// function findDiffs(a: any, b: any, path = ''): any[] {
//   if (a === b) return []
//   const isObj = (v: any) => v !== null && typeof v === 'object'
//   if (!isObj(a) || !isObj(b)) {
//     return [{ path: path || '(root)', original: a, current: b, origType: typeof a, currType: typeof b }]
//   }
//   const keys = new Set([...Object.keys(a), ...Object.keys(b)])
//   return [...keys].flatMap(k => findDiffs(a[k], b[k], path ? `${path}.${k}` : k))
// }

// watch(apiInstanceData, (newVal) => {
//   if (originalApiInstanceData.value && newVal) {
//     const diffs = findDiffs(toRaw(originalApiInstanceData.value), toRaw(newVal))
//     if (diffs.length) console.table(diffs)
//     else if (JSON.stringify(newVal) !== JSON.stringify(originalApiInstanceData.value)) {
//       console.warn('Same values, different key order')
//     }
//     hasUnsavedChanges.value = JSON.stringify(newVal) !== JSON.stringify(originalApiInstanceData.value)
//   }
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
        const response = await fetch(`/${props.server_slug}/ajax/apis/${apiInstanceData.value.api.id}/versions`, {
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

        // versions.value = await response.json()
        const stableVersions = await response.json()

        // The backend only returns stable versions; "latest" means api_version_id = null
        const current = apiInstanceData.value
        const onLatest = current?.api_version_id == null
        const latestVersion = {
            id: null,
            isLatest: true,
            summary: 'Latest (Working or Published)',
            description: 'Latest working version of this API',
            stable: false,
            // We only know latest's details if the instance is currently on it
            created_at: onLatest ? current?.api_version?.created_at ?? null : null,
        }

        versions.value = [latestVersion, ...stableVersions]
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

const isCurrentVersion = (version: any) =>
    (apiInstanceData.value?.api_version_id ?? null) === version.id

// Update API version for the instance
const updateInstanceVersion = async (version: any) => {
    if (isCurrentVersion(version)) {
        showVersionModal.value = false
        return
    }

    const requestData = {
        api_version_id: version.id,
        name: apiInstanceData.value?.name,
        route: serverApiType.value === 'php'? undefined : apiInstanceData.value?.route, 
        slug: serverApiType.value === 'php'?  apiInstanceData.value?.route: undefined, 
        route_user_map: apiInstanceData.value?.route_user_map,
        resources: apiInstanceData.value?.resources, 
        options: apiInstanceData.value?.options,
        public: apiInstanceData.value?.public,
    }

    try {
        const response = await fetch(`/${props.server_slug}/ajax/api_instances/${props.instance_id}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify(requestData)
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
// const checkForUnsavedChanges = () => {
//     if (originalApiInstanceData.value && apiInstanceData.value) {
//         return JSON.stringify(apiInstanceData.value) !== JSON.stringify(originalApiInstanceData.value)
//     }
//     return false
// }

// Navigation helper
const navigateToTab = (tabId: string) => {
    currentTab.value = tabId
    
    // This uses History API to update the URL without triggering navigation
    const newUrl = `/${props.server_slug}/api_instances/${props.instance_id}/${tabId}`
    window.history.pushState({ tab: tabId }, '', newUrl)
}

// Get current active component
const activeComponent = computed(() => {
    return tabs.find(tab => tab.id === currentTab.value)?.component || tabs[0].component
})

const isNavigatingWithinSameApiInstance = (url: string): boolean => {
    // Check if the URL is navigating to a different tab of the same API
    const urlPattern = new RegExp(`^/api_instances/${props.instance_id}(/[^/]+)?$`)
    return urlPattern.test(url)
}

// Component props to pass down
const componentProps = computed(() => ({
    instance_id: props.instance_id,
    api_type: serverApiType.value as string,  
    apiInstanceData: apiInstanceData.value,
    apiUsers: apiUsers.value,
    resources: resources.value,
    loading: loading.value,
    apiInstanceError: apiInstanceError.value,
    updateApiInstanceData,
    // refreshApiInstanceData
}))

// TODO: determine if https or http is needed
const visitInstance = () => {
    if (!apiInstanceData.value) {
        showError('Instance data not loaded', 'Error')
        return
    }
    
    let instanceUrl
    const domain = apiInstanceData.value.environment?.domain
    if (!domain) {
        showError('Environment domain not found', 'Error')
        return
    }
    const baseDomain = domain.split('/').slice(0, 3).join('/')
    const path = apiInstanceData.value.route ? apiInstanceData.value.route : apiInstanceData.value.slug
    instanceUrl = `http://${baseDomain}/${path}`
    
    window.open(instanceUrl, '_blank')
}

const viewDocumentation = () => {
    window.open(`/api/api_docs/${props.instance_id}`, '_blank');
};

const handleSave = () => {
    // Store the nested objects before the API call
    const preservedNestedData = {
        api: apiInstanceData.value?.api,
        api_version: apiInstanceData.value?.api_version,
        environment: apiInstanceData.value?.environment
    }

    const requestData = {
            id: apiInstanceData.value?.id,
            name: apiInstanceData.value?.name,
            route: serverApiType.value === 'php'? undefined : apiInstanceData.value?.route, 
            slug: serverApiType.value === 'php'?  apiInstanceData.value?.route: undefined, 
            route_user_map: apiInstanceData.value?.route_user_map,
            resources: apiInstanceData.value?.resources, 
            options: apiInstanceData.value?.options,
            public: apiInstanceData.value?.public,
            api_id: apiInstanceData.value?.api.id,
            api_version_id: apiInstanceData.value?.api_version_id,
            environment_id: apiInstanceData.value?.environment.id
        }
    
    // Create comparable object from original data with same structure as requestData
    const originalRequestData = {
        id: originalApiInstanceData.value?.id,
        name: originalApiInstanceData.value?.name,
        route: serverApiType.value === 'php'? undefined : originalApiInstanceData.value?.route,
        slug: serverApiType.value === 'php'? originalApiInstanceData.value?.route : undefined,
        route_user_map: originalApiInstanceData.value?.route_user_map,
        resources: originalApiInstanceData.value?.resources,
        options: originalApiInstanceData.value?.options,
        public: originalApiInstanceData.value?.public,
        api_id: originalApiInstanceData.value?.api?.id,
        api_version_id: originalApiInstanceData.value?.api_version_id,
        environment_id: originalApiInstanceData.value?.environment?.id
    }

    const emptyResources = requestData.resources?.length !== 0 && requestData.resources?.every((r: any) => !r.name && !r.resource)
    if (emptyResources) {
        warning('All resources must be configured before saving.', 'Validation Error')
        return
    }
   
    const hasChanges = JSON.stringify(originalRequestData) !== JSON.stringify(requestData)
    if (!hasChanges) {
        info('No changes detected to save.', 'Nothing to Save')
        return
    }

    pendingInstancePayload.value = requestData
    // pendingPreservedNestedData.value = preservedNestedData
    commentForm.value = { comment: '' }
    commentDialogOpen.value = true
}

const submitInstanceWithComment = async (formData: any) => {
    if (!commentFormRef.value) {
        warning('Form is not ready. Please try again.', 'Validation Error')
        return
    }
    const isValid = commentFormRef.value.validateForm()
    if (!isValid) {
        warning('Please enter a comment before saving.', 'Validation Error')
        return
    }

    const comment = (formData?.comment ?? '').trim()
    if (!comment) {
        warning('Comment is required.', 'Validation Error')
        return
    }

    if (!pendingInstancePayload.value) {
        showError('Nothing to save. Please try again.', 'Error')
        closeCommentDialog()
        return
    }

    saving.value = true
    // const preservedNestedData = pendingPreservedNestedData.value

    try {
        const body = {
            ...pendingInstancePayload.value,
            comment,
        }

        const response = await fetch(`/${props.server_slug}/ajax/api_instances/${props.instance_id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            body: JSON.stringify(body)
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            showError(errorData.message || `HTTP error! status: ${response.status}`)
            return
        }
        success('API Instance data saved successfully!')
        const responseData = await response.json()
        
        const current = apiInstanceData.value
        if (!current) return
        // Merge response with preserved nested objects
        apiInstanceData.value = {
            ...current,
            slug: serverApiType.value === 'php'? responseData.slug : undefined,
            // Restore nested objects if they're missing in the response -- PHP doesn't return them on update PUT
            api: responseData.api ?? current.api,
            api_version: responseData.api_version ?? current.api_version,
            environment: responseData.environment ?? current.environment
        }

        // Reset dirty state after successful save
        originalApiInstanceData.value = deepClone(apiInstanceData.value)
        hasUnsavedChanges.value = false
        commentDialogOpen.value = false
        clearPendingInstanceSave()
    } catch (err: any) {
        showError(err.message || 'Error saving API Instance', 'Error')
    } finally {
        saving.value = false
    }
}

const handlePopState = (event: PopStateEvent) => {
    // Extract tab from URL
    const urlParts = window.location.pathname.split('/')
    const tabFromUrl = urlParts[urlParts.length - 1]
    
    // Check if it's a valid tab
    const validTab = tabs.find(tab => tab.id === tabFromUrl)
    if (validTab) {
        currentTab.value = validTab.id
    }
}

// Browser/tab close warning
const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (hasUnsavedChanges.value) {
        event.preventDefault()
        // use @ts-ignore to avoid type error
        // @ts-ignore
        event.returnValue = '' // Chrome requires returnValue to be set but use @ts-ignore to avoid type error
    }
}

// Inertia navigation warning
let removeInertiaHook: (() => void) | null = null

let keydownHandler: ((event: KeyboardEvent) => void) | null = null
// let clickHandler: ((event: MouseEvent) => void) | null = null

// Fetch data on mount
onMounted(() => {
        fetchAllData()

         // Initialize tab from URL
        const urlParts = window.location.pathname.split('/')
        const tabFromUrl = urlParts[urlParts.length - 1]
        const validTab = tabs.find(tab => tab.id === tabFromUrl)
        if (validTab) {
            currentTab.value = validTab.id
        }
        
        keydownHandler = (event: KeyboardEvent) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                event.preventDefault()
                handleSave()
            }
        }

        document.addEventListener('keydown', keydownHandler)
        // Add beforeunload listener
        window.addEventListener('beforeunload', handleBeforeUnload)
        window.addEventListener('popstate', handlePopState)

        
        // Add Inertia navigation hook
        removeInertiaHook = router.on('before', (event) => {
            const targetUrl = event.detail.visit.url.pathname
        
            // Check if navigating to a different page (not just a tab change)
            if (!targetUrl.startsWith(`/api_instances/${props.instance_id}/`)) {
                if (hasUnsavedChanges.value) {
                    const confirmed = confirm('You have unsaved changes. Are you sure you want to leave?')
                    if (!confirmed) {
                        return false
                    }
                }
            }
        }
        )
    })

onUnmounted(() => {
    if (keydownHandler) {
        document.removeEventListener('keydown', keydownHandler)
    }

    window.removeEventListener('beforeunload', handleBeforeUnload)
    if (removeInertiaHook) {
        removeInertiaHook()
    }
    window.removeEventListener('popstate', handlePopState)

})

const pageTitle = computed(() => {
    const name = apiInstanceData.value?.name
    if (!name) return 'API Instance Details'

    const env = apiInstanceData.value?.environment?.type
    return env ? `${name} (${env})` : name
})

</script>

<template>
    <Head :title="pageTitle" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="px-4 py-6">
            <Heading :title="`API Instance - ${props.instance_id}`" description="Manage your API Instance" />
            <div class="flex justify-end items-center gap-2 mb-6">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" class="flex items-center gap-2">
                            Actions
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="handleSave" class="flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            Save
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="visitInstance" class="flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            Visit Instance
                        </DropdownMenuItem>
                        
                        <!-- <DropdownMenuItem @click="viewDocumentation" class="flex items-center gap-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            View Documentation
                        </DropdownMenuItem> -->
                    </DropdownMenuContent>
                </DropdownMenu>
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
                            { 'bg-muted font-semibold': currentTab === tab.id }
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
                    <section v-if="apiInstanceData && !loading" class="w-full space-y-12">
                         <div v-show="currentTab === 'main'">
                            <Main v-bind="componentProps" />
                        </div>
                         <div v-show="currentTab === 'resources'">
                            <Resources v-bind="componentProps" />
                        </div>
                        <div v-show="currentTab === 'permissions'">
                            <Permissions v-bind="componentProps" />
                        </div>
                        <div v-show="currentTab === 'options'">
                            <Options v-bind="componentProps" />
                        </div>
                        <!-- <Main v-if="currentTab === 'main'" v-bind="componentProps" />
                        <Resources v-if="currentTab === 'resources'" v-bind="componentProps" />
                        <Permissions v-if="currentTab === 'permissions'" v-bind="componentProps" />
                        <Options v-if="currentTab === 'options'" v-bind="componentProps" /> -->
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
                            'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800': version.isLatest,
                            'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800': isCurrentVersion(version)
                        }"
                        @click="updateInstanceVersion(version)"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    {{ version.summary || 'Untitled version' }}
                                    <span v-if="version.stable" class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                                        Stable
                                    </span>
                                    <span v-if="version.isLatest" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                                        Latest
                                    </span>
                                    <span v-if="isCurrentVersion(version)" class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded-full">
                                        Current
                                    </span>
                                </h3>
                                <p v-if="version.description" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ version.description }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p v-if="version.created_at" class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ new Date(version.created_at).toLocaleDateString() }}
                                </p>
                                <p v-if="isCurrentVersion(version)" class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">
                                    Current
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

        <!-- Required comment before update request -->
        <AlertModal
            :isOpen="commentDialogOpen"
            title="Save comment"
            @close="() => { if (!saving) closeCommentDialog() }"
        >
            <FormViewer
                ref="commentFormRef"
                :formConfig="commentFormConfig"
                :initialData="commentForm"
                :cancelAction="'close'"
                :actionHandler="handleCommentFormAction"
                :actions="[
                    { type: 'save', action: 'save', label: 'Confirm', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                    { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                ]"
                :isSubmitting="saving"
                :disabled="saving"
            />
        </AlertModal>
    </AppLayout>
    <Toaster/>
</template>