<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Server, Settings, AlertCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const page = usePage();
const isSuperAdmin = computed(() => (page.props.auth?.user as { super_admin?: boolean } | undefined)?.super_admin ?? false);
</script>

<template>
    <Head title="No Servers Available" />

    <div class="flex items-center justify-center">
        <Card class="w-full max-w-md">
            <CardHeader class="text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900">
                    <AlertCircle class="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
                </div>
                <CardTitle class="text-lg">No Active Proxy Servers</CardTitle>
                <CardDescription class="mt-2">
                    You need to configure/select at least one proxy server to continue.
                </CardDescription>
                <div v-if="isSuperAdmin" class="flex flex-col gap-2">
                    <Button as-child class="w-full">
                        <Link :href="route('servers')">
                            <Settings class="mr-2 h-4 w-4" />
                            Configure Servers
                        </Link>
                    </Button>
                </div>
                <p v-else class="mt-3 text-sm text-muted-foreground">
                    Contact your administrator to configure proxy servers.
                </p>
            </CardHeader>
        </Card>
    </div>
</template>