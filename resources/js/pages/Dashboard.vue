<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { Database, Folder, Users, Building2, History, ArrowRight, Activity } from 'lucide-vue-next';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { getCsrfToken } from '@/lib/utils';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

// API base URL
const apiBaseUrl = 'api';

// Statistics state with individual loading states
const stats = ref({
    apiInstances: 0,
    apis: 0,
    users: 0,
    environments: 0,
});

const loadingStats = ref({
    apiInstances: true,
    apis: true,
    users: true,
    environments: true,
});

// Activity logs state
const recentActivityLogs = ref<any[]>([]);
const loadingActivityLogs = ref(true);
const error = ref<string | null>(null);

// Toaster
const { error: showError } = useToaster();

// Get CSRF token from meta tag
// const getCsrfToken = () => {
//     const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
//     return token;
// };

// Format timestamp for display
const formatTimestamp = (timestamp: string | null | undefined) => {
    if (!timestamp) return '';
    try {
        const date = new Date(timestamp);
        if (isNaN(date.getTime())) return '';
        return date.toLocaleString();
    } catch {
        return timestamp;
    }
};

// Format relative time (e.g., "2 hours ago")
const formatRelativeTime = (timestamp: string | null | undefined) => {
    if (!timestamp) return '';
    try {
        const date = new Date(timestamp);
        if (isNaN(date.getTime())) return '';
        
        const now = new Date();
        const diffMs = now.getTime() - date.getTime();
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} minute${diffMins !== 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours !== 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
        return formatTimestamp(timestamp);
    } catch {
        return timestamp;
    }
};

