<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ApiInstance, ApiUser, Resource, type ApiData } from '@/types'
import { getCsrfToken } from '@/lib/utils'

interface Props {
    instance_id: string
    apiInstanceData: ApiInstance | null,
    apiUsers: ApiUser | null,
    resources: Resource | null,
    loading: boolean,
    apiInstanceError: string
    updateApiInstanceData: (updatedApiInstanceData: ApiInstance) => void
}

const props = defineProps<Props>()

// Local reactive data for editable fields
const editableData = ref({
    name: '',
    route: ''
})

watch(
  () => props.apiInstanceData,
  (newVal) => {
    if (newVal) {
      editableData.value.name = newVal.name
      editableData.value.route = newVal.route
    }
  },
  { immediate: true } // run once right away as well
)

// Watch for changes in apiInstanceData and update local data
const updateLocalData = () => {
    if (props.apiInstanceData) {
        editableData.value.name = props.apiInstanceData.name
        editableData.value.route = props.apiInstanceData.route
    }
}

// Initialize local data when component mounts
updateLocalData()

// Computed properties for read-only fields
const readOnlyData = computed(() => ({
    api: props.apiInstanceData?.api.name,
    api_version: props.apiInstanceData?.api_version.stable === false ? 'Latest working version' : props.apiInstanceData?.api_version.summary ?? undefined,
    environment: props.apiInstanceData?.environment.name
}))

// Save function
const saveChanges = async () => {
    if (props.apiInstanceData) {
        const updatedData: ApiInstance = {
            ...props.apiInstanceData,
            name: editableData.value.name,
            route: editableData.value.route
        }
        console.log('Updated data to save:', updatedData)
        const requestData = {
            id: updatedData.id,
            name: updatedData.name,
            route: updatedData.route, 
            route_user_map: updatedData.route_user_map,
            resources: updatedData.resources, 
            options: updatedData.options,
            public: updatedData.public,
            api_id: updatedData.api.id,
            api_version_id: updatedData.api_version_id,
            environment_id: updatedData.environment.id
        }
        console.log('Saving updated data:', requestData)
        // const response = await fetch(`/ajax/api_instances/${props.instance_id}`, {
        //     method: 'PUT',
        //     headers: {
        //         'Content-Type': 'application/json',
        //         'Accept': 'application/json',
        //         'X-CSRF-TOKEN': getCsrfToken() || '',
        //     },
        //     body: JSON.stringify(requestData)
        // })

        // if (!response.ok) {
        //     const errorData = await response.json().catch(() => ({}))
        //     throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        // }

        // const responseData = await response.json()
        // props.updateApiInstanceData(responseData)
    }
}
</script>

<template>
    <div class="space-y-6 p-6">
        <div v-if="loading" class="text-center">
            Loading...
        </div>
        
        <div v-else-if="apiInstanceError" class="text-red-500">
            Error: {{ apiInstanceError }}
        </div>
        
        <div v-else-if="apiInstanceData" class="space-y-4">
            <h2 class="text-2xl font-bold">API Instance Details</h2>
            
            <!-- Editable Fields -->
            <div class="space-y-4">
                <div>
                    <Label for="name" class="mb-1">Name</Label>
                    <Input 
                        id="name" 
                        v-model="editableData.name" 
                        placeholder="Enter name"
                        class="w-full"
                    />
                </div>
                
                <div>
                    <Label for="route" class="mb-1">Route</Label>
                    <Input 
                        id="route" 
                        v-model="editableData.route" 
                        placeholder="Enter route"
                        class="w-full"
                    />
                </div>
            </div>
            
            <!-- Read-only Fields -->
            <div class="space-y-4">
                <div>
                    <Label for="api" class="mb-1">API</Label>
                    <Input 
                        id="api" 
                        v-model="readOnlyData.api" 
                        readonly
                        disabled
                        class="w-full bg-gray-50"
                    />
                </div>
                
                <div>
                    <Label for="api-version" class="mb-1">API Version</Label>
                    <Input 
                        id="api-version" 
                        v-model="readOnlyData.api_version" 
                        readonly
                        disabled
                        class="w-full bg-gray-50"
                    />
                </div>
                
                <div>
                    <Label for="environment" class="mb-1">Environment</Label>
                    <Input 
                        id="environment" 
                        v-model="readOnlyData.environment" 
                        readonly
                        disabled
                        class="w-full bg-gray-50"
                    />
                </div>
            </div>
            
            <!-- Save Button -->
            <div class="pt-4">
                <Button @click="saveChanges" class="w-full md:w-auto">
                    Save Changes
                </Button>
            </div>
        </div>
        
        <div v-else class="text-gray-500">
            No API instance data available
        </div>
    </div>
</template>