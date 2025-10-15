<script setup lang="ts">
import { ChevronDown, Plus, Check } from 'lucide-vue-next'
import { ref, onMounted, onUnmounted, reactive } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, ApiInstance, Environment, Api, ApiInstanceRouteUserMap, ApiInstanceResource, ApiData} from '@/types'
import { Head } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import { getCsrfToken } from '@/lib/utils'
import { router } from '@inertiajs/vue3'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import DataGrid from '@/components/datagrid/DataGrid.vue';


const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'API Instances',
    href: '/api_instances',
  },
]

const api_instances = ref<ApiInstance[]>([])
const environments = ref<Environment[]>([])
const apis = ref<Api[]>([])
const api_versions = ref<ApiData[]>([])
// Toaster
const { success, error, warning, info } = useToaster();

const loading = ref(true)

// DataGrid schema for API instances
const apiInstancesSchema = {
    label: '',
    description: '',
    name: "api-instances-schema",
    files: false,
    fields: [
        {
            name: "id",
            label: "ID",
            type: "text",
            placeholder: "API Instance ID",
            value: "",
            help: "Unique identifier for the API instance",
            info: "Unique identifier for the API instance",
            width: "12",
            offset: "0",
            required: true,
            showColumn: false
        },
        {
            name: "name",
            label: "Name",
            type: "text",
            placeholder: "API Instance Name",
            value: "",
            help: "Name of the API instance",
            info: "Name of the API instance",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "route",
            label: "Slug",
            type: "text",
            placeholder: "Route/Slug",
            value: "",
            help: "Route or slug for the API instance",
            info: "Route or slug for the API instance",
            width: "12",
            offset: "0",
            required: true,
            showColumn: true
        },
        {
            name: "environment_id",
            label: "Environment",
            type: "text",
            placeholder: "Environment ID",
            value: "",
            help: "Environment where this API instance is deployed",
            info: "Environment where this API instance is deployed",
            width: "12",
            offset: "0",
            options: [],
            required: true,
            showColumn: true
        },
        {
            name: "api_id",
            label: "API",
            type: "text",
            placeholder: "API",
            value: "",
            help: "ID of the associated API",
            info: "ID of the associated API",
            width: "12",
            offset: "0",
            options: [],
            required: true,
            showColumn: true
        },
        {
            name: "api_version_id_id",
            label: "API Version",
            type: "text",
            placeholder: "API Version ID",
            value: "",
            help: "Version of the API",
            info: "Version of the API",
            width: "12",
            offset: "0",
            options: [] as Array<{label: string, value: any, color: string}>,
            required: false,
            showColumn: true
        },
        {
            name: "errors",
            label: "Error Level",
            type: "text",
            placeholder: "Resources",
            value: "All",
            help: "Resources of the API instance",
            info: "Resources of the API instance",
            width: "12",
            offset: "0",
            required: false,
            showColumn: true
        },
        {
            name: "resources",
            label: "Resources",
            type: "text",
            placeholder: "Resources",
            value: "",
            help: "Resources of the API instance",
            info: "Resources of the API instance",
            width: "12",
            offset: "0",
            required: false,
            showColumn: true,
            isArrayObject: true,
            targetObjectAttribute: "name",
            targetColor: "bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-200"
        }
    ]
};

//new API Instance
const newApiInstanceDialogOpen = ref(false)

interface NewApiInstanceForm {
  environment_id: string
  api_id: string
  api_version_id: string
  name: string
  route: string
  public: number
  route_user_map: ApiInstanceRouteUserMap[]
  resources: ApiInstanceResource[]
  options: [] | any
}

//TO-DO: Should send route_user_map and resources as empty JSON 
const newApiInstanceForm = ref<NewApiInstanceForm>({
  environment_id: '',
  api_id: '',
  api_version_id: '',
  name: '',
  route: '',
  public: 0,
  route_user_map: [],
  resources: [],
  options: []
})
const newApiInstanceLoading = ref(false)
const newApiInstanceError = ref('')

// Edit API Instance
const isEditMode = ref(false)
const editingApiInstanceId = ref<number|null>(null)

// Dropdown state
const dropdownOpen = reactive({
  environment: false,
  api: false
})

const openNewApiInstanceDialog = () => {
  newApiInstanceForm.value = {
    environment_id: '',
    api_id: '',
    api_version_id: '',
    name: '',
    route: '',
    public: 0,
    route_user_map: [],
    resources: [],
    options: []
  }
  newApiInstanceError.value = ''
  newApiInstanceDialogOpen.value = true
}

