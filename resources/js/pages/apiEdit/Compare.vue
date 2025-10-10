<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiData } from '@/types'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { CodeDiff } from 'v-code-diff'

// Import your components (convert them to pure components)
import Routes from '@/components/apiEdit/Routes.vue'
import Resources from '@/components/apiEdit/Resources.vue'
import Functions from '@/components/apiEdit/Functions.vue'
import Models from '@/components/apiEdit/Models.vue'
import Options from '@/components/apiEdit/Options.vue'
import Files from '@/components/apiEdit/Files.vue'

interface Props {
    api_type: string
    api_id: string
    version_id: string
}

const props = defineProps<Props>()

// Data fetching logic
const currentApiData = ref<ApiData | null>(null)
const selectedApiData = ref<ApiData | null>(null)
const loading = ref(true)
const error = ref('')

// Resizable panels
const leftPanelWidth = ref(50)
const rightPanelWidth = ref(50)
const isResizing = ref(false)

// Force update mechanism
const forceUpdate = ref(0)
const triggerUpdate = () => {
    forceUpdate.value++
}

// Selected function/file for diff view
const selectedFunctionIndex = ref<number | null>(null)
const selectedFileIndex = ref<number | null>(null)
const selectedFunction = computed(() => {
    if (selectedFunctionIndex.value === null || !functionDiffData.value) return null
    return {
        current: functionDiffData.value.current?.[selectedFunctionIndex.value],
        selected: functionDiffData.value.selected?.[selectedFunctionIndex.value]
    }
})
const selectedFile = computed(() => {
    if (selectedFileIndex.value === null || !fileDiffData.value) return null
    return {
        current: fileDiffData.value.current?.[selectedFileIndex.value],
        selected: fileDiffData.value.selected?.[selectedFileIndex.value]
    }
})

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
}

