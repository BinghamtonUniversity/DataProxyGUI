<script setup lang="ts">
import { onMounted, ref, computed, onUnmounted, watch, h } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, Api, ApiData, ApiInstance, Environment } from '@/types'
import { Button } from '@/components/ui/button'

import Routes from '@/components/apiEdit/Routes.vue'
import Resources from '@/components/apiEdit/Resources.vue'
import Functions from '@/components/apiEdit/Functions.vue'
import Models from '@/components/apiEdit/Models.vue'
import Options from '@/components/apiEdit/Options.vue'
import Files from '@/components/apiEdit/Files.vue'
import ApiDevelopers from '@/components/apiEdit/ApiDevelopers.vue'
import BottomSheet from '@/components/BottomSheet.vue'
import AlertModal from '@/components/AlertModal.vue'
import FormViewer from '@/components/formviewer/FormViewer.vue'
import { useToaster } from '@/composables/useToaster'
import Toaster from '@/components/toaster/Toaster.vue'
import { getCsrfToken } from '@/lib/utils'

interface Props {
    api_id: string
    activeTab?: string 
}

const props = defineProps<Props>()

// Local state for active tab (client-side only)
const currentTab = ref(props.activeTab || 'routes')

// Track unsaved changes
const hasUnsavedChanges = ref(false)
const originalApiData = ref<ApiData | null>(null)

// Toaster
const { success, error: showError, warning, info } = useToaster()

const showVersionsModal = ref(false)
const versions = ref<any[]>([])
const loadingVersions = ref(false)
const versionsError = ref('')

const showViewVersionModal = ref(false)
const selectedVersion = ref<any>(null)
const loadingVersionDetails = ref(false)
const versionDetailsError = ref('')

const showPublishModal = ref(false)
const publishFormData = ref({
    summary: '',
    description: ''
})

const showInstancesModal = ref(false)
const instances = ref<ApiInstance[]>([])
const loadingInstances = ref(false)
const instancesError = ref('')

const showSearchModal = ref(false)
const searchQuery = ref('')
const searchResults = ref<any[]>([])
const isVersionSwitch = ref(false)
const isSearching = ref(false)

const showApiDataImportModal = ref(false)
const apiDataImportJson = ref('')

const showApiDevelopersModal = ref(false)
const showDevelopersDropdown = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const apiData = ref<ApiData | null>(null)
const api = ref<Api | null>(null)
const environment = ref<Environment[]>([])
const loadingApiData = ref(true)
const apiError = ref('')
const apiBaseUrl = '/api'
const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL 
const hermesBaseUrl = import.meta.env.VITE_HERMES_BASEURL

const highlightQuery = ref<string>('')
const highlightTarget = ref<string>('')

// Ref to access Functions component for validation checking
const functionsComponentRef = ref<InstanceType<typeof Functions> | null>(null)

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: `API Edit`,
        href: `/apis/${props.api_id}/routes`,
    },
]

// Tab configuration
const tabs = [
    { 
        id: 'routes', 
        title: 'Routes', 
        component: Routes
    },
    { 
        id: 'resources', 
        title: 'Resources', 
        component: Resources
    },
    { 
        id: 'functions', 
        title: 'Functions', 
        component: Functions
    },
    { 
        id: 'models', 
        title: 'Models', 
        component: Models
    },
    { 
        id: 'files', 
        title: 'Files', 
        component: Files
    },
    { 
        id: 'options', 
        title: 'Options', 
        component: Options
    }
]

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

const apiDataImportFormConfig = ref({
    label: 'Import API Data',
    description: 'Import complete API data from JSON',
    files: false,
    fields: [
        {
            name: 'jsonData',
            label: 'API Data JSON',
            type: 'monaco',
            required: true,
            width: '12',
            placeholder: 'Paste your complete API data JSON here...',
            help: 'Paste the complete JSON configuration for your API data',
            info: 'The JSON should contain all API data including routes, resources, functions, models, and files',
            language: 'json',
            height: 400
        }
    ]
})

