<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { ShieldX, LayoutGrid } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { useProxyServer } from '@/composables/useProxyServer';

const { buildUrl, serverSlug } = useProxyServer();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Access Denied', href: '#' },
];

const mainRef = ref<HTMLElement | null>(null);

onMounted(() => {
    mainRef.value?.focus();
});
</script>

<template>
    <Head title="Access Denied" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <main
            ref="mainRef"
            id="main-content"
            role="main"
            tabindex="-1"
            class="flex flex-1 flex-col items-center justify-center p-6 focus:outline-none"
            aria-labelledby="access-denied-heading"
        >
            <Card
                class="w-full max-w-lg border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20"
                role="alert"
                aria-live="polite"
            >
                <CardHeader class="text-center">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/50"
                        aria-hidden="true"
                    >
                        <ShieldX class="h-8 w-8 text-amber-600 dark:text-amber-400" />
                    </div>
                    <CardTitle id="access-denied-heading" class="text-xl">
                        Access denied
                    </CardTitle>
                    <CardDescription class="mt-2 text-base text-muted-foreground">
                        You don’t have permission to open this page. This area is only available to server administrators.
                    </CardDescription>
                </CardHeader>
                <CardContent class="flex flex-col gap-3">
                    <p class="text-sm text-muted-foreground text-center">
                        If you believe you should have access, contact your administrator.
                    </p>
                    <Button as-child class="w-full" size="lg">
                        <Link
                            v-if="serverSlug"
                            :href="buildUrl('dashboard')"
                            class="inline-flex items-center justify-center"
                        >
                            <LayoutGrid class="mr-2 h-4 w-4" aria-hidden="true" />
                            Go to Dashboard
                        </Link>
                        <Link
                            v-else
                            href="/welcome"
                            class="inline-flex items-center justify-center"
                        >
                            <LayoutGrid class="mr-2 h-4 w-4" aria-hidden="true" />
                            Go to Welcome
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </main>
    </AppLayout>
</template>
