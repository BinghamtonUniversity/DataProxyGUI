<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

interface Props {
    api_id: string;
}

const props = defineProps<Props>();


const sidebarNavItems: NavItem[] = [
    {
        title: 'Routes',
        href: `/apis/${props.api_id}/routes`,
    },
    {
        title: 'Resources',
        href: `/apis/${props.api_id}/resources`,
    },
    {
        title: 'Functions',
        href: `/apis/${props.api_id}/functions`
    },
    {
        title: 'Files',
        href: `/apis/${props.api_id}/files`,
    },
    {
        title: 'Options',
        href: `/apis/${props.api_id}/options`,
    }
];

const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
// Fetch latest API version data
const apiData = ref(null)
const loadingApiData = ref(true)
const apiError = ref('')
const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL


const fetchApiData = async () => {
    loadingApiData.value = true
    apiError.value = ''
    try {
        const response = await fetch(`${djangoBaseUrl}/api/apis/${props.api_id}/versions/latest`)
        if (!response.ok) throw new Error('Failed to fetch API data')
        apiData.value = await response.json()
    } catch (e: any) {
        apiError.value = e.message || 'Error fetching API data'
        apiData.value = null
    } finally {
        loadingApiData.value = false
    }
}

onMounted(fetchApiData)

</script>

<template>
    <div class="px-4 py-6">
        <Heading title="API" description="Manage your API settings" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': currentPath === item.href }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot :apiData="apiData"
                        :loadingApiData="loadingApiData"
                        :apiError="apiError" 
                    />
                </section>
            </div>
        </div>
    </div>
</template>