const fetchApi = async () => {
    const response = await fetch(`/ajax/apis/${props.api_id}`)
    if (!response.ok) throw new Error('Failed to fetch API')
    api.value = await response.json()
}

const fetchEnvironment = async () => {
    const response = await fetch(`/api/environments`)
    if (!response.ok) throw new Error('Failed to fetch Environment')
    environment.value = await response.json()
}

const fetchApiData = async () => {
    loadingApiData.value = true
    apiError.value = ''
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/versions/latest`)
        if (!response.ok) {
            const errorData = await response.json()
            throw new Error(errorData.error || 'Failed to fetch API data')
        }
        const data = await response.json()
        apiData.value = data
        originalApiData.value = JSON.parse(JSON.stringify(data))
        hasUnsavedChanges.value = false
    } catch (e: any) {
        apiError.value = e.message || 'Error fetching API data'
        apiData.value = null
    } finally {
        loadingApiData.value = false
    }
}

watch(apiData, (newVal, oldVal) => {
    // Guard against null values
    if (!newVal || !originalApiData.value || !oldVal) {
        hasUnsavedChanges.value = false
        return
    }
    
    try {
        hasUnsavedChanges.value = JSON.stringify(newVal) !== JSON.stringify(originalApiData.value)
    } catch (error) {
        console.error('Error comparing API data:', error)
        hasUnsavedChanges.value = false
    }
}, { deep: true })


const updateApiData = (updatedApiData: ApiData) => {
    apiData.value = updatedApiData
}

const refreshApiData = () => {
    fetchApi()
    fetchApiData()
}


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

const fetchInstances = async () => {
    loadingInstances.value = true
    instancesError.value = ''
    try {
        const response = await fetch(`/api/api_instances`, {
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

        instances.value = await response.json()
        instances.value = instances.value.filter((instance: any) => instance.api_id === api.value?.id)
    } catch (e: any) {
        instancesError.value = e.message || 'Error fetching instances'
        showError('Failed to fetch API instances. Please try again.', 'Error')
    } finally {
        loadingInstances.value = false
    }
}

const viewInstance = (instance: any) => {
    const instanceUrl = `${window.location.origin}/api_instances/${instance.id}/main`
    window.open(instanceUrl, '_blank')
}

const directToInstanceRoute = (instance: any) => {
    let instanceUrl;
    if (api.value?.api_type === 'php') {
         instanceUrl = `${hermesBaseUrl}/api_instances/${instance.id}/main`
    } else {
        let domain = environment.value.find((env: Environment) => env.id === instance.environment_id)?.domain
        const baseDomain = domain?.split('/').slice(0, 3).join('/')
        // TODO https or http?
        instanceUrl = `http://${baseDomain}/${instance.route}`
    }
    window.open(instanceUrl, '_blank')
}

const performSearch = () => {
    if (!apiData.value || !searchQuery.value.trim()) {
        searchResults.value = []
        return
    }

    isSearching.value = true
    const query = searchQuery.value.toLowerCase().trim()
    const results: any[] = []
    const data = apiData.value as any

    const searchInArray = (items: any[], category: string, type: string) => {
        if (!items || !Array.isArray(items)) return

        items.forEach((item: any) => {
            const name = item.name || item.title || item.function_name || item.route_name || item.model_name || item.file_name || item.view_name || item.url_name
            const nameMatch = name && name.toLowerCase().includes(query)
            const content = item.content || item.description || item.path || ''
            const contentMatch = content && content.toLowerCase().includes(query)
            const otherFields = [
                item.verb,
                item.model_name,
                item.type,
                ...(item.required || []),
                ...(item.optional || [])
            ].filter(Boolean)
            const otherFieldsMatch = otherFields.some(field => 
                field && field.toString().toLowerCase().includes(query)
            )
            
            if (nameMatch || contentMatch || otherFieldsMatch) {
                results.push({
                    category: category,
                    type: type,
                    name: name || 'Unnamed',
                    description: item.description || item.path || item.content || 'No description',
                    data: item
                })
            }
        })
    }

    searchInArray(data.version_urls || [], 'Routes', 'route')
    searchInArray(data.resources || [], 'Resources', 'resource')
    searchInArray(data.version_views || [], 'Functions', 'function')
    searchInArray(data.version_models || [], 'Models', 'model')
    searchInArray(data.version_files || [], 'Files', 'file')

    const topLevelFields = [
        { value: data.summary, label: 'Summary' },
        { value: data.description, label: 'Description' }
    ].filter(field => field.value)
    
    topLevelFields.forEach(field => {
        if (field.value && field.value.toLowerCase().includes(query)) {
            results.push({
                category: 'API Info',
                type: 'info',
                name: field.label,
                description: field.value,
                data: { field: field.label, value: field.value }
            })
        }
    })

    searchResults.value = results
    isSearching.value = false
}