// Fetch individual statistics (each runs independently)
const fetchApiInstances = async () => {
    loadingStats.value.apiInstances = true;
    try {
        const response = await fetch(`${apiBaseUrl}/api_instances`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        if (response.ok) {
            const data = await response.json();
            stats.value.apiInstances = Array.isArray(data) ? data.length : 0;
        }
    } catch (err: any) {
        console.error('Error fetching API instances:', err);
    } finally {
        loadingStats.value.apiInstances = false;
    }
};

const fetchApis = async () => {
    loadingStats.value.apis = true;
    try {
        const response = await fetch(`${apiBaseUrl}/apis`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        if (response.ok) {
            const data = await response.json();
            if (Array.isArray(data)) {
                stats.value.apis = data.length;
            } else if (data && typeof data === 'object') {
                // Handle object with numeric keys
                stats.value.apis = Object.keys(data)
                    .filter(key => key !== 'error' && !isNaN(Number(key)))
                    .length;
            }
        }
    } catch (err: any) {
        console.error('Error fetching APIs:', err);
    } finally {
        loadingStats.value.apis = false;
    }
};

const fetchUsers = async () => {
    loadingStats.value.users = true;
    try {
        const response = await fetch(`${apiBaseUrl}/users`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        if (response.ok) {
            const data = await response.json();
            stats.value.users = Array.isArray(data) ? data.length : 0;
        }
    } catch (err: any) {
        console.error('Error fetching users:', err);
    } finally {
        loadingStats.value.users = false;
    }
};

const fetchEnvironments = async () => {
    loadingStats.value.environments = true;
    try {
        const response = await fetch(`${apiBaseUrl}/environments`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        });
        if (response.ok) {
            const data = await response.json();
            stats.value.environments = Array.isArray(data) ? data.length : 0;
        }
    } catch (err: any) {
        console.error('Error fetching environments:', err);
    } finally {
        loadingStats.value.environments = false;
    }
};

// Fetch recent activity logs
const fetchRecentActivityLogs = async () => {
    loadingActivityLogs.value = true;
    try {
        const response = await fetch(`${apiBaseUrl}/activity_log`, {
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
        // Get the 10 most recent logs
        recentActivityLogs.value = Array.isArray(data) 
            ? data.slice(0, 10).sort((a: any, b: any) => {
                const timeA = a.created_at ? new Date(a.created_at).getTime() : 0;
                const timeB = b.created_at ? new Date(b.created_at).getTime() : 0;
                return timeB - timeA; // Most recent first
            })
            : [];
    } catch (err: any) {
        error.value = err.message || 'Failed to fetch activity logs';
        console.error('Error fetching activity logs:', err);
    } finally {
        loadingActivityLogs.value = false;
    }
};

// Fetch all data on mount (each independently)
onMounted(() => {
    // Start all fetches in parallel, but they update independently
    fetchApiInstances();
    fetchApis();
    fetchUsers();
    fetchEnvironments();
    fetchRecentActivityLogs();
});

// Navigate to page
const navigateTo = (path: string) => {
    router.visit(path);
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- Dashboard Content -->
            <div class="space-y-6">
                <!-- Statistics Cards -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- API Instances Card -->
                    <div 
                        @click="navigateTo('/api_instances')"
                        class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 cursor-pointer hover:bg-accent/50 transition-colors group"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">API Instances</p>
                                <div v-if="loadingStats.apiInstances" class="mt-2">
                                    <div class="animate-pulse h-9 w-16 bg-muted rounded"></div>
                                </div>
                                <p v-else class="text-3xl font-bold mt-2">{{ stats.apiInstances }}</p>
                            </div>
                            <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-900/50 transition-colors">
                                <Database class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-muted-foreground group-hover:text-foreground transition-colors">
                            <span>View all</span>
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </div>
                    </div>

                    <!-- APIs Card -->
                    <div 
                        @click="navigateTo('/apis')"
                        class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 cursor-pointer hover:bg-accent/50 transition-colors group"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">APIs</p>
                                <div v-if="loadingStats.apis" class="mt-2">
                                    <div class="animate-pulse h-9 w-16 bg-muted rounded"></div>
                                </div>
                                <p v-else class="text-3xl font-bold mt-2">{{ stats.apis }}</p>
                            </div>
                            <div class="rounded-full bg-green-100 dark:bg-green-900/30 p-3 group-hover:bg-green-200 dark:group-hover:bg-green-900/50 transition-colors">
                                <Folder class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-muted-foreground group-hover:text-foreground transition-colors">
                            <span>View all</span>
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </div>
                    </div>

                    <!-- Users Card -->
                    <div 
                        @click="navigateTo('/users')"
                        class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 cursor-pointer hover:bg-accent/50 transition-colors group"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Users</p>
                                <div v-if="loadingStats.users" class="mt-2">
                                    <div class="animate-pulse h-9 w-16 bg-muted rounded"></div>
                                </div>
                                <p v-else class="text-3xl font-bold mt-2">{{ stats.users }}</p>
                            </div>
                            <div class="rounded-full bg-purple-100 dark:bg-purple-900/30 p-3 group-hover:bg-purple-200 dark:group-hover:bg-purple-900/50 transition-colors">
                                <Users class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-muted-foreground group-hover:text-foreground transition-colors">
                            <span>View all</span>
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </div>
                    </div>

                    <!-- Environments Card -->
                    <div 
                        @click="navigateTo('/environments')"
                        class="relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 cursor-pointer hover:bg-accent/50 transition-colors group"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-muted-foreground">Environments</p>
                                <div v-if="loadingStats.environments" class="mt-2">
                                    <div class="animate-pulse h-9 w-16 bg-muted rounded"></div>
                                </div>
                                <p v-else class="text-3xl font-bold mt-2">{{ stats.environments }}</p>
                            </div>
                            <div class="rounded-full bg-orange-100 dark:bg-orange-900/30 p-3 group-hover:bg-orange-200 dark:group-hover:bg-orange-900/50 transition-colors">
                                <Building2 class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                            </div>
                        </div>
                        <div class="mt-4 flex items-center text-sm text-muted-foreground group-hover:text-foreground transition-colors">
                            <span>View all</span>
                            <ArrowRight class="ml-2 h-4 w-4" />
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card">
                    <div class="p-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Activity class="h-5 w-5 text-muted-foreground" />
                                <h2 class="text-xl font-semibold">Recent Activity</h2>
                            </div>
                            <button 
                                @click="navigateTo('/activity_logs')"
                                class="text-sm text-muted-foreground hover:text-foreground transition-colors flex items-center gap-1"
                            >
                                View all
                                <ArrowRight class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div v-if="loadingActivityLogs" class="text-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                            <p class="text-sm text-muted-foreground mt-3">Loading activity logs...</p>
                        </div>
                        <div v-else-if="error" class="text-center py-8 text-muted-foreground">
                            <p>Unable to load activity logs</p>
                            <p class="text-sm mt-1">{{ error }}</p>
                        </div>
                        <div v-else-if="recentActivityLogs.length === 0" class="text-center py-8 text-muted-foreground">
                            <History class="h-12 w-12 mx-auto mb-3 opacity-50" />
                            <p>No recent activity</p>
                        </div>
                        <div v-else class="space-y-4">
                            <div 
                                v-for="(log, index) in recentActivityLogs" 
                                :key="index"
                                class="flex items-start gap-4 p-4 rounded-lg hover:bg-accent/50 transition-colors"
                            >
                                <div class="mt-1">
                                    <div class="rounded-full bg-blue-100 dark:bg-blue-900/30 p-2">
                                        <Activity class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex-1">
                                            <p class="font-medium">
                                                <span v-if="log.action" class="capitalize">{{ log.action }}</span>
                                                <span v-else-if="log.event_type" class="capitalize">{{ log.event_type }}</span>
                                                <span v-else>Activity</span>
                                            </p>
                                            <p v-if="log.comment" class="text-sm text-muted-foreground mt-1">{{ log.comment }}</p>
                                            <div v-if="log.event_id" class="text-xs text-muted-foreground mt-1">
                                                Event ID: {{ log.event_id }}
                                            </div>
                                        </div>
                                        <div class="text-xs text-muted-foreground whitespace-nowrap">
                                            {{ formatRelativeTime(log.created_at) }}
                                        </div>
                                    </div>
                                </div>
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
