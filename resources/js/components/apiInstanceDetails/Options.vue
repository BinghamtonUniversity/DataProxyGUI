<script setup lang="ts">
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import Editor from '@/pages/Editor.vue'
import { type ApiData } from '@/types'
import { ApiInstance, ApiUser, Resource, type ApiInstanceRouteUserMap } from '@/types'
import FormViewer from '@/components/formviewer/FormViewer.vue'
import Toaster from '@/components/toaster/Toaster.vue'
import { getCsrfToken } from '@/lib/utils'
import { useToaster } from '@/composables/useToaster'
import { Skeleton } from '@/components/ui/skeleton'
const { success, error: showError, warning, info } = useToaster();

interface Props {
    instance_id: string
    apiInstanceData: ApiInstance | null,
    apiUsers: ApiUser[] | null,
    resources: Resource[] | null,
    loading: boolean,
    apiInstanceError: string
    updateApiInstanceData: (updatedApiInstanceData: ApiInstance) => void
    refreshApiInstanceData?: () => void
}
const props = defineProps<Props>()

const isChanged = ref(false)

const formConfig = computed(() => {
    return {label: ' Options',
    description: '',
    name: "options",
    files: false,
    fields: [...props.apiInstanceData?.api_version?.options.fields || []]
}

});

const initialData = computed(() => {

    return props.apiInstanceData?.options || []
})

// const handleSave = async (data: any) => {

    
//     if (!props.apiInstanceData) return

//     const requestData = {
//     id: props.apiInstanceData.id,
//     name: props.apiInstanceData.name,
//     route: props.apiInstanceData.route, 
//     route_user_map: props.apiInstanceData.route_user_map,
//     resources: props.apiInstanceData.resources, 
//     options: data,
//     public: props.apiInstanceData.public,
//     api_id: props.apiInstanceData.api.id,
//     api_version_id: props.apiInstanceData.api_version_id,
//     environment_id: props.apiInstanceData.environment.id
//   }


//   const response = await fetch(`/ajax/api_instances/${props.instance_id}`, {
//         method: 'PUT',
//         headers: {
//             'Content-Type': 'application/json',
//             'Accept': 'application/json',
//             'X-CSRF-TOKEN': getCsrfToken() || '',
//         },
//         body: JSON.stringify(requestData)
//     })

//     if (!response.ok) {
//         const errorData = await response.json().catch(() => ({}))
//         showError(errorData.message || `HTTP error! status: ${response.status}`)
//         throw new Error(errorData.message || `HTTP error! status: ${response.status}`)  
        
//     }

//   const responseData = await response.json()
//   props.updateApiInstanceData(responseData)
//   success('Options saved successfully!')   
// }


const handleFormDataUpdate = (data: any) => {

    if (props.apiInstanceData?.options === data) return;
    if (!props.apiInstanceData) return

    const requestData = {
        id: props.apiInstanceData.id,
        name: props.apiInstanceData.name,
        route: props.apiInstanceData.route, 
        route_user_map: props.apiInstanceData.route_user_map,
        resources: props.apiInstanceData.resources, 
        options: data,
        public: props.apiInstanceData.public,
        api_id: props.apiInstanceData.api.id,
        api_version_id: props.apiInstanceData.api_version_id,
        environment_id: props.apiInstanceData.environment.id
        }
    props.updateApiInstanceData(requestData  as ApiInstance);
};
</script>

<template>
    <div v-if="props.loading" class="space-y-4">
        <Skeleton class="h-6 w-1/3" />
        <div class="grid grid-cols-12 gap-4">
            <Skeleton class="h-10 col-span-12 md:col-span-6" />
            <Skeleton class="h-10 col-span-12 md:col-span-6" />
            <Skeleton class="h-10 col-span-12 md:col-span-4" />
            <Skeleton class="h-10 col-span-12 md:col-span-4" />
            <Skeleton class="h-10 col-span-12 md:col-span-4" />
        </div>
    </div>
    <div v-else class="max-w-2xl mx-auto border border-gray-200 dark:border-gray-800 rounded-lg p-4">
    <FormViewer
       
        :formConfig="formConfig "
        :initialData="initialData"
        :actions="[{ type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' }]"
        @update:modelValue="handleFormDataUpdate"
        :showActions="false"
    />
    </div>
    <!-- Global Toaster -->
    <Toaster />
</template>