const openSearchModal = () => {
    showSearchModal.value = true
    searchQuery.value = ''
    searchResults.value = []
}

const closeSearchModal = () => {
    showSearchModal.value = false
    searchQuery.value = ''
    searchResults.value = []
}

const handleSearchResultClick = (result: any) => {
    const currentSearchQuery = searchQuery.value
    closeSearchModal()
    
    highlightQuery.value = currentSearchQuery
    highlightTarget.value = result.name
    
    switch (result.category) {
        case 'Routes':
            navigateToTab('routes')
            break
        case 'Resources':
            navigateToTab('resources')
            break
        case 'Functions':
            navigateToTab('functions')
            break
        case 'Models':
            navigateToTab('models')
            break
        case 'Files':
            navigateToTab('files')
            break
        case 'API Info':
            info(`Found in API ${result.name}: ${result.description}`)
            return
    }
    
    if (result.category === 'Routes' || result.category === 'Resources') {
        setTimeout(() => {
            const element = document.querySelector(`[data-${result.category.toLowerCase()}-name="${result.name}"]`)
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' })
                element.classList.add('search-highlight-item')
                setTimeout(() => {
                    element.classList.remove('search-highlight-item')
                }, 3000)
            }
        }, 500)
    }
    
    if (result.category === 'Functions' || result.category === 'Files') {
        setTimeout(() => {
            window.dispatchEvent(new CustomEvent('search-result-selected', {
                detail: {
                    category: result.category,
                    type: result.type,
                    name: result.name,
                    data: result.data,
                    searchQuery: currentSearchQuery
                }
            }))
        }, 1000)
    }
}

const getGroupedResults = () => {
    const grouped: { [key: string]: any[] } = {}
    
    searchResults.value.forEach(result => {
        if (!grouped[result.category]) {
            grouped[result.category] = []
        }
        grouped[result.category].push(result)
    })
    
    return Object.keys(grouped).map(category => ({
        name: category,
        items: grouped[category]
    }))
}

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

const switchToVersion = async (version: any) => {
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/versions/${version.id}`, {
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
        isVersionSwitch.value = true
        const versionData = await response.json()
        updateApiData(versionData)
        originalApiData.value = JSON.parse(JSON.stringify(versionData))
        hasUnsavedChanges.value = true
        showVersionsModal.value = false
        success(`Switched to version: ${version.summary || 'Latest/Working'}`, 'Version Switched')
        
    } catch (e: any) {
        showError(e.message || 'Failed to switch to version. Please try again.', 'Switch Error')
    }
}

const switchToLatestVersion = async () => {
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/versions/latest`, {
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
        isVersionSwitch.value = false
        const versionData = await response.json()
        updateApiData(versionData)
        originalApiData.value = JSON.parse(JSON.stringify(versionData))
        hasUnsavedChanges.value = true
        showVersionsModal.value = false
        success('Switched back to latest version', 'Version Switched')
        
    } catch (e: any) {
        showError(e.message || 'Failed to switch to latest version. Please try again.', 'Switch Error')
    }
}

