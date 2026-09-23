<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem, ApiData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { getCsrfToken } from '@/lib/utils';

// Getting it from web.php parameter
interface Props {
    api_type: string;
    api_id: string;
}

const props = defineProps<Props>();

const sidebarNavItems: NavItem[] = [
    {
        title: 'Routes',
        href: `/apis/${props.api_type}/${props.api_id}/routes`,
    },
    {
        title: 'Resources',
        href: `/apis/${props.api_type}/${props.api_id}/resources`,
    },
    {
        title: 'Functions',
        href: `/apis/${props.api_type}/${props.api_id}/functions`,
    },
    {
        title: 'Models',
        href: `/apis/${props.api_type}/${props.api_id}/models`,
    },
    {
        title: 'Files',
        href: `/apis/${props.api_type}/${props.api_id}/files`,
    },
    {
        title: 'Options',
        href: `/apis/${props.api_type}/${props.api_id}/options`,
    }
];

const page = usePage();

const currentPath = page.props.ziggy?.location ? new URL(page.props.ziggy.location).pathname : '';
// Fetch latest API version data
const apiData = ref<ApiData | null>(null)
const loadingApiData = ref(true)
const apiError = ref('')
const apiBaseUrl = '/api'


const fetchApiData = async () => {
    loadingApiData.value = true
    apiError.value = ''
    try {
        const response = await fetch(`${apiBaseUrl}/apis/${props.api_id}/versions/latest`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin'
        })
        if (!response.ok) throw new Error('Failed to fetch API data')
        apiData.value = await response.json()
    } catch (e: any) {
        apiError.value = e.message || 'Error fetching API data'
        apiData.value = null
    } finally {
        loadingApiData.value = false
    }
}

const updateApiData = (updatedApiData: ApiData) => {
    apiData.value = updatedApiData
}

const refreshApiData = () => {
    fetchApiData()
}

onMounted(fetchApiData)

</script>

<template>
    <div class="px-4 py-6">
        <Heading :title="`API - ${props.api_id}`" description="Manage your API settings" />

        <div class="flex flex-col space-y-8">
        <!-- Tab navigation at the top -->
        <nav class="flex w-full mb-8">
            <Button
                v-for="item in sidebarNavItems"
                :key="item.href"
                variant="ghost"
                :class="[
                    'flex-1 px-4 py-2 rounded-t-md text-center',
                    { 'bg-muted font-semibold': currentPath === item.href }
                ]"
                as-child
            >
                <Link :href="item.href">
                    {{ item.title }}
                </Link>
            </Button>
        </nav>

        <div class="flex-1 w-11/12">
            <section class="w-full space-y-12">
                <slot
                    :apiData="apiData"
                    :loadingApiData="loadingApiData"
                    :apiError="apiError"
                    :updateApiData="updateApiData"
                    :refreshApiData="refreshApiData"
                />
            </section>
        </div>
    </div>
    </div>
</template>