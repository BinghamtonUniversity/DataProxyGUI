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

// FormViewer ref for validation
const formViewerRef = ref<InstanceType<typeof FormViewer> | null>(null)

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


const handleFormDataUpdate = (data: any) => {
    // Merge incoming data with existing formData to preserve read-only fields
    // FormViewer only emits changed fields, so we need to merge with current formData
    const mergedData = {
        ...formData.value, // Preserve all existing fields (including read-only ones)
        ...data // Override with changed fields
    }
    
    // Validate form before updating
    if (formViewerRef.value) {
        const isValid = formViewerRef.value.validateForm()
        if (!isValid) {
            // Validation failed - errors are already displayed by FormViewer
            // Don't save, but update local editableData to keep UI in sync
            if (data.name !== undefined) {
                editableData.value.name = data.name || ''
            }
            if (data.route !== undefined) {
                editableData.value.route = data.route || ''
            }
            return
        }
    }
    
    // Update local editableData
    if (data.name !== undefined) {
        editableData.value.name = data.name || ''
    }
    if (data.route !== undefined) {
        editableData.value.route = data.route || ''
    }
    
    // Save with merged data (preserves read-only fields)
    if (props.apiInstanceData && mergedData){
        const updatedData: ApiInstance = {
            ...props.apiInstanceData,
            name: mergedData.name || '',
            route: mergedData.route || ''
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

const handleFormAction = async (actionData: { type: string; action: string; formData: any }) => {
    switch (actionData.action) {
        case 'save':
            // Validate form before submitting
            if (formViewerRef.value) {
                const isValid = formViewerRef.value.validateForm()
                if (!isValid) {
                    // Validation failed - errors are already displayed by FormViewer
                    error('Please fix validation errors before saving', 'Validation Error')
                    return
                }
            }
            
            // Save the validated form data
            if (props.apiInstanceData && actionData.formData) {
                const updatedData: ApiInstance = {
                    ...props.apiInstanceData,
                    name: actionData.formData.name || '',
                    route: actionData.formData.route || ''
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
                success('Changes saved successfully', 'Success')
            }
            break
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

            <FormViewer 
                ref="formViewerRef"
                @change="handleFormDataUpdate"
                :formConfig="formConfig" 
                :initialData="formData" 
                :showActions="false"
            />
        </div>
        
        <div v-else class="text-gray-500">
            No API instance data available
        </div>
    </div>

</template>