const openViewVersionModal = async (version: any) => {
    selectedVersion.value = version
    showViewVersionModal.value = true
    await fetchVersionDetails(version.id)
}

const openDiffModal = async (version: any) => {
    const isLatest = versions.value.length > 0 && version.id === versions.value[versions.value.length - 1].id
    
    if (isLatest) {
        await switchToLatestVersion()
        return
    }
    
    window.location.href = `/apis/${props.api_id}/compare/${version.id}`
}

const isLatestVersionStable = computed(() => {
    if (!apiData.value) return false
    return apiData.value.stable === true
})

const currentVersionName = computed(() => {
    if (!apiData.value) return 'Loading...'
    return apiData.value.summary || (apiData.value.stable ? 'Latest' : 'Latest/Working')
})

const openPublishModal = () => {
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

const publishApiVersion = async (formData: any) => {
    if (isVersionSwitch.value) {
        const confirmed = confirm('You have switched to a different version. Are you sure you want to publish?')
        if (!confirmed) {
            isVersionSwitch.value = false
            return
        }
    }
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
        showPublishModal.value = false
        await fetchApiData()
        
    } catch (e: any) {
        showError(e.message || 'Failed to publish API version. Please try again.', 'Publish Error')
    }
}

const openApiDevelopersModal = () => {
    showApiDevelopersModal.value = true
}

const closeApiDevelopersModal = () => {
    showApiDevelopersModal.value = false
}

const toggleDevelopersDropdown = () => {
    showDevelopersDropdown.value = !showDevelopersDropdown.value
}

const handleDevelopersAction = (action: string) => {
    showDevelopersDropdown.value = false
    
    switch (action) {
        case 'export':
            const exportUrl = `http://127.0.0.1:8001/apis/${props.api_id}/version/latest`
            window.open(exportUrl, '_blank')
            break
        case 'import':
            openApiDataImportModal()
            break
        case 'versions':
            showVersionsModal.value = true
            fetchVersions()
            break
        case 'instances':
            showInstancesModal.value = true
            fetchEnvironment()
            fetchInstances()
            break
        case 'publish':
            openPublishModal()
            break
        default:
            console.log('Unknown action:', action)
    }
}

const openApiDataImportModal = () => {
    showApiDataImportModal.value = true
    apiDataImportJson.value = JSON.stringify(apiData.value, null, 2)
}

const closeApiDataImportModal = () => {
    showApiDataImportModal.value = false
    apiDataImportJson.value = ''
}

const handleApiDataImport = (formData: any) => {
    try {
        const importedData = JSON.parse(formData.jsonData)
        
        if (!importedData || typeof importedData !== 'object') {
            throw new Error('Invalid JSON structure')
        }
        
        const requiredFields = ['version_urls', 'version_views', 'version_models', 'version_files', 'resources']
        const missingFields = requiredFields.filter(field => !importedData.hasOwnProperty(field))
        
        if (missingFields.length > 0) {
            throw new Error(`Missing required fields: ${missingFields.join(', ')}`)
        }
        updateApiData(importedData)
        originalApiData.value = JSON.parse(JSON.stringify(importedData))
        hasUnsavedChanges.value = true
        closeApiDataImportModal()
        success('API data imported successfully!', 'Import Successful')
        
    } catch (error: any) {
        showError(error.message || 'Invalid JSON format. Please check your JSON and try again.', 'Import Error')
    }
}

