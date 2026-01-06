<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Play, CheckCircle2, XCircle, Clock, Loader2, FileText, Folder, RefreshCw, AlertCircle } from 'lucide-vue-next';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Unit Tests',
        href: '/unit-tests',
    },
];

// API base URL
const apiBaseUrl = '/api';

// State
const tests = ref<{
    unit: Array<{ name: string; path: string }>;
    feature: Array<{ name: string; path: string; directory?: string | null }>;
}>({
    unit: [],
    feature: [],
});

const stats = ref({
    total_tests: 0,
    unit_tests: 0,
    feature_tests: 0,
    test_files: 0,
});

const loading = ref(true);
const loadingTests = ref(false);
const runningTest = ref<string | null>(null);
const testResults = ref<any>(null);
const selectedSuite = ref<'all' | 'unit' | 'feature'>('all');

// Toaster
const { success, error: showError, info } = useToaster();

// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
};

// Fetch test list
const fetchTests = async () => {
    try {
        loading.value = true;
        const response = await fetch(`${apiBaseUrl}/tests`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        tests.value = data;
    } catch (err: any) {
        showError('Failed to fetch tests. Please try again.', 'Error');
        console.error('Error fetching tests:', err);
    } finally {
        loading.value = false;
    }
};

// Fetch test statistics
const fetchStats = async () => {
    try {
        const response = await fetch(`${apiBaseUrl}/tests/stats`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });

        if (response.ok) {
            const data = await response.json();
            stats.value = data;
        }
    } catch (err: any) {
        console.error('Error fetching test stats:', err);
    }
};

// Run all tests
const runAllTests = async (suite: 'all' | 'unit' | 'feature' = 'all') => {
    try {
        loadingTests.value = true;
        runningTest.value = suite === 'all' ? 'All Tests' : `${suite.charAt(0).toUpperCase() + suite.slice(1)} Tests`;
        testResults.value = null;

        const response = await fetch(`${apiBaseUrl}/tests/run`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ suite }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        testResults.value = data;

        if (data.results?.status === 'passed') {
            success(`All tests passed! (${data.results.passed} passed)`, 'Tests Passed');
        } else if (data.results?.status === 'failed') {
            showError(`${data.results.failed} test(s) failed`, 'Tests Failed');
        } else {
            info('Tests completed', 'Test Results');
        }
    } catch (err: any) {
        showError(err.message || 'Failed to run tests. Please try again.', 'Error');
        console.error('Error running tests:', err);
    } finally {
        loadingTests.value = false;
        runningTest.value = null;
    }
};

// Run specific test file
const runTestFile = async (filePath: string) => {
    try {
        loadingTests.value = true;
        runningTest.value = filePath;
        testResults.value = null;

        const response = await fetch(`${apiBaseUrl}/tests/run-file`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ file: filePath }),
        });

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        testResults.value = data;

        if (data.results?.status === 'passed') {
            success(`Test passed! (${data.results.passed} passed)`, 'Test Passed');
        } else if (data.results?.status === 'failed') {
            showError(`Test failed (${data.results.failed} failed)`, 'Test Failed');
        } else {
            info('Test completed', 'Test Results');
        }
    } catch (err: any) {
        showError(err.message || 'Failed to run test. Please try again.', 'Error');
        console.error('Error running test:', err);
    } finally {
        loadingTests.value = false;
        runningTest.value = null;
    }
};

// Refresh data
const refresh = async () => {
    await Promise.all([fetchTests(), fetchStats()]);
};

// Fetch data on mount
onMounted(() => {
    refresh();
});
</script>

