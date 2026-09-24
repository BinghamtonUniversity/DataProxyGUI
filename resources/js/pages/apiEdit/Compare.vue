<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiData, Api } from '@/types'
import { Button } from '@/components/ui/button'

import Routes from '@/components/apiEdit/Routes.vue'
import Resources from '@/components/apiEdit/Resources.vue'
import Functions from '@/components/apiEdit/Functions.vue'
import Models from '@/components/apiEdit/Models.vue'
import Options from '@/components/apiEdit/Options.vue'
import Files from '@/components/apiEdit/Files.vue'
import { mapDjangoToApiData, mapPhpToApiData, denormalizeToPhp, getCsrfToken } from '@/lib/utils'
import { useProxyServer } from '@/composables/useProxyServer'
import { useToaster } from '@/composables/useToaster'
import Toaster from '@/components/toaster/Toaster.vue'

import MonacoDiffView from '@/components/compare/MonacoDiffView.vue'
import DiffStat from '@/components/compare/DiffStat.vue'
import {
    buildDiffEntries,
    lineStats,
    sumStats,
    languageForFile,
    type DiffEntry,
    type DiffStatus,
} from '@/composables/useVersionDiff'

interface Props {
    server_slug: string
    api?: Api
    api_id: string
    version_id: string
}

const props = defineProps<Props>()
const { serverApiType } = useProxyServer()
const { success, error: showError, info } = useToaster()

const compareDebugEnabled = () =>
    import.meta.env.DEV ||
    new URLSearchParams(window.location.search).has('compare_debug') ||
    localStorage.getItem('compare_debug') === '1'

const compareMountTime = performance.now()
const compareLog = (step: string, data?: Record<string, unknown>) => {
    if (!compareDebugEnabled()) return
    console.log(`[Compare +${(performance.now() - compareMountTime).toFixed(0)}ms] ${step}`, data ?? '')
}

const getVersionPayloadStats = (payload: any) => ({
    routes: payload?.routes?.length ?? payload?.version_urls?.length ?? 0,
    functions: payload?.functions?.length ?? payload?.version_views?.length ?? 0,
    files: payload?.files?.length ?? payload?.version_files?.length ?? 0,
    resources: payload?.resources?.length ?? 0,
})

const getApiDataStats = (data: ApiData | null) => ({
    routes: data?.version_urls?.length ?? 0,
    functions: data?.version_views?.length ?? 0,
    files: data?.version_files?.length ?? 0,
    resources: data?.resources?.length ?? 0,
})

// compareLog('setup', {
//     api_id: props.api_id,
//     version_id: props.version_id,
//     server_slug: props.server_slug,
//     serverApiType: serverApiType.value,
//     debugEnabled: compareDebugEnabled(),
// })

const api = ref<Api | null>(null)
const currentApiData = ref<ApiData | null>(null)
const selectedApiData = ref<ApiData | null>(null)
const originalApiData = ref<ApiData | null>(null)
const loading = ref(true)
const error = ref('')
const isSaving = ref(false)

// Resizable panels
const leftPanelWidth = ref(50)
const rightPanelWidth = ref(50)
const isResizing = ref(false)
const splitContainer = ref<HTMLElement | null>(null) // replaces the fragile querySelector

const forceUpdate = ref(0)
const triggerUpdate = () => {
    forceUpdate.value++
}

function normalizeApiData(payload: any, backend: 'python' | 'php'): ApiData {
    return backend === 'python' ? mapDjangoToApiData(payload) : mapPhpToApiData(payload)
}

const fetchApi = async () => {
    const url = `/${props.server_slug}/ajax/apis/${props.api_id}`
    // compareLog('fetch:api:start', { url })
    try {
        const response = await fetch(url)
        // compareLog('fetch:api:response', { url, status: response.status, ok: response.ok, contentLength: response.headers.get('content-length') })
        if (!response.ok) throw new Error('Failed to fetch API')
        const jsonStart = performance.now()
        const data = await response.json()
        // compareLog('fetch:api:jsonParsed', { durationMs: Math.round(performance.now() - jsonStart) })
        api.value = data
        // compareLog('fetch:api:done')
    } catch (e: any) {
        // compareLog('fetch:api:error', { message: e.message })
        error.value = e.message || 'Error fetching API'
    }
}