const handleSave = async () => {
    if (
        !apiData.value ||
        !apiData.value.version_views ||
        !Array.isArray(apiData.value.version_views)
    ) {
        showError('No version views found to save.')
        return
    }
    
    // Check for validation errors in Functions component (regardless of current tab)
    if (functionsComponentRef.value) {
        const hasErrors = functionsComponentRef.value.hasValidationErrors
        const errorCount = functionsComponentRef.value.validationErrors
        
        if (hasErrors) {
            showError(`Cannot save: There ${errorCount === 1 ? 'is' : 'are'} ${errorCount} validation error${errorCount === 1 ? '' : 's'} in the Functions tab. Please fix the errors before saving.`, 'Validation Errors')
            return
        }
    }
    
    if (isVersionSwitch.value) {
        const confirmed = confirm('You have switched to a different version. Are you sure you want to save?')
        if (!confirmed) {
            isVersionSwitch.value = false
            return
        }
    }

    const emptyViews = apiData.value.version_views.filter(
        (view: any) => !view.content || view.content.trim() === ''
    )

    if (emptyViews.length > 0) {
        const emptyNames = emptyViews.map((v: any) => v.name || '(Unnamed View)').join(', ')
        showError(`The following functions have empty content: ${emptyNames}`)
        return
    }

    const response = await fetch(`/ajax/apis/${props.api_id}/code`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken() || '',
        },
        body: JSON.stringify(apiData.value)
    })

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        showError(errorData.message || `HTTP error! status: ${response.status}`)
        return
    }
    success('API data saved successfully!', 'API Data Saved')
    const responseData = await response.json()
    
    updateApiData(responseData)

    originalApiData.value = JSON.parse(JSON.stringify(responseData))
    hasUnsavedChanges.value = false
}

// UPDATED: Client-side only tab navigation (no XHR)
const navigateToTab = (tabId: string) => {
    currentTab.value = tabId
    // props.activeTab? = tabId 
    // This uses History API to update the URL without triggering navigation
    const newUrl = `/apis/${props.api_id}/${tabId}`
    window.history.pushState({ tab: tabId }, '', newUrl)
}

// Get current active component based on local state
const activeComponent = computed(() => {
    return tabs.find(tab => tab.id === currentTab.value)?.component || tabs[0].component
})

// Component props to pass down
const componentProps = computed(() => ({
    api_id: props.api_id,
    api_type: 'python',
    api: api.value,
    apiData: apiData.value || null,
    loadingApiData: loadingApiData.value,
    apiError: apiError.value || "",
    updateApiData,
    refreshApiData,
    handleSave,
    highlightQuery: highlightQuery.value || "",
    highlightTarget: highlightTarget.value || ""
}))


const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (hasUnsavedChanges.value) {
        event.preventDefault()
        // @ts-ignore
        event.returnValue = ''
    }
}

let removeInertiaHook: (() => void) | null = null
let keydownHandler: ((event: KeyboardEvent) => void) | null = null
let clickHandler: ((event: MouseEvent) => void) | null = null

// Handle browser back/forward buttons
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

onMounted(() => {
    fetchApi()
    fetchApiData()
    
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
    
    clickHandler = (event: MouseEvent) => {
        const target = event.target as HTMLElement
        
        if (dropdownRef.value && !dropdownRef.value.contains(target)) {
            showDevelopersDropdown.value = false
        }
    }
    
    document.addEventListener('keydown', keydownHandler)
    document.addEventListener('click', clickHandler)
    window.addEventListener('beforeunload', handleBeforeUnload)
    window.addEventListener('popstate', handlePopState)
    
    // Keep Inertia hook for navigation AWAY from this page
    removeInertiaHook = router.on('before', (event) => {
        const targetUrl = event.detail.visit.url.pathname
        
        // Check if navigating to a different page (not just a tab change)
        if (!targetUrl.startsWith(`/apis/${props.api_id}/`)) {
            if (hasUnsavedChanges.value) {
                const confirmed = confirm('You have unsaved changes. Are you sure you want to leave?')
                if (!confirmed) {
                    return false
                }
            }
        }
    })
})

onUnmounted(() => {
    
    if (keydownHandler) {
        document.removeEventListener('keydown', keydownHandler)
    }
    if (clickHandler) {
        document.removeEventListener('click', clickHandler)
    }
    window.removeEventListener('beforeunload', handleBeforeUnload)
    window.removeEventListener('popstate', handlePopState)
    if (removeInertiaHook) {
        removeInertiaHook()
    }
})
</script>

