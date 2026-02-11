<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Api, ApiInstance, ApiUser, Resource, type ApiData } from '@/types'
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
    updateApiInstanceData: (updatedApiInstanceData: Partial<ApiInstance>) => void
}

const props = defineProps<Props>()

// Toaster
const { success, error, warning, info } = useToaster();

// Form configuration
const resourcesFormConfig = computed(() => ({
  label: 'Resources',
  description: 'Configure instance resources',
  name: "resources-form",
  files: false,
  fields: [
    {
   
      name: 'resources',
      label: '',
      type: 'fieldset',
      array: {
        min: 0,
        max: "",
        add: {
          enable: 'never',
        },
        duplicate: {
          enable: 'never',
        },
        remove: {
          enable: 'never',
        },
       
      },

      fields: [
        {
          name: 'name',
          label: 'Name',
          type: 'text',
          required: true,
          edit: false,
          width: '6',
          offset: '0'
        },
        {
          name: 'resource',
          label: 'Resource',
          type: 'select',
          required: false,
          width: '6',
          offset: '0',
          options: props.resources?.map(r => ({
            label: `${r.name} (${r.resource_type})`,
            value: r.id.toString()
          })) || []
        }
      ]
    }
  ]
}))

// Initial form data - map api_version.resources to form structure
const initialFormData = computed(() => {
  if (!props.apiInstanceData?.api_version?.resources) {
    return { resources: [] }
  }

  const resources = props.apiInstanceData.api_version.resources.map((versionResource, index) => {
    // Find the corresponding resource ID from apiInstanceData.resources
    const instanceResource = props.apiInstanceData?.resources?.[index]
    return {
      name: versionResource.name || instanceResource?.name || '',
      resource: instanceResource?.resource || ''
    }
  })

  return { resources }
})

// Handle form changes
const handleFormChange = (formData: any) => {
  if (!props.apiInstanceData) return

  if (formData.resources && formData.resources.length != 0) {
    for (const resource of formData.resources) {
        if (!resource.name || !resource.resource) {
            // remove the resource from the updatedResources
            formData.resources = formData.resources.filter((r: any) => r.name !== resource.name)
        }
    }
  }

  // Map form data back to apiInstanceData.resources structure
  const updatedResources = (formData.resources || []).map((item: any) => ({
    name: item.name || '',
    resource: item.resource || ''
  }))

  

  const updatedData = {
    ...props.apiInstanceData,
    resources: updatedResources
  }

  props.updateApiInstanceData(updatedData)
}

// Handle form actions (if needed)
const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.action) {
    case 'save':
      handleFormChange(actionData.formData)
      success('Resources updated successfully', 'Success')
      break;

    default:
      console.log('Unknown FormViewer action type:', actionData.type);
  }
}

const hasExistingResources = computed(() => {
  return props.apiInstanceData?.api_version.resources && props.apiInstanceData?.api_version.resources.length > 0
})
</script>

<template>
  <div class="space-y-6">
    <div class="border rounded-lg p-4">
      <!-- Loading state -->
      <div v-if="loading" class="text-center py-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
        <p class="mt-2">Loading resources...</p>
      </div>
      
      <!-- Error state -->
      <div v-else-if="apiInstanceError" class="text-red-600 py-4">
        Error: {{ apiInstanceError }}
      </div>
      
      <!-- Main content with FormViewer -->
      <div v-else-if="apiInstanceData">
        <FormViewer
          :formConfig="resourcesFormConfig"
          :initialData="initialFormData"
          :edit="true"
          :showActions="false"
          @change="handleFormChange"
          :actionHandler="handleFormAction"
        />
      </div>
      
      <!-- No data state -->
      <div v-else class="text-gray-500 text-center py-4">
        No API instance data available
      </div>
    </div>
  </div>
</template>