const fetchCurrentVersion = async () => {
    const url = `/${props.server_slug}/ajax/apis/${props.api_id}/versions/latest`
    // compareLog('fetch:current:start', { url })
    try {
        const response = await fetch(url)
        // compareLog('fetch:current:response', { url, status: response.status, ok: response.ok, contentLength: response.headers.get('content-length') })
        if (!response.ok) throw new Error('Failed to fetch current version')
        const jsonStart = performance.now()
        const data = await response.json()
        // compareLog('fetch:current:jsonParsed', { durationMs: Math.round(performance.now() - jsonStart), ...getVersionPayloadStats(data) })
        const normalizeStart = performance.now()
        const versionData = normalizeApiData(data, serverApiType.value as 'python' | 'php')
        // compareLog('fetch:current:normalized', { durationMs: Math.round(performance.now() - normalizeStart), ...getApiDataStats(versionData) })
        const cloneStart = performance.now()
        currentApiData.value = versionData
        originalApiData.value = JSON.parse(JSON.stringify(versionData))
        // compareLog('fetch:current:cloned', { durationMs: Math.round(performance.now() - cloneStart) })
        // compareLog('fetch:current:done')
    } catch (e: any) {
        // compareLog('fetch:current:error', { message: e.message })
        error.value = e.message || 'Error fetching current version'
    }
}

const fetchSelectedVersion = async () => {
    const url = `/${props.server_slug}/ajax/apis/versions/${props.version_id}`
    // compareLog('fetch:selected:start', { url })
    try {
        const response = await fetch(url)
        // compareLog('fetch:selected:response', { url, status: response.status, ok: response.ok, contentLength: response.headers.get('content-length') })
        if (!response.ok) throw new Error('Failed to fetch selected version')
        const jsonStart = performance.now()
        const data = await response.json()
        // compareLog('fetch:selected:jsonParsed', { durationMs: Math.round(performance.now() - jsonStart), ...getVersionPayloadStats(data) })
        const normalizeStart = performance.now()
        const versionData = normalizeApiData(data, serverApiType.value as 'python' | 'php')
        // compareLog('fetch:selected:normalized', { durationMs: Math.round(performance.now() - normalizeStart), ...getApiDataStats(versionData) })
        selectedApiData.value = versionData
        // compareLog('fetch:selected:done')
    } catch (e: any) {
        // compareLog('fetch:selected:error', { message: e.message })
        error.value = e.message || 'Error fetching selected version'
    }
}

const fetchAllData = async () => {
    // compareLog('fetchAllData:start')
    loading.value = true
    error.value = ''
    try {
        await Promise.all([fetchApi(), fetchCurrentVersion(), fetchSelectedVersion()])
        // compareLog('fetchAllData:allSettled')
    } catch (e: any) {
        // compareLog('fetchAllData:error', { message: e.message })
        error.value = e.message || 'Error fetching version data'
    } finally {
        loading.value = false
        // compareLog('fetchAllData:finally', {
        //     loading: loading.value,
        //     hasApi: !!api.value,
        //     hasCurrent: !!currentApiData.value,
        //     hasSelected: !!selectedApiData.value,
        //     error: error.value || null,
        // })
    }
}

// Tabs
const tabs = [
    { id: 'routes', title: 'Routes' },
    { id: 'resources', title: 'Resources' },
    { id: 'functions', title: 'Functions' },
    ...(serverApiType.value === 'php' ? [] : [{ id: 'models', title: 'Models' }]),
    { id: 'files', title: 'Files' },
    { id: 'options', title: 'Options' },
]

const activeTab = ref('routes')

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'API Edit', href: `/apis/${props.api_id}/routes` },
    { title: 'Version Comparison', href: '#' },
]

