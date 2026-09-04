<script setup lang="ts">
import type {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
} from '@tanstack/vue-table'
import {
  FlexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, ChevronDown, Plus, Trash2 } from 'lucide-vue-next'
import { h, ref, computed } from 'vue'
import { getCsrfToken, valueUpdater } from '@/lib/utils'

import { ApiInstance, ApiUser, Resource, type ApiInstanceRouteUserMap } from '@/types'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuItem
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose
} from '@/components/ui/dialog'
import { Label } from '@/components/ui/label'
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import AlertModal from '@/components/AlertModal.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import DataGrid from '@/components/datagrid/DataGrid.vue';

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

const newPermissionDialogOpen = ref(false)
const newPermissionForm = ref({
    api_user: '',
    user: '',
    verb: '',
    route: ''
})
const newPermissionLoading = ref(false)
const newPermissionError = ref('')
const isEditMode = ref(false)
const editingPermissionIndex = ref<number | null>(null)

// Toaster
const { success, error, warning, info } = useToaster();

// FormViewer ref for validation
const permissionFormViewer = ref<InstanceType<typeof FormViewer> | null>(null)

const formConfig = computed(() => ({
    label: 'New Permission',
    description: 'Create a new permission',
    fields: [
        { name: 'api_user', label: 'User', type: 'select', options: props.apiUsers?.map((user: ApiUser) => ({
                label: user.app_name,
                value: user.id.toString()
            })) || [], required: true },
        { name: 'verb', label: 'HTTP Method (Verb)', type: 'select', options: ['ALL','GET', 'POST', 'PUT', 'DELETE', 'PATCH'], required: true },
        { name: 'route', label: 'Route', type: 'select', options: [
                { label: "*", value: "*" },
                ...groupRoutePaths(props.apiInstanceData?.api_version?.version_urls || [])
                    .map(path => ({ label: path, value: path }))
            ], required: true }
    ]
}))

function groupRoutePaths(versionUrls: { path: string }[]): string[] {
    const groups = new Map<string, Set<string>>()

    for (const { path } of versionUrls) {
        const segments = path.split('/').filter(Boolean)
        const key = segments.length ? `/${segments[0]}` : path

        if (!groups.has(key)) groups.set(key, new Set())
        groups.get(key)!.add(path)
    }

    return Array.from(groups.entries()).map(([key, paths]) =>
        paths.size > 1 ? `${key}*` : [...paths][0]
    )
}

const permissionSchema = computed(() => ({
    label: 'Permissions',
    description: 'A list of permissions with their information.',
    name: "permissions-schema",
    fields: [
        { 
            name: 'api_user', 
            label: 'User', 
            type: 'text', 
            options: props.apiUsers?.map((user: ApiUser) => ({
                label: user.app_name,
                value: user.id.toString()
            })) || [], 
            required: true 
        },
        { name: 'verb', label: 'HTTP Method (Verb)', type: 'select', options: ['ALL','GET', 'POST', 'PUT', 'DELETE', 'PATCH'], required: true },
        { name: 'route', label: 'Route', type: 'text', required: true }
    ]
}))   
const handleFormAction = async (actionData: { type: string; action: string; formData: any }) => {
    switch (actionData.action) {
        case 'close':
        case 'cancel':
            closeNewPermissionDialog()
            break
        case 'save':
            // Validation is handled automatically by FormViewer when validateOnSubmit is true
            newPermissionForm.value = {
                api_user: actionData.formData.api_user,
                user: actionData.formData.api_user,
                verb: actionData.formData.verb,
                route: actionData.formData.route
            }
          
            await submitNewPermission(actionData.formData)
            break
    }
}
const openNewPermissionDialog = () => {
  newPermissionForm.value = {
    api_user: '',
    user: '',
    verb: '',
    route: ''
  }
  newPermissionError.value = ''
  newPermissionDialogOpen.value = true
}

const closeNewPermissionDialog = () => {
  newPermissionDialogOpen.value = false
  newPermissionError.value = ''
  isEditMode.value = false
  editingPermissionIndex.value = null
}

const submitNewPermission = async (formData: any) => {

    newPermissionLoading.value = true
    newPermissionError.value = ''
    
    if (!props.apiInstanceData) {
        newPermissionError.value = 'API instance data not available'
        newPermissionLoading.value = false
        return
    }

    try {
        const normalizedRoute = newPermissionForm.value.route ?? "" 
        const newPermission: ApiInstanceRouteUserMap = {
            api_user: newPermissionForm.value.user,
            verb: newPermissionForm.value.verb,
            route: normalizedRoute.trim()
        }
       
        let updatedApiInstanceData
    
        if (isEditMode.value && editingPermissionIndex.value !== null) {
            updatedApiInstanceData = {
                ...props.apiInstanceData,
                route_user_map: props.apiInstanceData.route_user_map?.map((permission, index) => 
                index === editingPermissionIndex.value 
                    ? { ...permission, ...newPermission }
                    : permission
                ) || []
            }
        } else { 
            updatedApiInstanceData = {
                ...props.apiInstanceData,
                route_user_map: [...(props.apiInstanceData.route_user_map || []), newPermission]
            }
        }
       
    
        const requestData = {
            id: updatedApiInstanceData.id,
            name: updatedApiInstanceData.name,
            route: updatedApiInstanceData.route, 
            route_user_map: updatedApiInstanceData.route_user_map,
            resources: updatedApiInstanceData.resources, 
            options: updatedApiInstanceData.options,
            public: updatedApiInstanceData.public,
            api_id: updatedApiInstanceData.api.id,
            api_version_id: updatedApiInstanceData.api_version_id,
            environment_id: updatedApiInstanceData.environment.id
        }
      
        props.updateApiInstanceData(requestData)
         if(isEditMode.value) {
            success('Updated successfully', 'Permission Updated');
        } else {
            success('Created successfully', 'Permission Created');
        }

        closeNewPermissionDialog()
    } catch (err: any) {
        // console.error('Error saving permission:', err)
        newPermissionError.value = err.message || 'Error saving permission'
        error(newPermissionError.value, 'Error');
    } finally {
        newPermissionLoading.value = false
    }
}

