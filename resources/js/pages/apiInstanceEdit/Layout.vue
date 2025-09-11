<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiData, ApiInstance, ApiUser, Resource } from '@/types'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'

// Import your components (convert them to pure components)
import Main from '@/components/apiInstanceDetails/Main.vue'
import Resources from '@/components/apiInstanceDetails/Resources.vue'
import Options from '@/components/apiInstanceDetails/Options.vue'
import Permissions from '@/components/apiInstanceDetails/Permissions.vue'



interface Props {
    instance_id: string
    activeTab: string
}

const props = defineProps<Props>()

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
const apiUsers = ref<ApiUser | null>(null)
const resources = ref<Resource | null>(null)


const loading = ref(true)
const apiInstanceError = ref('')

const fetchApiInstanceData = async () => {
    loading.value = true
    apiInstanceError.value = ''
    try {
        const response = await fetch(`/ajax/api_instances/${props.instance_id}`)
        // console.log('Fetch response:', response)
        if (!response.ok) throw new Error('Failed to fetch API Instance data')
        apiInstanceData.value = await response.json()
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
    const [
      apiInstancesResponse,
      apiUsersResponse,
      resourcesResponse,
    //   apisResponse,
      // apiVersionsResponse
    ] = await Promise.all([
      fetch(`/api/api_instances`),
      fetch(`/api/api_users`),
      fetch(`/resources/type/dev`), // TO:DO - Change to dynamic type if needed
    //   fetch(`/api/environments`),
    //   fetch(`/api/apis`),
      
    ])

    if (!apiInstancesResponse.ok) throw new Error('Failed to fetch API instances')
    if (!apiUsersResponse.ok) throw new Error('Failed to fetch environments')
    if (!resourcesResponse.ok) throw new Error('Failed to fetch resources')
    // if (!apisResponse.ok) throw new Error('Failed to fetch APIs')
    // if (!apiVersionsResponse.ok) throw new Error('Failed to fetch API users')

    const [
      apiInstancesData,
      apiUsersData,
      resourcesData,
      // apisData,
      // apiVersionsData
    ] = await Promise.all([
      apiInstancesResponse.json(),
      apiUsersResponse.json(),
      resourcesResponse.json(),
      //   apisResponse.json(),
      // apiVersionsResponse.json(),
    ])

    apiInstanceData.value = apiInstancesData
    apiUsers.value = apiUsersData
    resources.value = resourcesData
    // apis.value = apisData
    // api_versions.value = apiVersionsData

  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    loading.value = false
  }
}

const updateApiInstanceData = (updatedApiInstanceData: ApiInstance) => {
    apiInstanceData.value = updatedApiInstanceData
}

const refreshApiInstanceData = () => {
    fetchApiInstanceData()
}

// Navigation helper
const navigateToTab = (tabId: string) => {
    router.get(`/api_instances/${props.instance_id}/${tabId}`, {}, {
        preserveState: true,
        preserveScroll: true,
        // only: ['activeTab'] // Only update the activeTab prop
    })
}

// Get current active component
const activeComponent = computed(() => {
    return tabs.find(tab => tab.id === props.activeTab)?.component || tabs[0].component
})

// Component props to pass down
const componentProps = computed(() => ({
    instance_id: props.instance_id,
    apiInstanceData: apiInstanceData.value,
    apiUsers: apiUsers.value,
    loading: loading.value,
    apiInstanceError: apiInstanceError.value,
    updateApiInstanceData,
    refreshApiInstanceData
}))

// // Fetch data on mount
onMounted(() => fetchAllData())

</script>

<template>
    <Head title="API Instace Details" />
    
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="px-4 py-6">
            <Heading :title="`API Instance - ${props.instance_id}`" description="Manage your API Instance" />

            <div class="flex flex-col space-y-8">
                <!-- Tab navigation -->
                <nav class="flex w-full mb-8">
                    <Button
                        v-for="tab in tabs"
                        :key="tab.id"
                        variant="ghost"
                        :class="[
                            'flex-1 px-4 py-2 rounded-t-md text-center transition-colors',
                            { 'bg-muted font-semibold': props.activeTab === tab.id }
                        ]"
                        @click="navigateToTab(tab.id)"
                    >
                        {{ tab.title }}
                    </Button>
                </nav>

                <div class="flex-1 w-11/12">
                    <section class="w-full space-y-12">
                        <!-- Dynamic component rendering -->
                        <component
                            :is="activeComponent"
                            v-bind="componentProps"
                        />
                    </section>
                </div>
            </div>
        </div>
    </AppLayout>
</template>