const currentComponentProps = computed(() => ({
    api_id: props.api_id,
    api_type: serverApiType.value as 'python' | 'php',
    api: api.value,
    apiData: currentApiData.value,
    loadingApiData: loading.value,
    apiError: error.value,
    updateApiData: (newData: any) => {
        // A plain spread covers every field the old per-key branches handled
        currentApiData.value = currentApiData.value ? { ...currentApiData.value, ...newData } : newData
        triggerUpdate()
    },
    refreshApiData: async () => {
        await fetchCurrentVersion()
        triggerUpdate()
    },
    forceUpdate: forceUpdate.value,
}))

const selectedComponentProps = computed(() => ({
    api_id: props.api_id,
    api_type: serverApiType.value as 'python' | 'php',
    api: api.value,
    apiData: selectedApiData.value,
    loadingApiData: loading.value,
    apiError: error.value,
    updateApiData: () => {},
    refreshApiData: () => {},
    isReadOnly: true,
    disableActions: true,
    viewOnly: true,
}))

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

const shouldShowDiffView = computed(() => activeTab.value === 'functions' || activeTab.value === 'files' || (activeTab.value === 'models' && serverApiType.value === 'python'))
const shouldShowJsonComparison = computed(() => activeTab.value === 'options')

// ---------- Diff data (NEW) ----------
// Old = selected version, new = current. "+" means present in current but not in selected.

const functionEntries = computed(() =>
    buildDiffEntries(currentApiData.value?.version_views ?? [], selectedApiData.value?.version_views ?? []),
)
const fileEntries = computed(() =>
    buildDiffEntries(currentApiData.value?.version_files ?? [], selectedApiData.value?.version_files ?? []),
)

const modelEntries = computed(() =>
    serverApiType.value === 'python'
        ? buildDiffEntries(
              currentApiData.value?.version_models ?? [],
              selectedApiData.value?.version_models ?? [],
          )
        : [],
)

const hideUnchanged = ref(false)
const allEntries = computed<DiffEntry[]>(() => {
    switch (activeTab.value) {
        case 'functions': return functionEntries.value
        case 'files': return fileEntries.value
        case 'models': return modelEntries.value
        default: return []
    }
})
const visibleEntries = computed(() =>
    hideUnchanged.value ? allEntries.value.filter((e) => e.status !== 'unchanged') : allEntries.value,
)
const activeTotals = computed(() => sumStats(allEntries.value))
const changedCount = computed(() => allEntries.value.filter((e) => e.status !== 'unchanged').length)

const selectedEntryKey = ref<string | null>(null)
const selectedEntry = computed(() => allEntries.value.find((e) => e.key === selectedEntryKey.value) ?? null)

const itemLabel = computed(() =>
    activeTab.value === 'functions' ? 'function' : activeTab.value === 'models' ? 'model' : 'file',
)
const diffLanguage = computed(() => {
    if (activeTab.value === 'files') return languageForFile(selectedEntry.value?.name)
    return serverApiType.value === 'php' ? 'php' : 'python'
})

const sideBySide = ref(true)

const optionsJsonData = computed(() => {
    if (!currentApiData.value || !selectedApiData.value) return null
    const current = JSON.stringify(currentApiData.value.options || {}, null, 2)
    const selected = JSON.stringify(selectedApiData.value.options || {}, null, 2)
    return { current, selected, ...lineStats(selected, current) }
})

