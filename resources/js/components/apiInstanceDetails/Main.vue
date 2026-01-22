<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ApiInstance, ApiUser, Resource, type ApiData } from '@/types'
import { getCsrfToken } from '@/lib/utils'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import FormViewer from '@/components/formviewer/FormViewer.vue';

interface Props {
    instance_id: string,
    api_type: string,
    apiInstanceData: ApiInstance | null,
    apiUsers: ApiUser[] | null,
    resources: Resource[] | null,
    loading: boolean,
    apiInstanceError: string
    updateApiInstanceData: (updatedApiInstanceData: Partial<ApiInstance> ) => void
}
const props = defineProps<Props>()
// Toaster
const { success, error, warning, info } = useToaster();

const formConfig = computed(() => ({
    label: 'API Instance Details',
    description: '',
    fields: [
        { name: 'name', label: 'Name', type: 'text', required: true },
        { name: 'route', label: 'Route', type: 'text', required: true },
        { name: 'api', label: 'API', type: 'text', required: true, edit:false },
        { name: 'api_version', label: 'API Version', type: 'text', required: true, edit:false  },
        { name: 'environment', label: 'Environment', type: 'text', required: true, edit:false  }
    ]
}));
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
      editableData.value.route = props.api_type === 'php' && newVal.slug ? newVal.slug : newVal.route
    }
  },
  { immediate: true } // run once right away as well
)
// Watch for changes in apiInstanceData and update local data
const updateLocalData = () => {
    if (props.apiInstanceData) {
        editableData.value.name = props.apiInstanceData.name
        editableData.value.route = props.api_type === 'php' && props.apiInstanceData.slug ? props.apiInstanceData.slug : props.apiInstanceData.route
    }
}

// Initialize local data when component mounts
updateLocalData()

const formData = computed(() => ({
    name: editableData.value.name,
    route: editableData.value.route,
    api: props.apiInstanceData?.api.name,
    api_version: props.apiInstanceData?.api_version==null ? 'Latest working version' : props.apiInstanceData?.api_version.stable === false ? 'Latest working version' : props.apiInstanceData?.api_version.summary ?? undefined,
    environment: props.apiInstanceData?.environment.name
}))

let oldFormData =  formData.value;

// Computed properties for read-only fields
// const readOnlyData = computed(() => ({
//     api: props.apiInstanceData?.api.name,
//     api_version: props.apiInstanceData?.api_version==null ? 'Latest working version' : props.apiInstanceData?.api_version.stable === false ? 'Latest working version' : props.apiInstanceData?.api_version.summary ?? undefined,
//     environment: props.apiInstanceData?.environment.name
// }))

// // Save function
// const saveChanges = async () => {
//     if (props.apiInstanceData) {
//         const updatedData: ApiInstance = {
//             ...props.apiInstanceData,
//             name: editableData.value.name,
//             route: editableData.value.route
//         }
       
//         const requestData = {
//             id: updatedData.id,
//             name: updatedData.name,
//             route: updatedData.route, 
//             route_user_map: updatedData.route_user_map,
//             resources: updatedData.resources, 
//             options: updatedData.options,
//             public: updatedData.public,
//             api_id: updatedData.api_id,
//             api_version_id: updatedData.api_version_id,
//             environment_id: updatedData.environment_id
//         }

//         props.updateApiInstanceData(requestData)
//         success('Changes saved', 'Success');
//     }
// }

const handleFormDataUpdate = (data: any) => {
    debugger;
    if (props.apiInstanceData &&data){
        const updatedData: ApiInstance = {
            ...props.apiInstanceData,
            name: data.name,
            route: data.route
        }
        const requestData = {
                id: updatedData.id,
                name: updatedData.name,
                route: updatedData.route, 
                route_user_map: updatedData.route_user_map,
                resources: updatedData.resources, 
                options: updatedData.options,
                public: updatedData.public,
                api_id: updatedData.api_id,
                api_version_id: updatedData.api_version_id,
                environment_id: updatedData.environment_id
            }
            props.updateApiInstanceData(requestData)
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

            <FormViewer :formConfig="formConfig" :initialData="formData" :showActions="false" @change="handleFormDataUpdate" :actions="[
                { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' }
            ]" />
     <!--
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
            </div> -->
            
            <!-- Save Button -->
            <!-- <div class="pt-4">
                <Button @click="saveChanges" class="w-full md:w-auto">
                    Save Changes
                </Button>
            </div> -->
        </div>
        
        <div v-else class="text-gray-500">
            No API instance data available
        </div>
    </div>

</template>