<style>
.search-highlight-item {
    background-color: #ffeb3b !important;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}
</style>

<template>
    <Head :title="' API Edit'" />
    <Toaster />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="px-4 py-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">API - {{ api?.name }}</h1>
                        <div class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200 text-xs font-medium rounded-md border border-blue-200 dark:border-blue-800">
                            {{ api?.api_type }}
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Manage your API settings</p>
                </div>
                
                <!-- Version Display -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Version:</span>
                    <div :class="[
                        'px-3 py-1 text-sm font-medium rounded-md border',
                        apiData?.summary 
                            ? 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700'
                            : 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 border-green-200 dark:border-green-800'
                    ]">
                        {{ currentVersionName }}
                    </div>
                </div>
            </div>
            <div class="flex justify-end items-center gap-2 mb-6">
                <!-- Search Button -->
                <Button 
                    @click="openSearchModal"
                    variant="outline"
                    class="flex items-center gap-2"
                >   
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </Button>
                <!-- Manage Developers Button -->
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

                <div class="flex-1">
                    <section v-if="apiData && originalApiData && !loadingApiData" class="w-full space-y-12">
                        
                        <div v-show="currentTab === 'routes'">
                            <Routes v-bind="componentProps" />
                        </div>
                        <div v-show="currentTab === 'resources'">
                            <Resources v-bind="componentProps" />
                        </div>
                        <div v-show="currentTab === 'functions'">
                            <Functions ref="functionsComponentRef" v-bind="componentProps" />
                        </div>
                        <div v-show="currentTab === 'models' && api?.api_type !== 'php'">
                            <Models v-bind="componentProps" />
                        </div>
                        <div v-show="currentTab === 'files'">
                            <Files v-bind="componentProps" />
                        </div>
                        <div v-show="currentTab === 'options'">
                            <Options v-bind="componentProps" />
                        </div>
                        <!-- Dynamic component rendering -->
                        <!-- <component
                            :is="activeComponent"
                            v-bind="componentProps"
                        /> -->
                    </section>

                    <!-- Loading state -->
                        <div v-else-if="loadingApiData" class="flex justify-center items-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3 text-gray-600 dark:text-gray-300">Loading API data...</span>
                        </div>
                        
                        <!-- Error state -->
                        <div v-else-if="apiError" class="text-center py-8">
                        <div class="text-red-600 dark:text-red-400">
                            <p class="text-lg font-semibold">{{ apiError }}</p>
                            <p class="text-sm">Couldn't load API data.</p>
                            <button @click="fetchApiData" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Try Again
                            </button>
                        </div>
                        </div>
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
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ new Date(version.created_at).toLocaleDateString() }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    <button 
                                        @click="openDiffModal(version)"
                                        class="px-3 py-1 text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:hover:bg-blue-800 rounded-md transition-colors"
                                    >
                                        {{ index === versions.length - 1 ? 'View' : 'Compare' }}
                                    </button>
                                    <button 
                                        v-if="version.stable && index !== versions.length - 1"
                                        @click="switchToVersion(version)"
                                        class="px-3 py-1 text-xs bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-200 dark:hover:bg-orange-800 rounded-md transition-colors"
                                    >
                                        Switch
                                    </button>
                                </div>
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

        <!-- API Instances Modal -->
        <AlertModal
            :isOpen="showInstancesModal"
            title="API Instances"
            @close="showInstancesModal = false"
        >
            <div class="space-y-4">
                <!-- Loading State -->
                <div v-if="loadingInstances" class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600 dark:text-gray-300">Loading instances...</span>
                </div>

                <!-- Error State -->
                <div v-else-if="instancesError" class="text-center py-8">
                    <div class="text-red-600 dark:text-red-400">
                        <p class="text-lg font-semibold">Error loading instances</p>
                        <p class="text-sm">{{ instancesError }}</p>
                        <button @click="fetchInstances" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Try Again
                        </button>
                    </div>
                </div>

                <!-- Instances List -->
                <div v-else-if="instances.length > 0" class="space-y-3">
                    <div 
                        v-for="instance in instances" 
                        :key="instance.id"
                        class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900 dark:text-white">
                                    {{ instance.name || `Instance ${instance.id}` }}
                                </h3>
                                <div class="flex items-center space-x-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    <span v-if="instance.environment_id" class="px-2 py-1 text-xs bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded">
                                        {{ environment?.find((env: Environment) => env.id === instance.environment_id)?.name }}
                                    </span>
                                    <span v-if="instance.route" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded font-mono">
                                        {{ instance.route }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center space-x-3">
                                <button 
                                    @click="viewInstance(instance)"
                                    class="px-3 py-1 text-xs bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-200 dark:hover:bg-orange-800 rounded-md transition-colors"
                                >
                                    Edit
                                </button>
                                <button 
                                    @click="directToInstanceRoute(instance)"
                                    class="px-3 py-1 text-xs bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900 dark:text-green-200 dark:hover:bg-orange-800 rounded-md transition-colors"
                                >
                                    Visit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No instances found for this API.</p>
                </div>
            </div>
        </AlertModal>

        <!-- Search Modal -->
        <AlertModal
            :isOpen="showSearchModal"
            title="Search API Data"
            @close="closeSearchModal"
        >
            <div class="space-y-4">
                <!-- Search Input -->
                <div class="flex gap-2">
                    <input
                        v-model="searchQuery"
                        @input="performSearch"
                        @keyup.enter="performSearch"
                        type="text"
                        placeholder="Search routes, resources, functions, models, and files..."
                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <Button 
                        @click="performSearch"
                        :disabled="!searchQuery.trim()"
                        class="px-4 py-2"
                    >
                        Search
                    </Button>
                </div>

                <!-- Search Results -->
                <div v-if="searchResults.length > 0" class="space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Search Results ({{ searchResults.length }} found)
                    </h3>
                    
                    <!-- Group results by category -->
                    <div v-for="category in getGroupedResults()" :key="category.name" class="space-y-2">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 pb-1">
                            {{ category.name }} ({{ category.items.length }})
                        </h4>
                        <div class="space-y-2">
                            <div 
                                v-for="result in category.items" 
                                :key="`${result.type}-${result.name}`"
                                @click="handleSearchResultClick(result)"
                                class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h5 class="font-medium text-gray-900 dark:text-white">
                                            {{ result.name }}
                                        </h5>
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            {{ result.description }}
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded">
                                            {{ result.type }}
                                        </span>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Results -->
                <div v-else-if="searchQuery && !isSearching" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No results found for "{{ searchQuery }}"</p>
                </div>

                <!-- Search Prompt -->
                <div v-else-if="!searchQuery" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>Enter a search term to find items across routes, resources, functions, models, and files.</p>
                </div>
            </div>
        </AlertModal>

        <!-- API Data Import Modal -->
        <AlertModal
            :isOpen="showApiDataImportModal"
            title="Import API Data"
            width="sm:max-w-4xl"
            @close="closeApiDataImportModal"
        >
            <FormViewer
                :formConfig="apiDataImportFormConfig"
                :initialData="{ jsonData: apiDataImportJson }"
                :actions="[
                    {
                        type: 'save',
                        action: 'import',
                        label: 'Import API Data',
                        modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors'
                    },
                    {
                        type: 'cancel',
                        action: 'close',
                        label: 'Cancel',
                        modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors'
                    }
                ]"
                :actionHandler="async ({ type, action, formData }: { type: string, action: string, formData: any }) => {
                    if (action === 'import') {
                        handleApiDataImport(formData);
                    } else if (action === 'close') {
                        closeApiDataImportModal();
                    }
                }"
            />
        </AlertModal>
    </AppLayout>
</template>