const handleDelete = async (permission: ApiInstanceRouteUserMap) => {
    const api_user = props.apiUsers?.find(u => u.id === Number(permission.api_user))?.app_name
    if (!confirm(`Are you sure you want to delete the api user "${api_user}"?`)) {
        return
    }

    if (!props.apiInstanceData) {
        console.error('API Instance data not available')
        return
    }
    
    try {
        const updatedApiInstanceData = {
                ...props.apiInstanceData,
                route_user_map: props.apiInstanceData.route_user_map?.filter(existingPermission => 
                    !(existingPermission.api_user === permission.api_user && 
                      existingPermission.route === permission.route && 
                      existingPermission.verb === permission.verb)
                ) || []
            }
        
        const requestData = {
            id: updatedApiInstanceData.id,
            name: updatedApiInstanceData.name,
            route: updatedApiInstanceData.route, 
            route_user_map: updatedApiInstanceData.route_user_map,
            resources: updatedApiInstanceData.resources, 
            options: updatedApiInstanceData.options,
            public: updatedApiInstanceData.public,
            api_id: updatedApiInstanceData.api.id,
            api_version_id: updatedApiInstanceData.api_version_id,
            environment_id: updatedApiInstanceData.environment.id
        }

        props.updateApiInstanceData(requestData)
        success('Deleted successfully', 'Permission Deleted');

    } catch (err: any) {
        // console.error('Error deleting route:', err)
        error(err.message || 'Error deleting permission', 'Error');}
}


const openEditPermissionDialog = (permission: ApiInstanceRouteUserMap, index: number) => {
  isEditMode.value = true
  editingPermissionIndex.value = index
  newPermissionForm.value = {
    user: permission.api_user,
    api_user: permission.api_user,
    verb: permission.verb,
    route: permission.route
  }

  newPermissionDialogOpen.value = true
}

const handleDataGridActionHandler = (actionData: { action: string; selectedRows: any[]; selectedData: any[], selectedIndex: any[] }) => {
    switch (actionData.action) {
        case 'create':
            openNewPermissionDialog()
            break
    }
}   

const handleDataGridRowActionHandler = (actionData: { type: string; payload: any, index: number }) => {

    switch (actionData.type) {
        case 'single-edit':
            newPermissionForm.value = {
                api_user: actionData.payload.api_user,
                user: actionData.payload.api_user,
                verb: actionData.payload.verb,
                route: actionData.payload.route
            }
           
            openEditPermissionDialog(actionData.payload, actionData.index)
            break
        case 'single-delete':
            handleDelete(actionData.payload)
            break
    }
}
const handleDataGridRowClick = (row: any, index: number) => {

    openEditPermissionDialog(row, index)
}

</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
       
            
            <!-- Loading State -->
            <template v-if="loading">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
                        <p class="mt-2">Loading permissions...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiInstanceError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading permissions: {{ apiInstanceError }}</p>
                    </div>
                </div>
            </template>

            <!-- Data Table -->
            <template v-else-if="apiInstanceData?.route_user_map">
                <AlertModal
                    :isOpen="newPermissionDialogOpen"
                    :title="isEditMode ? 'Edit Permission' : 'Create New Permission'"
                    @close="closeNewPermissionDialog"
                >
                    <FormViewer 
                        ref="permissionFormViewer"
                        :formConfig="formConfig" 
                        :initialData="newPermissionForm" 
                        :cancelAction="'close'" 
                        :actionHandler="handleFormAction"
                        :validateOnSubmit="true"
                        :actions="[
                            { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                            { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                        ]" 
                    />

                </AlertModal>
                <DataGrid
                    :schema="permissionSchema"
                    :data="apiInstanceData.route_user_map"
                    :clickableRows="true"
                    :rowActionDropdown="false"
                    :showCheckboxes="true"
                    :actions="[
                        { name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus' }
                    ]"
                    :rowActions="[
                        { type: 'single-edit', label: 'Edit', icon: 'edit', colorClass: 'text-blue-600 hover:bg-blue-50' },
                        { type: 'single-delete', label: 'Delete', icon: 'trash', colorClass: 'text-red-600 hover:bg-red-50' }
                    ]"
                    @actionHandler="handleDataGridActionHandler"
                    @rowActionHandler="handleDataGridRowActionHandler"
                    @rowClick="handleDataGridRowClick"
                />

            
            </template>

            <!-- No Data State -->
            <template v-else>
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <p>No permissions available for this API instance.</p>
                    </div>
                </div>
            </template>
      
    </div>
</template>