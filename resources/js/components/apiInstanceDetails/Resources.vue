<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ApiInstance, ApiUser, Resource, type ApiData } from '@/types'
import {
  DropdownMenu,
  DropdownMenuItem,
  DropdownMenuContent,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import {  ChevronDown, Plus } from 'lucide-vue-next'
import { getCsrfToken } from '@/lib/utils'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';

interface Props {
    instance_id: string
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

const newResourceName = ref('')
const selectedResourceId = ref('')

const hasExistingResources = computed(() => {
  return props.apiInstanceData?.api_version.resources && props.apiInstanceData.api_version.resources.length > 0
})

const getResourceNameById = (resourceId: string) => {
  if (!props.resources || !Array.isArray(props.resources)) return resourceId
  const resource = props.resources.find(r => r.id.toString() === resourceId)
  return resource ? resource.name : resourceId
}


const getResourceIdForIndex = (index: string): string | null => {
  const numericIndex = parseInt(index, 10);

  if (props.apiInstanceData?.resources && props.apiInstanceData.resources[numericIndex]) {
    return props.apiInstanceData.resources[numericIndex].resource || null;
  }
  return null;
};

const saveResources = async() => {
  // if (!props.apiInstanceData || !newResourceName.value || !selectedResourceId.value) return
  if (!props.apiInstanceData) return

  const requestData = {
    id: props.apiInstanceData.id,
    name: props.apiInstanceData.name,
    route: props.apiInstanceData.route, 
    route_user_map: props.apiInstanceData.route_user_map,
    resources: props.apiInstanceData.resources, 
    options: props.apiInstanceData.options,
    public: props.apiInstanceData.public,
    api_id: props.apiInstanceData.api_id,
    api_version_id: props.apiInstanceData.api_version_id,
    environment_id: props.apiInstanceData.environment_id
  }
  
  props.updateApiInstanceData(requestData)
  success('Resources updated successfully', 'Success');
  
  newResourceName.value = ''
  selectedResourceId.value = ''
}

// Update existing resource
const updateResource = (index: number, resource_name: string, value: string) => {
  if (!props.apiInstanceData) return
  
  const updatedResources = [...props.apiInstanceData.resources || []]
  updatedResources[index] = {
    ...updatedResources[index],
    ['name']: resource_name,
    ['resource']: value
  }
  
  const updatedData = {
    ...props.apiInstanceData,
    resources: updatedResources
  }
  
  props.updateApiInstanceData(updatedData)
}
</script>

<template>
  <div class="space-y-6">
    <div class="border rounded-lg p-4">
      <h3 class="text-lg font-semibold mb-4">Resources</h3>
      
      <!-- Loading state -->
      <div v-if="loading" class="text-center py-4">
        Loading...
      </div>
      
      <!-- Error state -->
      <div v-else-if="apiInstanceError" class="text-red-600 py-4">
        Error: {{ apiInstanceError }}
      </div>
      
      <!-- Main content -->
      <div v-else-if="apiInstanceData" class="space-y-4">
        
        <!-- Existing Resources Display -->
        <div v-if="hasExistingResources" class="space-y-3">
          <h4 class="font-medium">Existing Resources:</h4>
          <div 
            v-for="(resourceItem, index) in apiInstanceData.api_version.resources" 
            :key="index"
            class="flex items-end gap-4"
          >
            <div class="flex-1">
              <Label :for="`resource-name-${index}`" class="text-sm font-medium">Name:</Label>
              <Input
                :id="`resource-name-${index}`"
                v-model="resourceItem.name"
                class="mt-1"
                placeholder="Resource name"
              />
            </div>
            
            <div class="flex-1">
              <Label class="text-sm font-medium">Resource:</Label>
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="outline" class="w-full mt-1 justify-between">
                    {{ getResourceNameById(getResourceIdForIndex(index.toString()) ?? '') || 'Select Resource' }}
                    <ChevronDown class="ml-2 h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-full">
                  <DropdownMenuItem
                    v-for="resource in (resources || [])"
                    :key="resource.id"
                    @click="updateResource(index, resourceItem.name, resource.id.toString())"
                    class="cursor-pointer"
                  >
                    {{ resource.name }} ({{ resource.resource_type }})
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>

          </div>
        </div>
         <!-- <div class="pt-4 border-t">
          <Button 
            @click="saveResources"
          >
            Save Resources
          </Button>
        </div> -->
        
        <!-- Empty state message -->
        <div v-if="!hasExistingResources" class="text-gray-500 text-center py-4">
          No resources configured yet
        </div>
      </div>
      
      <!-- No data state -->
      <div v-else class="text-gray-500 text-center py-4">
        No API instance data available
      </div>
    </div>
  </div>
  <Toaster />
</template>