const closeNewApiInstanceDialog = () => {
  newApiInstanceDialogOpen.value = false
  newApiInstanceError.value = ''
  newApiInstanceForm.value = {
    environment_id: '',
    api_id: '',
    api_version_id: '',
    name: '',
    route: '',
    public: 0,
    route_user_map: [],
    resources: [],
    options: []
  }
  isEditMode.value = false
  editingApiInstanceId.value = null
}

const submitNewApiInstance = async (e: Event) => {
  e.preventDefault()
  newApiInstanceLoading.value = true
  newApiInstanceError.value = ''

  // Trim and normalize route just in case
  const routeToCheck = newApiInstanceForm.value.route.trim().toLowerCase()
  const envToCheck = newApiInstanceForm.value.environment_id

  // Composite duplicate check (route + environment)
  const duplicate = api_instances.value.some(inst =>
    inst.route.trim().toLowerCase() === routeToCheck &&
    inst.environment_id === Number(envToCheck) &&
    (!isEditMode.value || inst.id !== editingApiInstanceId.value) // ignore self when editing
  )

  if (duplicate) {
    newApiInstanceError.value = 'An API instance with this route already exists in the selected environment.'
    error(newApiInstanceError.value, 'Duplicate Entry')
    newApiInstanceLoading.value = false
    return // prevent API call
  }

  try {
    let url = `/api/api_instances`
    let request_method = 'POST'
    if (isEditMode.value && editingApiInstanceId.value) {
      url = `/api/api_instances/${editingApiInstanceId.value}`
      request_method = 'PUT'
    }

    const body = isEditMode.value && editingApiInstanceId.value
      ? { ...newApiInstanceForm.value, id: editingApiInstanceId.value }
      : { ...newApiInstanceForm.value }

    const response = await fetch(url, {
      method: request_method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken() || '',
      },
      body: JSON.stringify(body),
    })

    if (!response.ok) throw new Error('Failed to save API Instance')
    if(isEditMode.value) {
      success('Updated successfully', 'API Instance Updated');
    } else {
      success('Created successfully', 'API Instance Created');
    }
    closeNewApiInstanceDialog()
    await fetchApiInstances()
  } catch (err: any) {
    newApiInstanceError.value = err.message || 'Error saving API Instance'
    error(newApiInstanceError.value, 'Error');
  } finally {
    newApiInstanceLoading.value = false
    isEditMode.value = false
    editingApiInstanceId.value = null
  }
}

const handleDeleteInstance = async (instance: ApiInstance) => {
  if (!confirm(`Are you sure you want to delete API Instance "${instance.name}"? This action cannot be undone.`)) {
    return
  }
  try{
    const response = await fetch(`/api/api_instances/${instance.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken() || '',
      },
    })
    if (!response.ok) {
      error('Failed to delete API Instance', 'Error')
      throw new Error('Failed to delete API Instance')
    }
    success(`API Instance "${instance.name}" deleted successfully`, 'API Instance Deleted');
    await fetchApiInstances()
  } catch (err: any) {
    error(err.message || 'Error deleting API Instance', 'Error')
  } finally {
    // cleanup 
  }
}

const openEditApiInstanceDialog = (apiInstance: ApiInstance) => {
  isEditMode.value = true
  editingApiInstanceId.value = apiInstance.id
  newApiInstanceForm.value = {
    environment_id: apiInstance.environment_id?.toString() || '',
    api_id: apiInstance.api_id?.toString() || '',
    api_version_id: apiInstance.api_version_id?.toString() || '',
    name: apiInstance.name || '',
    route: apiInstance.route || '',
    public: apiInstance.public || 0,
    route_user_map: apiInstance.route_user_map?.map(item => ({
      api_user: item.api_user?.toString() || '',
      verb: item.verb || '',
      route: item.route || ''
    })) || [],
    resources: apiInstance.resources?.map(item => ({
      name: item.name || '',
      resource: item.resource?.toString() || ''
    })) || [
      {
        name: '',
        resource: ''
      }
    ],
    options: apiInstance.options || []
  }
  newApiInstanceDialogOpen.value = true
}

const handleRowClick = (instance: ApiInstance, event: MouseEvent) => {
  // Check if the click target is within the actions column
  const target = event.target as HTMLElement
  if (target.closest('[data-actions-cell]')) {
    return // Don't handle row click if clicking on actions
  }
  
  // console.log('View details for API:', instance)
  router.visit(`/api_instances/${instance.id}/main`)
}



const selectEnvironment = (env: Environment) => {
  newApiInstanceForm.value.environment_id = env.id.toString()
  dropdownOpen.environment = false
}

const selectApi = (api: Api) => {
  newApiInstanceForm.value.api_id = api.id.toString()
  dropdownOpen.api = false
}

const getSelectedEnvironmentName = () => {
  if (!newApiInstanceForm.value.environment_id || !environments.value) return ''
  const selected = environments.value.find(env => env.id.toString() === newApiInstanceForm.value.environment_id)
  return selected ? `${selected.name} - ${selected.type}` : ''
}

const getSelectedApiName = () => {
  if (!newApiInstanceForm.value.api_id || !apis.value) return ''
  const selected = apis.value.find(api => api.id.toString() === newApiInstanceForm.value.api_id)
  return selected ? selected.name : ''
}

// Close dropdowns when clicking outside
const handleClickOutside = (event: Event) => {
  const target = event.target as Element
  const dropdownElements = document.querySelectorAll('.relative')
  let clickedInside = false
  
  dropdownElements.forEach(element => {
    if (element.contains(target)) {
      clickedInside = true
    }
  })
  
  if (!clickedInside) {
    Object.keys(dropdownOpen).forEach(key => {
      dropdownOpen[key as keyof typeof dropdownOpen] = false
    })
  }
}


const fetchApiInstances = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/api_instances`)
    api_instances.value = await response.json()

    // Set api_version_id to -1 if it is null
    api_instances.value.forEach((instance: any) => {
        if(instance.api_version_id === null) {
            instance.api_version_id = -1
        }
    });
  } catch (e) {
    api_instances.value = []
    console.error('Error fetching API Instances:', e)
  } finally {
    loading.value = false
  }
}