const statusStyles: Record<DiffStatus, { label: string; class: string }> = {
    added: { label: 'Added', class: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200' },
    removed: { label: 'Removed', class: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200' },
    modified: { label: 'Modified', class: 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200' },
    unchanged: { label: 'Unchanged', class: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' },
}

// Navigation
const navigateToTab = (tabId: string) => {
    activeTab.value = tabId
    selectedEntryKey.value = null
}

const goBack = () => {
    router.get(`/${props.server_slug}/apis/${props.api_id}/${activeTab.value}`)
}

const handleSave = async () => {
    if (!currentApiData.value) {
        showError('No API data found to save.')
        return
    }

    const hasChanges = JSON.stringify(originalApiData.value) !== JSON.stringify(currentApiData.value)
    if (!hasChanges) {
        info('No changes detected to save.', 'Nothing to Save')
        return
    }

    if (isSaving.value) return
    isSaving.value = true

    try {
        const requestData = serverApiType.value === 'php' ? denormalizeToPhp(currentApiData.value) : currentApiData.value

        const { created_at, created_by, ...cleanedData } = requestData as any

        const response = await fetch(`/${props.server_slug}/ajax/apis/${props.api_id}/code`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            body: JSON.stringify(cleanedData),
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            if (response.status === 409) {
                showError('API version already exists. Please use a different version name.', '409: Version Conflict')
                return
            }
            showError(errorData.message || `HTTP error! status: ${response.status}`)
            return
        }

        const responseData = await response.json()
        const normalizedData = normalizeApiData(responseData, serverApiType.value as 'python' | 'php')
        currentApiData.value = normalizedData
        originalApiData.value = JSON.parse(JSON.stringify(normalizedData))
        triggerUpdate()
        success('API data saved successfully!', 'API Data Saved')
    } catch (e: any) {
        showError(e.message || 'Failed to save API data.', 'Save Error')
    } finally {
        isSaving.value = false
    }
}

let keydownHandler: ((event: KeyboardEvent) => void) | null = null

const startResize = (e: MouseEvent) => {
    isResizing.value = true
    e.preventDefault()

    const handleMouseMove = (e: MouseEvent) => {
        if (!isResizing.value || !splitContainer.value) return
        const rect = splitContainer.value.getBoundingClientRect()
        const newLeftWidth = ((e.clientX - rect.left) / rect.width) * 100
        const constrainedLeftWidth = Math.max(10, Math.min(80, newLeftWidth))
        leftPanelWidth.value = constrainedLeftWidth
        rightPanelWidth.value = 100 - constrainedLeftWidth - 1
    }

    const handleMouseUp = () => {
        isResizing.value = false
        document.removeEventListener('mousemove', handleMouseMove)
        document.removeEventListener('mouseup', handleMouseUp)
    }

    document.addEventListener('mousemove', handleMouseMove)
    document.addEventListener('mouseup', handleMouseUp)
}

// watch(loading, (isLoading) => {
//     // compareLog('loading:changed', { loading: isLoading, error: error.value || null })
// })

// watch(error, (err) => {
//     // compareLog('error:changed', { error: err || null, loading: loading.value })
// })

// watch([currentApiData, selectedApiData], () => {
//         compareLog('dataReady', {
//             hasCurrent: !!currentApiData.value,
//             hasSelected: !!selectedApiData.value,
//             current: getApiDataStats(currentApiData.value),
//             selected: getApiDataStats(selectedApiData.value),
//         })
// })

const forceUpdateWindowStart = ref(performance.now())
const forceUpdateWindowCount = ref(0)
watch(forceUpdate, (n) => {
    // compareLog('forceUpdate', { n })
    forceUpdateWindowCount.value++
    const elapsed = performance.now() - forceUpdateWindowStart.value
    if (elapsed > 1000) {
        forceUpdateWindowStart.value = performance.now()
        forceUpdateWindowCount.value = 1
        return
    }
    if (forceUpdateWindowCount.value > 5) {
        console.warn(`[Compare +${(performance.now() - compareMountTime).toFixed(0)}ms] forceUpdate:rapid`, {
            count: forceUpdateWindowCount.value,
            elapsedMs: Math.round(elapsed),
        })
    }
})

// watch([loading, error], ([isLoading, err]) => {
//     if (!isLoading && !err) {
//         compareLog('render:comparisonContent', {
//             activeTab: activeTab.value,
//             current: getApiDataStats(currentApiData.value),
//             selected: getApiDataStats(selectedApiData.value),
//         })
//     }
// })

onMounted(() => {
    // compareLog('mount')
    fetchAllData()

    keydownHandler = (event: KeyboardEvent) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 's') {
            event.preventDefault()
            handleSave()
        }
    }
    document.addEventListener('keydown', keydownHandler)
})

onUnmounted(() => {
    // compareLog('unmount')
    if (keydownHandler) document.removeEventListener('keydown', keydownHandler)
})
</script>

<template>
    <Head title="API Version Comparison" />

    <AppLayout :breadcrumbs="breadcrumbItems">
        <Toaster />
        <div class="h-screen flex flex-col">
            <!-- Header -->
            <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">API Version Comparison</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Changes from version {{ props.version_id }} to the current version
                        </p>
                    </div>
                    <Button @click="goBack" variant="outline">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to API Edit
                    </Button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4">
                <nav class="flex w-full">
                    <Button
                        v-for="tab in tabs"
                        :key="tab.id"
                        variant="ghost"
                        :class="['flex-1 px-4 py-2 rounded-t-md text-center transition-colors', { 'bg-muted font-semibold': activeTab === tab.id }]"
                        @click="navigateToTab(tab.id)"
                    >
                        {{ tab.title }}
                        <span
                            v-if="tab.id === 'functions' && functionEntries.some((e) => e.status !== 'unchanged')"
                            class="ml-2 h-1.5 w-1.5 rounded-full bg-amber-500"
                            title="Has changes"
                        />
                        <span
                            v-if="tab.id === 'files' && fileEntries.some((e) => e.status !== 'unchanged')"
                            class="ml-2 h-1.5 w-1.5 rounded-full bg-amber-500"
                            title="Has changes"
                        />
                        <span
                            v-if="tab.id === 'models' && modelEntries.some((e) => e.status !== 'unchanged')"
                            class="ml-2 h-1.5 w-1.5 rounded-full bg-amber-500"
                            title="Has changes"
                        />
                    </Button>
                </nav>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600 dark:text-gray-300">Loading version comparison...</p>
                </div>
            </div>

            <!-- Error -->
            <div v-else-if="error" class="flex-1 flex items-center justify-center">
                <div class="text-center text-red-600 dark:text-red-400">
                    <p class="text-lg font-semibold">Error loading comparison</p>
                    <p class="text-sm">{{ error }}</p>
                    <Button @click="fetchAllData" class="mt-4">Try again</Button>
                </div>
            </div>

            <div v-else ref="splitContainer" class="flex-1 flex overflow-hidden" :class="{ 'select-none': isResizing }">
                <!-- Options: JSON diff -->
                <div v-if="shouldShowJsonComparison" class="flex-1 overflow-auto p-4">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="font-medium text-gray-900 dark:text-white">options.json</h4>
                            <DiffStat v-if="optionsJsonData" :additions="optionsJsonData.additions" :deletions="optionsJsonData.deletions" />
                        </div>
                        <MonacoDiffView
                            :original="optionsJsonData?.selected || '{}'"
                            :modified="optionsJsonData?.current || '{}'"
                            language="json"
                            :side-by-side="sideBySide"
                        />
                    </div>
                </div>

                <!-- Functions / Files -->
                <div v-else-if="shouldShowDiffView" class="flex-1 flex flex-col overflow-hidden">
                    <!-- Sub-header -->
                    <div class="flex-shrink-0 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4 min-w-0">
                                <button
                                    v-if="selectedEntry"
                                    @click="selectedEntryKey = null"
                                    class="flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                    Back to list
                                </button>
                                <div v-if="selectedEntry" class="flex items-center gap-3 min-w-0">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded" :class="statusStyles[selectedEntry.status].class">
                                        {{ statusStyles[selectedEntry.status].label }}
                                    </span>
                                    <h3 class="font-mono font-medium text-gray-900 dark:text-white truncate">{{ selectedEntry.name }}</h3>
                                    <DiffStat :additions="selectedEntry.additions" :deletions="selectedEntry.deletions" />
                                </div>
                                <div v-else>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        {{ changedCount }} of {{ allEntries.length }} {{ itemLabel }}s changed
                                    </h3>
                                    <DiffStat :additions="activeTotals.additions" :deletions="activeTotals.deletions" :show-bar="false" />
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <label v-if="!selectedEntry" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                    <input v-model="hideUnchanged" type="checkbox" class="rounded" />
                                    Hide unchanged
                                </label>
                                <div v-else class="inline-flex rounded-md border border-gray-200 dark:border-gray-700 text-sm overflow-hidden">
                                    <button class="px-3 py-1" :class="sideBySide ? 'bg-muted font-medium' : ''" @click="sideBySide = true">Split</button>
                                    <button class="px-3 py-1" :class="!sideBySide ? 'bg-muted font-medium' : ''" @click="sideBySide = false">Unified</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- List -->
                    <div v-if="!selectedEntry" class="flex-1 overflow-auto p-4">
                        <p v-if="allEntries.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            Neither version has any {{ itemLabel }}s.
                        </p>
                        <p v-else-if="visibleEntries.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No {{ itemLabel }}s changed between these versions.
                        </p>
                        <ul v-else class="divide-y divide-gray-200 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700">
                            <li v-for="entry in visibleEntries" :key="entry.key">
                                <button
                                    type="button"
                                    class="w-full flex items-center justify-between gap-4 px-4 py-3 text-left hover:bg-gray-50 dark:hover:bg-gray-800/60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-500"
                                    @click="selectedEntryKey = entry.key"
                                >
                                    <span class="flex items-center gap-3 min-w-0">
                                        <span class="w-20 shrink-0 px-2 py-0.5 text-center text-xs font-medium rounded" :class="statusStyles[entry.status].class">
                                            {{ statusStyles[entry.status].label }}
                                        </span>
                                        <span
                                            class="font-mono text-sm truncate text-gray-900 dark:text-white"
                                            :class="{ 'line-through text-gray-500 dark:text-gray-500': entry.status === 'removed' }"
                                        >
                                            {{ entry.name }}
                                        </span>
                                    </span>
                                    <DiffStat :additions="entry.additions" :deletions="entry.deletions" />
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Detail diff -->
                    <div v-else class="flex-1 overflow-hidden p-4">
                        <div class="h-full rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <MonacoDiffView
                                :key="selectedEntry.key"
                                :original="selectedEntry.oldCode"
                                :modified="selectedEntry.newCode"
                                :language="diffLanguage"
                                :side-by-side="sideBySide"
                                height="100%"
                            />
                        </div>
                    </div>
                </div>

                <!-- Other tabs: side-by-side component panels -->
                <template v-else>
                    <!-- Left: selected (read-only) -->
                    <div class="overflow-hidden" :style="{ width: leftPanelWidth + '%' }">
                        <div class="h-full flex flex-col">
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="font-medium text-yellow-900 dark:text-yellow-100">Selected version</h3>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-300">{{ selectedApiData?.summary || 'Selected version' }}</p>
                                    </div>
                                    <div class="shrink-0 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-3 py-2 rounded-lg text-sm font-medium flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                        View only
                                    </div>
                                </div>
                            </div>
                            <!-- inert blocks all interaction and focus, replacing the setTimeout listener hack -->
                            <div class="flex-1 overflow-auto">
                                <div class="opacity-75 select-none" inert>
                                    <component :is="activeComponent" :key="`selected-${activeTab}`" v-bind="selectedComponentProps" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div
                        class="w-1 bg-gray-300 dark:bg-gray-600 hover:bg-blue-400 dark:hover:bg-blue-500 cursor-col-resize transition-colors flex-shrink-0"
                        :class="{ 'bg-blue-400 dark:bg-blue-500': isResizing }"
                        @mousedown="startResize"
                    />

                    <!-- Right: current -->
                    <div class="overflow-hidden" :style="{ width: rightPanelWidth + '%' }">
                        <div class="h-full flex flex-col">
                            <div class="bg-blue-50 dark:bg-blue-900/20 p-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-blue-900 dark:text-blue-100">Current version (latest)</h3>
                                    <p class="text-sm text-blue-700 dark:text-blue-300">{{ currentApiData?.summary || 'Latest/Working' }}</p>
                                </div>
                                <div class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-xs font-medium rounded">Latest</div>
                            </div>
                            <div class="flex-1 overflow-auto">
                                <component :is="activeComponent" :key="`current-${activeTab}-${forceUpdate}`" v-bind="currentComponentProps" />
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>