<template>
    <Head title="Unit Tests" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Header with Stats and Actions -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">Unit Tests</h1>
                    <p class="text-sm text-muted-foreground mt-1">Run and manage your test suite</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="refresh"
                        :disabled="loading"
                        class="px-4 py-2 text-sm font-medium rounded-lg border border-sidebar-border/70 dark:border-sidebar-border bg-card hover:bg-accent/50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <RefreshCw :class="['h-4 w-4', loading && 'animate-spin']" />
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Tests</p>
                            <p class="text-2xl font-bold mt-1">{{ stats.total_tests }}</p>
                        </div>
                        <FileText class="h-8 w-8 text-blue-600 dark:text-blue-400 opacity-50" />
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Unit Tests</p>
                            <p class="text-2xl font-bold mt-1">{{ stats.unit_tests }}</p>
                        </div>
                        <Folder class="h-8 w-8 text-green-600 dark:text-green-400 opacity-50" />
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Feature Tests</p>
                            <p class="text-2xl font-bold mt-1">{{ stats.feature_tests }}</p>
                        </div>
                        <Folder class="h-8 w-8 text-purple-600 dark:text-purple-400 opacity-50" />
                    </div>
                </div>
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Test Files</p>
                            <p class="text-2xl font-bold mt-1">{{ stats.test_files }}</p>
                        </div>
                        <FileText class="h-8 w-8 text-orange-600 dark:text-orange-400 opacity-50" />
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6">
                <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
                <div class="flex flex-wrap gap-3">
                    <button
                        @click="runAllTests('all')"
                        :disabled="loadingTests"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <Play v-if="!loadingTests || runningTest !== 'All Tests'" class="h-4 w-4" />
                        <Loader2 v-else class="h-4 w-4 animate-spin" />
                        Run All Tests
                    </button>
                    <button
                        @click="runAllTests('unit')"
                        :disabled="loadingTests"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-green-600 hover:bg-green-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <Play v-if="!loadingTests || runningTest !== 'Unit Tests'" class="h-4 w-4" />
                        <Loader2 v-else class="h-4 w-4 animate-spin" />
                        Run Unit Tests
                    </button>
                    <button
                        @click="runAllTests('feature')"
                        :disabled="loadingTests"
                        class="px-4 py-2 text-sm font-medium rounded-lg bg-purple-600 hover:bg-purple-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <Play v-if="!loadingTests || runningTest !== 'Feature Tests'" class="h-4 w-4" />
                        <Loader2 v-else class="h-4 w-4 animate-spin" />
                        Run Feature Tests
                    </button>
                </div>
            </div>

            <!-- Test Results -->
            <div v-if="testResults" class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                <div class="p-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Test Results</h2>
                        <div v-if="testResults.results" class="flex items-center gap-4">
                            <div class="flex items-center gap-2">
                                <CheckCircle2 v-if="testResults.results.status === 'passed'" class="h-5 w-5 text-green-600 dark:text-green-400" />
                                <XCircle v-else-if="testResults.results.status === 'failed'" class="h-5 w-5 text-red-600 dark:text-red-400" />
                                <AlertCircle v-else class="h-5 w-5 text-yellow-600 dark:text-yellow-400" />
                                <span class="text-sm font-medium capitalize">{{ testResults.results.status }}</span>
                            </div>
                            <div v-if="testResults.results.duration" class="flex items-center gap-2 text-sm text-muted-foreground">
                                <Clock class="h-4 w-4" />
                                {{ testResults.results.duration }}s
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div v-if="testResults.results" class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 rounded-lg bg-green-50 dark:bg-green-900/20">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ testResults.results.passed || 0 }}</p>
                            <p class="text-sm text-muted-foreground mt-1">Passed</p>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-red-50 dark:bg-red-900/20">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ testResults.results.failed || 0 }}</p>
                            <p class="text-sm text-muted-foreground mt-1">Failed</p>
                        </div>
                        <div class="text-center p-4 rounded-lg bg-gray-50 dark:bg-gray-900/20">
                            <p class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ testResults.results.total || 0 }}</p>
                            <p class="text-sm text-muted-foreground mt-1">Total</p>
                        </div>
                    </div>
                    <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm overflow-x-auto max-h-96 overflow-y-auto">
                        <pre>{{ testResults.output || 'No output available' }}</pre>
                    </div>
                </div>
            </div>

            <!-- Test Files List -->
            <div class="grid gap-4 md:grid-cols-2">
                <!-- Unit Tests -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                    <div class="p-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Unit Tests</h2>
                            <span class="text-sm text-muted-foreground">{{ tests.unit.length }} files</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="loading" class="text-center py-8">
                            <Loader2 class="h-8 w-8 animate-spin mx-auto text-muted-foreground" />
                            <p class="text-sm text-muted-foreground mt-2">Loading tests...</p>
                        </div>
                        <div v-else-if="tests.unit.length === 0" class="text-center py-8 text-muted-foreground">
                            <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                            <p>No unit tests found</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="test in tests.unit"
                                :key="test.path"
                                class="flex items-center justify-between p-3 rounded-lg hover:bg-accent/50 transition-colors group"
                            >
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <FileText class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                    <span class="text-sm font-medium truncate">{{ test.name }}</span>
                                </div>
                                <button
                                    @click="runTestFile(test.path)"
                                    :disabled="loadingTests"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity px-2 py-1 text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                >
                                    <Play class="h-3 w-3" />
                                    Run
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Tests -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                    <div class="p-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold">Feature Tests</h2>
                            <span class="text-sm text-muted-foreground">{{ tests.feature.length }} files</span>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="loading" class="text-center py-8">
                            <Loader2 class="h-8 w-8 animate-spin mx-auto text-muted-foreground" />
                            <p class="text-sm text-muted-foreground mt-2">Loading tests...</p>
                        </div>
                        <div v-else-if="tests.feature.length === 0" class="text-center py-8 text-muted-foreground">
                            <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                            <p>No feature tests found</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="test in tests.feature"
                                :key="test.path"
                                class="flex items-center justify-between p-3 rounded-lg hover:bg-accent/50 transition-colors group"
                            >
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <FileText class="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-medium block truncate">{{ test.name }}</span>
                                        <span v-if="test.directory" class="text-xs text-muted-foreground">{{ test.directory }}</span>
                                    </div>
                                </div>
                                <button
                                    @click="runTestFile(test.path)"
                                    :disabled="loadingTests"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity px-2 py-1 text-xs font-medium rounded bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                >
                                    <Play class="h-3 w-3" />
                                    Run
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Toaster -->
            <Toaster />
        </div>
    </AppLayout>
</template>