const fetchAllData = async () => {
  loading.value = true
  try {
    const [
      apiInstancesResponse,
      environmentsResponse,
      apisResponse,
      apiVersionsResponse
    ] = await Promise.all([
      fetch(`/api/api_instances`),
      fetch(`/api/environments`),
      fetch(`/api/apis`),
      fetch(`/api/api_versions`),
    ])

    if (!apiInstancesResponse.ok) throw new Error('Failed to fetch API instances')
    if (!environmentsResponse.ok) throw new Error('Failed to fetch environments')
    if (!apisResponse.ok) throw new Error('Failed to fetch APIs')
    if (!apiVersionsResponse.ok) throw new Error('Failed to fetch API versions')

    const [
      apiInstancesData,
      environmentsData,
      apisData,
      apiVersionsData
    ] = await Promise.all([
      apiInstancesResponse.json(),
      environmentsResponse.json(),
      apisResponse.json(),
      apiVersionsResponse.json(),
    ])
    


    api_instances.value = apiInstancesData
    environments.value = environmentsData
    apis.value = apisData
    api_versions.value = apiVersionsData
    


    apiInstancesSchema.fields[3].options = environmentsData.map((env: any) => ({
        label: env.name + ' (' + env.type + ') '  || `Environment ${env.id}`,
        value: env.id,
        color: env.type === 'test' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : env.type === 'dev' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    }));

    apiInstancesSchema.fields[4].options = apisData.map((api: any) => ({
        label: api.name  || `API ${api.id}`,
        value: api.id,
        color: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
    }));

    apiInstancesSchema.fields[5].options = apiVersionsData.map((apiVersion: any) => ({
        
        label: apiVersion.summary  || `API Version ${apiVersion.id}`,
        value: apiVersion.id,
        color: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
    }));
    api_instances.value.forEach((instance: any) => {
        if(instance.api_version_id_id === null) {
            instance.api_version_id_id = -1
        }
    });
     apiInstancesSchema.fields[5]!.options!.unshift!({
         label: 'Latest/Working',
         value: -1,
         color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
     });
     console.log(apiInstancesSchema.fields[5]!.options!)
     console.log(api_instances.value)
  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    loading.value = false
  }
}