const fetchCurrentVersion = async () => {
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/versions/latest`)
        if (!response.ok) throw new Error('Failed to fetch current version')
        currentApiData.value = await response.json()
    } catch (e: any) {
        error.value = e.message || 'Error fetching current version'
    }
}

const fetchSelectedVersion = async () => {
    try {
        const response = await fetch(`/ajax/apis/${props.api_id}/versions/${props.version_id}`)
        if (!response.ok) throw new Error('Failed to fetch selected version')
        selectedApiData.value = await response.json()
    } catch (e: any) {
        error.value = e.message || 'Error fetching selected version'
    }
}

const fetchAllData = async () => {
    loading.value = true
    error.value = ''
    try {
        await Promise.all([
            fetchCurrentVersion(),
            fetchSelectedVersion()
        ])
    } catch (e: any) {
        error.value = e.message || 'Error fetching version data'
    } finally {
        loading.value = false
    }
}

// Tab configuration
const tabs = [
    { 
        id: 'routes', 
        title: 'Routes'
    },
    { 
        id: 'resources', 
        title: 'Resources'
    },
    { 
        id: 'functions', 
        title: 'Functions'
    },
    { 
        id: 'models', 
        title: 'Models'
    },
    { 
        id: 'files', 
        title: 'Files'
    },
    { 
        id: 'options', 
        title: 'Options'
    }
]

const activeTab = ref('routes')

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'API Edit',
        href: `/apis/${props.api_type}/${props.api_id}/routes`,
    },
    {
        title: 'Version Comparison',
        href: '#',
    },
]

// Component props for current version
const currentComponentProps = computed(() => ({
    api_id: props.api_id,
    api_type: props.api_type,
    apiData: currentApiData.value,
    loadingApiData: loading.value,
    apiError: error.value,
    updateApiData: (newData: any) => {
        // Update the current API data when changes are made
        if (currentApiData.value) {
            // Handle specific updates for different data types
            if (newData.version_urls) {
                currentApiData.value.version_urls = newData.version_urls
            }
            if (newData.resources) {
                currentApiData.value.resources = newData.resources
            }
            if (newData.version_views) {
                currentApiData.value.version_views = newData.version_views
            }
            if (newData.version_models) {
                currentApiData.value.version_models = newData.version_models
            }
            if (newData.options) {
                currentApiData.value.options = newData.options
            }
            // Merge any other properties
            currentApiData.value = { ...currentApiData.value, ...newData }
        } else {
            currentApiData.value = newData
        }
        // Trigger force update
        triggerUpdate()
    },
    refreshApiData: async () => {
        // Refresh the current version data
        await fetchCurrentVersion()
        triggerUpdate()
    },
    // Add force update key to trigger reactivity
    forceUpdate: forceUpdate.value
}))

// Component props for selected version (read-only)
const selectedComponentProps = computed(() => ({
    api_id: props.api_id,
    api_type: props.api_type,
    apiData: selectedApiData.value,
    loadingApiData: loading.value,
    apiError: error.value,
    updateApiData: () => {},
    refreshApiData: () => {},
    // Add read-only flags to disable all actions
    isReadOnly: true,
    disableActions: true,
    viewOnly: true
}))

// Get current active component
const activeComponent = computed(() => {
    switch (activeTab.value) {
        case 'routes': return Routes
        case 'resources': return Resources
        case 'functions': return Functions
        case 'models': return Models
        case 'files': return Files
        case 'options': return Options
        default: return Routes
    }
})

// Check if we should show diff view for functions and files
const shouldShowDiffView = computed(() => {
    return activeTab.value === 'functions' || activeTab.value === 'files'
})

// Check if we should show JSON comparison for options
const shouldShowJsonComparison = computed(() => {
    return activeTab.value === 'options'
})

// Generate side-by-side diff for functions
const functionSideBySideDiff = computed(() => {
    if (!functionDiffData.value) return null
    
    const currentFunctions = functionDiffData.value.current || []
    const selectedFunctions = functionDiffData.value.selected || []
    
    const diffResults = []
    
    // Compare functions by name or index
    const maxFunctions = Math.max(currentFunctions.length, selectedFunctions.length)
    
    for (let i = 0; i < maxFunctions; i++) {
        const currentFunc = currentFunctions[i]
        const selectedFunc = selectedFunctions[i]
        
        if (currentFunc && selectedFunc) {
            // Both functions exist, generate diff data
            const diffData = generateFunctionDiffData(currentFunc, selectedFunc)
            diffResults.push({
                currentFunc,
                selectedFunc,
                diffData,
                type: 'both'
            })
        } else if (currentFunc) {
            // Only current function exists (added)
            diffResults.push({
                currentFunc,
                selectedFunc: null,
                diff: null,
                type: 'added'
            })
        } else if (selectedFunc) {
            // Only selected function exists (removed)
            diffResults.push({
                currentFunc: null,
                selectedFunc,
                diff: null,
                type: 'removed'
            })
        }
    }
    
    return diffResults
})

// Function diff data
const functionDiffData = computed(() => {
    if (!currentApiData.value || !selectedApiData.value) return null
    
    const currentFunctions = currentApiData.value.version_views || []
    const selectedFunctions = selectedApiData.value.version_views || []
    
    return {
        current: currentFunctions,
        selected: selectedFunctions
    }
})

// File diff data
const fileDiffData = computed(() => {
    if (!currentApiData.value || !selectedApiData.value) return null
    
    const currentFiles = currentApiData.value.version_files || []
    const selectedFiles = selectedApiData.value.version_files || []
    
    return {
        current: currentFiles,
        selected: selectedFiles
    }
})

// Options JSON comparison data
const optionsJsonData = computed(() => {
    if (!currentApiData.value || !selectedApiData.value) return null
    
    const currentOptions = currentApiData.value.options || {}
    const selectedOptions = selectedApiData.value.options || {}
    
    return {
        current: JSON.stringify(currentOptions, null, 2),
        selected: JSON.stringify(selectedOptions, null, 2)
    }
})

// Generate diff data for v-code-diff component
const generateFunctionDiffData = (currentFunc: any, selectedFunc: any) => {
    if (!currentFunc || !selectedFunc) return null
    
    return {
        oldCode: selectedFunc.content || selectedFunc.code || '',
        newCode: currentFunc.content || currentFunc.code || '',
        language: 'javascript', // or detect from function type
        filename: currentFunc.name || 'function'
    }
}

// Navigation helper
const navigateToTab = (tabId: string) => {
    activeTab.value = tabId
    // Reset selected function and file when switching tabs
    selectedFunctionIndex.value = null
    selectedFileIndex.value = null
}

// Function selection handlers
const selectFunction = (index: number) => {
    selectedFunctionIndex.value = index
}

const backToFunctionList = () => {
    selectedFunctionIndex.value = null
}

// File selection handlers
const selectFile = (index: number) => {
    selectedFileIndex.value = index
}

const backToFileList = () => {
    selectedFileIndex.value = null
}

// Go back to API edit
const goBack = () => {
    router.get(`/apis/${props.api_id}/${activeTab.value}`)
}

// Resizing functions
const startResize = (e: MouseEvent) => {
    isResizing.value = true
    e.preventDefault()
    
    const handleMouseMove = (e: MouseEvent) => {
        if (!isResizing.value) return
        
        const container = document.querySelector('.flex-1.flex.overflow-hidden') as HTMLElement
        if (!container) return
        
        const containerRect = container.getBoundingClientRect()
        const mouseX = e.clientX - containerRect.left
        const containerWidth = containerRect.width
        
        // Calculate new widths (leave 1% for the divider)
        const newLeftWidth = (mouseX / containerWidth) * 100
        const newRightWidth = 100 - newLeftWidth - 1 // 1% for divider
        
        // Constrain to reasonable limits (10% to 80%)
        const constrainedLeftWidth = Math.max(10, Math.min(80, newLeftWidth))
        const constrainedRightWidth = 100 - constrainedLeftWidth - 1
        
        leftPanelWidth.value = constrainedLeftWidth
        rightPanelWidth.value = constrainedRightWidth
    }
    
    const handleMouseUp = () => {
        isResizing.value = false
        document.removeEventListener('mousemove', handleMouseMove)
        document.removeEventListener('mouseup', handleMouseUp)
    }
    
    document.addEventListener('mousemove', handleMouseMove)
    document.addEventListener('mouseup', handleMouseUp)
}

// Custom event handlers for read-only mode
const handleReadOnlyEvent = (event: Event) => {
    event.preventDefault()
    event.stopPropagation()
    return false
}

// Add event listeners to disable all interactions on selected version
const addReadOnlyListeners = () => {
    const selectedPanel = document.querySelector('.selected-version-panel')
    if (selectedPanel) {
        // Disable all form interactions
        const forms = selectedPanel.querySelectorAll('form')
        forms.forEach(form => {
            form.addEventListener('submit', handleReadOnlyEvent)
        })
        
        // Disable all button clicks
        const buttons = selectedPanel.querySelectorAll('button')
        buttons.forEach(button => {
            button.addEventListener('click', handleReadOnlyEvent)
        })
        
        // Disable all input interactions
        const inputs = selectedPanel.querySelectorAll('input, textarea, select')
        inputs.forEach(input => {
            input.addEventListener('change', handleReadOnlyEvent)
            input.addEventListener('input', handleReadOnlyEvent)
            input.addEventListener('keydown', handleReadOnlyEvent)
        })
    }
}

// Watch for tab changes to re-apply read-only listeners
watch(activeTab, () => {
    setTimeout(addReadOnlyListeners, 100)
})

// Watch for changes in current API data to trigger updates
watch(currentApiData, (newData) => {
    if (newData) {
        // Force reactivity update
        currentApiData.value = { ...newData }
    }
}, { deep: true })

// Fetch data on mount
onMounted(() => {
    fetchAllData()
    // Add read-only listeners after component mounts
    setTimeout(addReadOnlyListeners, 100)
})
</script>

<template>
    <Head title="API Version Comparison" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="h-screen flex flex-col">
            <!-- Header -->
            <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">API Version Comparison</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Compare current version with version {{ props.version_id }}</p>
                    </div>
                    <Button @click="goBack" variant="outline">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to API Edit
                    </Button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4">
                <nav class="flex w-full">
                    <Button
                        v-for="tab in tabs"
                        :key="tab.id"
                        variant="ghost"
                        :class="[
                            'flex-1 px-4 py-2 rounded-t-md text-center transition-colors',
                            { 'bg-muted font-semibold': activeTab === tab.id }
                        ]"
                        @click="navigateToTab(tab.id)"
                    >
                        {{ tab.title }}
                    </Button>
                </nav>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600 dark:text-gray-300">Loading version comparison...</p>
                </div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <div class="text-red-600 dark:text-red-400">
                        <p class="text-lg font-semibold">Error loading comparison</p>
                        <p class="text-sm">{{ error }}</p>
                        <Button @click="fetchAllData" class="mt-4">
                            Try Again
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Comparison Content -->
            <div v-else class="flex-1 flex overflow-hidden" :class="{ 'select-none': isResizing }">
                <!-- JSON Comparison View for Options -->
                <div v-if="shouldShowJsonComparison" class="flex-1 flex flex-col overflow-hidden">


                    <!-- JSON Comparison Content -->
                    <div class="flex-1 overflow-auto">
                        <div class="p-4">
                            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-medium text-gray-900 dark:text-white">Options JSON Comparison</h4>
                                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                                <span>Selected (Historical)</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                                                <span>Current (Latest)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white dark:bg-gray-900">
                                    <CodeDiff
                                        :old-string="optionsJsonData?.selected || '{}'"
                                        :new-string="optionsJsonData?.current || '{}'"
                                        :language="'json'"
                                        :context="10"
                                        :output-format="'side-by-side'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Side-by-Side Diff View for Functions and Files -->
                <div v-else-if="shouldShowDiffView" class="flex-1 flex flex-col overflow-hidden">
                    <!-- Header -->
                    <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <button 
                                    v-if="(activeTab === 'functions' && selectedFunctionIndex !== null) || (activeTab === 'files' && selectedFileIndex !== null)"
                                    @click="activeTab === 'functions' ? backToFunctionList() : backToFileList()"
                                    class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                    Back to {{ activeTab === 'functions' ? 'Functions' : 'Files' }}
                                </button>
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        {{ (activeTab === 'functions' && selectedFunctionIndex !== null) || (activeTab === 'files' && selectedFileIndex !== null) ? `${activeTab === 'functions' ? 'Function' : 'File'} Comparison` : `${activeTab === 'functions' ? 'Function' : 'File'} List` }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <span v-if="(activeTab === 'functions' && selectedFunctionIndex !== null) || (activeTab === 'files' && selectedFileIndex !== null)">
                                            Comparing {{ activeTab === 'functions' ? 'function' : 'file' }} {{ activeTab === 'functions' ? selectedFunctionIndex + 1 : selectedFileIndex + 1 }}
                                        </span>
                                        <span v-else>
                                            {{ activeTab === 'functions' ? (functionDiffData?.current?.length || 0) : (fileDiffData?.current?.length || 0) }} {{ activeTab === 'functions' ? 'functions' : 'files' }} available for comparison
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="px-2 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-xs font-medium rounded">
                                    Selected
                                </div>
                                <div class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded">
                                    Current
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Function/File List View -->
                    <div v-if="(activeTab === 'functions' && selectedFunctionIndex === null) || (activeTab === 'files' && selectedFileIndex === null)" class="flex-1 overflow-auto">
                        <!-- Functions List -->
                        <div v-if="activeTab === 'functions'">
                            <div v-if="!functionDiffData || functionDiffData.current?.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No functions to compare
                            </div>
                            <div v-else class="p-4">
                                <div class="grid gap-4">
                                    <div 
                                        v-for="(func, index) in functionDiffData.current" 
                                        :key="index"
                                        @click="selectFunction(index)"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 cursor-pointer hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-200"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h5 class="font-medium text-gray-900 dark:text-white mb-1">
                                                    {{ func.name || `Function ${index + 1}` }}
                                                </h5>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                    {{ func.content?.substring(0, 100) || 'No description' }}{{ func.content?.length > 100 ? '...' : '' }}
                                                </p>
                                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                    <span>{{ (func.content || '').split('\n').length }} lines</span>
                                                    <span v-if="functionDiffData.selected?.[index]" class="text-green-600 dark:text-green-400">
                                                        Has comparison data
                                                    </span>
                                                    <span v-else class="text-gray-400 dark:text-gray-500">
                                                        No comparison data
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Files List -->
                        <div v-else-if="activeTab === 'files'">
                            <div v-if="!fileDiffData || fileDiffData.current?.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                No files to compare
                            </div>
                            <div v-else class="p-4">
                                <div class="grid gap-4">
                                    <div 
                                        v-for="(file, index) in fileDiffData.current" 
                                        :key="index"
                                        @click="selectFile(index)"
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 cursor-pointer hover:border-blue-300 dark:hover:border-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all duration-200"
                                    >
                                        <div class="flex items-center justify-between">
                                            <div class="flex-1">
                                                <h5 class="font-medium text-gray-900 dark:text-white mb-1">
                                                    {{ file.name || `File ${index + 1}` }}
                                                </h5>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                    {{ file.content?.substring(0, 100) || 'No description' }}{{ file.content?.length > 100 ? '...' : '' }}
                                                </p>
                                                <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                                    <span>{{ (file.content || '').split('\n').length }} lines</span>
                                                    <span v-if="fileDiffData.selected?.[index]" class="text-green-600 dark:text-green-400">
                                                        Has comparison data
                                                    </span>
                                                    <span v-else class="text-gray-400 dark:text-gray-500">
                                                        No comparison data
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Function/File Diff View -->
                    <div v-else class="flex-1 overflow-auto">
                        <!-- Function Diff View -->
                        <div v-if="activeTab === 'functions' && selectedFunction" class="h-full">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden m-4">
                                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <h5 class="font-medium text-gray-900 dark:text-white">
                                        {{ selectedFunction.current?.name || `Function ${selectedFunctionIndex + 1}` }}
                                    </h5>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ selectedFunction.current?.content || 'No description' }}
                                    </p>
                                </div>
                                <div class="bg-white dark:bg-gray-900">
                                    <CodeDiff
                                        :old-string="selectedFunction.selected?.content || ''"
                                        :new-string="selectedFunction.current?.content || ''"
                                        :language="'javascript'"
                                        :context="10"
                                        :output-format="'side-by-side'"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- File Diff View -->
                        <div v-else-if="activeTab === 'files' && selectedFile" class="h-full">
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden m-4">
                                <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                                    <h5 class="font-medium text-gray-900 dark:text-white">
                                        {{ selectedFile.current?.name || `File ${selectedFileIndex + 1}` }}
                                    </h5>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ selectedFile.current?.content || 'No description' }}
                                    </p>
                                </div>
                                <div class="bg-white dark:bg-gray-900">
                                    <CodeDiff
                                        :old-string="selectedFile.selected?.content || ''"
                                        :new-string="selectedFile.current?.content || ''"
                                        :language="'javascript'"
                                        :context="10"
                                        :output-format="'side-by-side'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Normal Comparison View for Other Tabs -->
                <div v-else-if="!shouldShowJsonComparison" class="flex-1 flex overflow-hidden">
                <!-- Left Panel - Selected Version (Historical) -->
                <div 
                    class="overflow-hidden selected-version-panel"
                    :style="{ width: leftPanelWidth + '%' }"
                >
                    <div class="h-full flex flex-col">
                        <!-- Selected Version Header -->
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="font-medium text-yellow-900 dark:text-yellow-100">Selected Version (Historical)</h3>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                {{ selectedApiData?.summary || 'Historical Version' }}
                            </p>
                        </div>
                        
                        <!-- Selected Version Content -->
                        <div class="flex-1 overflow-auto relative">
                            <!-- Read-only overlay -->
                            <div class="absolute inset-0 bg-gray-50/50 dark:bg-gray-900/50 z-10 pointer-events-none"></div>
                            
                            <div class="opacity-75 pointer-events-none select-none">
                                <component
                                    :is="activeComponent"
                                    :key="`selected-${activeTab}`"
                                    v-bind="selectedComponentProps"
                                />
                            </div>
                            
                            <!-- Read-only notice -->
                            <div class="absolute bottom-4 left-4 z-20">
                                <div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-3 py-2 rounded-lg shadow-lg text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        Historical version - view only
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resizable Divider -->
                <div 
                    class="w-1 bg-gray-300 dark:bg-gray-600 hover:bg-blue-400 dark:hover:bg-blue-500 cursor-col-resize transition-colors flex-shrink-0 relative group"
                    :class="{ 'bg-blue-400 dark:bg-blue-500': isResizing }"
                    @mousedown="startResize"
                >
                    <!-- Drag Handle -->
                    <div class="absolute inset-y-0 -left-2 -right-2 flex items-center justify-center">
                        <div class="w-4 h-12 bg-gray-400 dark:bg-gray-500 rounded-md opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center shadow-sm">
                            <div class="flex flex-col space-y-1">
                                <div class="w-0.5 h-1 bg-white rounded-full"></div>
                                <div class="w-0.5 h-1 bg-white rounded-full"></div>
                                <div class="w-0.5 h-1 bg-white rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Panel - Current Version (Latest) -->
                <div 
                    class="overflow-hidden"
                    :style="{ width: rightPanelWidth + '%' }"
                >
                    <div class="h-full flex flex-col">
                        <!-- Current Version Header -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-blue-900 dark:text-blue-100">Current Version (Latest)</h3>
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        {{ currentApiData?.summary || 'Latest/Working' }}
                                    </p>
                                </div>
                                <div class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded">
                                    Latest
                                </div>
                            </div>
                        </div>
                        
                        <!-- Current Version Content -->
                        <div class="flex-1 overflow-auto">
                            <div v-if="shouldShowDiffView" class="h-full">
                                <!-- Function/File Diff View -->
                                <div class="h-full flex flex-col">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 border-b border-gray-200 dark:border-gray-700">
                                        <h4 class="font-medium text-blue-900 dark:text-blue-100">Current {{ activeTab === 'functions' ? 'Functions' : 'Files' }}</h4>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            {{ activeTab === 'functions' ? (functionDiffData?.current?.length || 0) : (fileDiffData?.current?.length || 0) }} {{ activeTab === 'functions' ? 'functions' : 'files' }}
                                        </p>
                                    </div>
                                    <div class="flex-1 overflow-auto">
                                        <!-- Functions Content -->
                                        <div v-if="activeTab === 'functions'">
                                            <div v-if="!functionDiffData || functionDiffData.current?.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                                No functions in current version
                                            </div>
                                            <div v-else class="space-y-6 p-4">
                                                <div 
                                                    v-for="(func, index) in functionDiffData.current" 
                                                    :key="index"
                                                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                                >
                                                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                                        <h5 class="font-medium text-gray-900 dark:text-white">{{ func.name || `Function ${index + 1}` }}</h5>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ func.content || 'No description' }}</p>
                                                    </div>
                                                    <div class="bg-gray-50 dark:bg-gray-900">
                                                        <div class="bg-gray-100 dark:bg-gray-800 px-3 py-1 text-xs text-gray-600 dark:text-gray-400 border-b flex items-center justify-between">
                                                            <span>Current Version</span>
                                                            <span class="text-xs">{{ (func.content || '').split('\n').length }} lines</span>
                                                        </div>
                                                        <div class="overflow-x-auto">
                                                            <table class="w-full text-sm">
                                                                <tbody>
                                                                    <tr v-for="(line, lineIndex) in (func.content || '').split('\n')" :key="lineIndex">
                                                                        <td class="w-12 px-2 py-1 text-right text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 select-none">
                                                                            {{ lineIndex + 1 }}
                                                                        </td>
                                                                        <td class="px-3 py-1 font-mono text-gray-900 dark:text-white whitespace-pre">
                                                                            {{ line }}
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Files Content -->
                                        <div v-else-if="activeTab === 'files'">
                                            <div v-if="!fileDiffData || fileDiffData.current?.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                                No files in current version
                                            </div>
                                            <div v-else class="space-y-6 p-4">
                                                <div 
                                                    v-for="(file, index) in fileDiffData.current" 
                                                    :key="index"
                                                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                                >
                                                    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-2 border-b border-gray-200 dark:border-gray-700">
                                                        <h5 class="font-medium text-gray-900 dark:text-white">{{ file.name || `File ${index + 1}` }}</h5>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ file.content || 'No description' }}</p>
                                                    </div>
                                                    <div class="bg-gray-50 dark:bg-gray-900">
                                                        <div class="bg-gray-100 dark:bg-gray-800 px-3 py-1 text-xs text-gray-600 dark:text-gray-400 border-b flex items-center justify-between">
                                                            <span>Current Version</span>
                                                            <span class="text-xs">{{ (file.content || '').split('\n').length }} lines</span>
                                                        </div>
                                                        <div class="overflow-x-auto">
                                                            <table class="w-full text-sm">
                                                                <tbody>
                                                                    <tr v-for="(line, lineIndex) in (file.content || '').split('\n')" :key="lineIndex">
                                                                        <td class="w-12 px-2 py-1 text-right text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 select-none">
                                                                            {{ lineIndex + 1 }}
                                                                        </td>
                                                                        <td class="px-3 py-1 font-mono text-gray-900 dark:text-white whitespace-pre">
                                                                            {{ line }}
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <component
                                v-else
                                :is="activeComponent"
                                :key="`current-${activeTab}-${forceUpdate}`"
                                v-bind="currentComponentProps"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </AppLayout>
</template>