// Lifecycle hooks
onMounted(() => { 
  fetchAllData()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// DataGrid action handlers
const handleDataGridAction = (actionData: { type: string; payload: any }) => {
  console.log('DataGrid action:', actionData);
  
  switch (actionData.type) {
    case 'single-edit':
      openEditApiInstanceDialog(actionData.payload);
      break;
    case 'single-delete':
      handleDeleteInstance(actionData.payload);
      break;
    case 'view':
      router.visit(`/api_instances/${actionData.payload.id}/main`);
      break;
    default:
      console.log('Unknown action type:', actionData.type);
  }
};

const handleDataGridCustomAction = (actionData: { action: string; selectedRows: any[]; selectedData: any[] }) => {
  console.log('Custom action triggered:', actionData);
  
  switch (actionData.action) {
    case 'create':
      openNewApiInstanceDialog();
      break;
    default:
      info(`Please implement the ${actionData.action} function`, 'Action Not Implemented');
  }
};

const handleDataGridRowClick = (row: any) => {
  console.log('DataGrid row click:', row);
  router.visit(`/api_instances/${row.id}/main`);
};
</script>

<template>
  <Head title="APIs" />
  
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
        
        <!-- Loading State -->
        <template v-if="loading">
          <div class="flex items-center justify-center h-32">
            <div class="text-center">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
              <p class="mt-2">Loading APIs...</p>
            </div>
          </div>
        </template>

        <!-- DataGrid Implementation -->
        <template v-else>
          <DataGrid 
            :schema="apiInstancesSchema"
            :data="api_instances"
            theme="default"
            :actions="[
              {name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus'},
            ]"
            :rowActions="[
              { type: 'single-edit', label: 'Edit', icon: 'edit', colorClass: 'text-blue-600 hover:bg-blue-50' },
              { type: 'view', label: 'View', icon: 'eye', colorClass: 'text-green-600 hover:bg-green-50' },
              { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50' }
            ]"
            @actionHandler="handleDataGridCustomAction"
            @rowActionHandler="handleDataGridAction"
            @rowClick="handleDataGridRowClick"
          />
        </template>

        <!-- Create/Edit Dialog -->
        <Dialog v-model:open="newApiInstanceDialogOpen">
          <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <form @submit="submitNewApiInstance" class="space-y-6">
              <DialogHeader>
                <DialogTitle>{{ isEditMode ? 'Edit API Instance' : 'Create New API Instance' }}</DialogTitle>
              </DialogHeader>
              <div class="grid gap-6">
                <!-- Environment Selection -->
                <div class="relative">
                  <Label for="environment-id" class="mb-1">Environment</Label>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="outline" class="w-full justify-between">
                        {{ getSelectedEnvironmentName() || 'Select Environment' }}
                        <ChevronDown class="ml-2 h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-full max-h-60">
                      <DropdownMenuItem
                        v-for="env in environments"
                        :key="env.id"
                        @click="selectEnvironment(env)"
                        class="flex items-center justify-between"
                      >
                        <span class="block truncate">{{ env.name }} - {{ env.type }}</span>
                        <Check
                          v-if="newApiInstanceForm.environment_id === env.id.toString()"
                          class="h-4 w-4 text-blue-600"
                        />
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>

                <!-- API Selection -->
                <div class="relative">
                  <Label for="api-id" class="mb-1">API</Label>
                  <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                      <Button variant="outline" class="w-full justify-between">
                        {{ getSelectedApiName() || 'Select API' }}
                        <ChevronDown class="ml-2 h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-full max-h-60">
                      <DropdownMenuItem
                        v-for="api in apis"
                        :key="api.id"
                        @click="selectApi(api)"
                        class="flex items-center justify-between"
                      >
                        <span class="block truncate">{{ api.name }}</span>
                        <Check
                          v-if="newApiInstanceForm.api_id === api.id.toString()"
                          class="h-4 w-4 text-blue-600"
                        />
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>

                <!-- API Version -->
                <div>
                  <Label for="api-version" class="mb-1">API Version</Label>
                  <Input id="api-version" v-model="newApiInstanceForm.api_version_id" placeholder="API Version" />
                </div>

                <!-- Name -->
                <div>
                  <Label for="instance-name" class="mb-1">Name</Label>
                  <Input id="instance-name" v-model="newApiInstanceForm.name" required placeholder="API Instance Name" />
                </div>

                <!-- Route/Slug -->
                <div>
                  <Label for="instance-route" class="mb-1">Slug</Label>
                  <Input id="instance-route" v-model="newApiInstanceForm.route" required placeholder="Route/Slug" />
                </div>

                <div v-if="newApiInstanceError" class="text-red-600 text-sm">{{ newApiInstanceError }}</div>
              </div>
              <DialogFooter class="gap-2">
                <DialogClose as-child>
                  <Button variant="secondary" type="button" @click="closeNewApiInstanceDialog">Cancel</Button>
                </DialogClose>
                <Button type="submit" variant="default" :disabled="newApiInstanceLoading">
                  <span v-if="newApiInstanceLoading">{{ isEditMode ? 'Saving...' : 'Creating...' }}</span>
                  <span v-else>{{ isEditMode ? 'Save' : 'Create' }}</span>
                </Button>
              </DialogFooter>
            </form>
          </DialogContent>
        </Dialog>

        
      </div>
    </div>
  </AppLayout>
  <Toaster />
</template>

<style scoped>
.rotate-180 {
  transform: rotate(180deg);
}
</style>