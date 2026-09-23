<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem, Resource, Environment } from '@/types'
import { Head } from '@inertiajs/vue3'
import { getCsrfToken } from '@/lib/utils'
import { router } from '@inertiajs/vue3'
import Toaster from '@/components/toaster/Toaster.vue'
import { useToaster } from '@/composables/useToaster'
import DataGrid from '@/components/datagrid/DataGrid.vue'
import FormViewer from '@/components/formviewer/FormViewer.vue'
import AlertModal from '@/components/AlertModal.vue'
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue'

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Resources',
    href: '/resources',
  },
]

const resources = ref<Resource[]>([])
const environments = ref<Environment[]>([])
const loading = ref(true)
const resourceFormRef = ref<InstanceType<typeof FormViewer> | null>(null);

// Toaster
const { success, error, warning, info } = useToaster()

// Resource type options
const resourceTypeOptions = [
  { label: 'Oracle', value: 'oracle' },
  { label: 'MySQL', value: 'mysql' },
  { label: 'Microsoft SQL Server', value: 'sqlsrv' },
  { label: 'Secret Value', value: 'secret' },
  { label: 'Value', value: 'value' }
]

// Computed property to get environment types from environments array
const environmentTypes = computed(() => {
  const types = environments.value.map(env => ({
    value: env.type,
    label: env.type
  }))
  // Remove duplicates
  const uniqueTypes = types.filter((type, index, self) => 
    index === self.findIndex(t => t.value === type.value)
  )
  return uniqueTypes
})

// Form configuration for FormViewer
const formConfig = ref({
  label: 'Resource Form',
  description: '',
  name: "resources-form",
  files: false,
  fields: [
    {
      name: "name",
      label: "Name",
      type: "text",
      placeholder: "Resource Name",
      value: "",
      required: true,
    },
    {
      name: "type",
      label: "Environment Type",
      type: "select",
      placeholder: "Select environment type",
      value: "",
      options: [] as Array<{label: string, value: any}>,
      required: true,
    },
    {
      name: "resource_type",
      label: "Resource Type",
      type: "select",
      placeholder: "Select resource type",
      value: "",
      options: resourceTypeOptions,
      required: true,
    },
    // Oracle fields
    {
      name: "user",
      label: "User",
      type: "text",
      placeholder: "Database Username",
      value: "",
      required: false,
      show: [
				{
					op: "and",
					conditions: [
						{
							type: "matches",
							name: "resource_type",
							value: [
								"oracle", "mysql", "sqlsrv"
							]
						}
					]
				}
			]
    },
    {
      name: "pass",
      label: "Password",
      type: "password",
      placeholder: "Database Password",
      value: "",
      required: false,
      show: [
				{
					op: "and",
					conditions: [
						{
							type: "matches",
							name: "resource_type",
							value: [
								"oracle", "mysql", "sqlsrv"
							]
						}
					]
				}
			]
    },
    {
      name: "tns",
      label: "TNS",
      type: "textarea",
      placeholder: "TNS connection string",
      value: "",
      required: false,
      show: [
				{
					op: "and",
					conditions: [
						{
							type: "matches",
							name: "resource_type",
							value: [
								"oracle"
							]
						}
					]
				}
			]
    },

    // MySQL/SQL Server fields
    {
      name: "db_name",
      label: "Database Name",
      type: "text",
      placeholder: "Database Name",
      value: "",
      required: false,
      show: [
				{
					op: "and",
					conditions: [
						{
							type: "matches",
							name: "resource_type",
							value: [
								"mysql", "sqlsrv"
							]
						}
					]
				}
			]
    },
    {
      name: "server",
      label: "Server",
      type: "text",
      placeholder: "Server Address",
      value: "",
      required: false,
      show: [
				{
					op: "and",
					conditions: [
						{
							type: "matches",
							name: "resource_type",
							value: [
								"mysql", "sqlsrv"
							]
						}
					]
				}
			]
    },
    // Secret/Value fields
    {
      name: "value",
      label: "Value",
      type: "text",
      placeholder: "Value",
      value: "",
      required: false,
      show: [
				{
					op: "and",
					conditions: [
						{
							type: "matches",
							name: "resource_type",
							value: [
								"value"
							]
						}
					]
				}
			]
    },
    {
      name: "secret_value",
      label: "Secret Value",
      type: "password",
      placeholder: "Secret Value",
      value: "",
      required: false,
      show: [
				{
					op: "and",
					conditions: [
						{
							type: "matches",
							name: "resource_type",
							value: [
								"secret"
							]
						}
					]
				}
			]
    }
  ]
})

// DataGrid schema for Resources
const resourcesSchema = {
  label: '',
  description: '',
  name: "resources-schema",
  files: false,
  fields: [
    {
      name: "id",
      label: "ID",
      type: "text",
      placeholder: "Resource ID",
      value: "",
      help: "Unique identifier for the resource",
      info: "Unique identifier for the resource",
      width: "12",
      offset: "0",
      required: true,
      showColumn: true
    },
    {
      name: "name",
      label: "Name",
      type: "text",
      placeholder: "Resource Name",
      value: "",
      help: "Name of the resource",
      info: "Name of the resource",
      width: "12",
      offset: "0",
      required: true,
      showColumn: true
    },
    {
      name: "type",
      label: "Environment Type",
      type: "text",
      placeholder: "Environment Type",
      value: "",
      help: "Environment type for this resource",
      info: "Environment type for this resource",
      width: "12",
      offset: "0",
      options: [] as Array<{label: string, value: any, color: string}>,
      required: true,
      showColumn: true
    },
    {
      name: "resource_type",
      label: "Resource Type",
      type: "text",
      placeholder: "Resource Type",
      value: "",
      help: "Type of the resource",
      info: "Type of the resource",
      width: "12",
      offset: "0",
      options: [
        {
          label: 'MySQL',
          value: 'mysql',
          color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
        },
        {
          label: 'Oracle',
          value: 'oracle',
          color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
        },
        {
          label: 'SQL Server',
          value: 'sqlsrv',
          color: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
        },
        {
          label: 'Secret',
          value: 'secret',
          color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
        },
        {
          label: 'Value',
          value: 'value',
          color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        }
      ],
      required: true,
      showColumn: true
    },
    {
      name: "created_at",
      label: "Created At",
      type: "date",
      placeholder: "Created At",
      value: "",
      help: "Creation date",
      info: "Creation date",
      width: "12",
      offset: "0",
      required: false,
      showColumn: true
    }
  ]
}

// New Resource
const newResourceDialogOpen = ref(false)

interface NewResourceForm {
  name: string
  type: string
  resource_type: string
  user?: string
  pass?: string
  tns?: string
  db_name?: string
  server?: string
  value?: string
  secret_value?: string
  // config: {
  //   user?: string
  //   pass?: string
  //   tns?: string
  //   name?: string
  //   server?: string
  //   value?: string
  // }
}

const newResourceForm = ref<NewResourceForm>({
  name: '',
  type: '',
  resource_type: '',
  user: '',
  pass: '',
  tns: '',
  db_name: '',
  server: '',
  value: '',
  secret_value: ''
})

const newResourceLoading = ref(false)
const newResourceError = ref('')

// Delete confirmation dialog
const showDeleteModal = ref(false)
const pendingDeleteResource = ref<{ resource: Resource; index: number } | null>(null)
const deleting = ref(false)

// Edit Resource
const isEditMode = ref(false)
const editingResourceId = ref<number | null>(null)

// Comment dialog (required before create/update request)
const commentDialogOpen = ref(false)
const commentFormRef = ref<InstanceType<typeof FormViewer> | null>(null)
const commentForm = ref({ comment: '' })
const pendingResourcePayload = ref<Record<string, any> | null>(null)
const pendingRequestMethod = ref<'POST' | 'PUT'>('POST')
const pendingRequestUrl = ref('ajax/resources')

const commentFormConfig = {
  label: '',
  description: '',
  name: 'resource-comment-form',
  showLabel: false,
  files: false,
  fields: [
    {
      name: 'comment',
      label: 'Comment',
      type: 'textarea',
      placeholder: 'Describe why this change is being made',
      value: '',
      required: true,
    },
  ],
}

const resetResourceForm = () => {
  newResourceForm.value = {
    name: '',
    type: '',
    resource_type: '',
    user: '',
    pass: '',
    tns: '',
    db_name: '',
    server: '',
    value: '',
    secret_value: ''
  }
}

const clearPendingSave = () => {
  pendingResourcePayload.value = null
  pendingRequestMethod.value = 'POST'
  pendingRequestUrl.value = 'ajax/resources'
  commentForm.value = { comment: '' }
  isEditMode.value = false
  editingResourceId.value = null
}

const openNewResourceDialog = () => {
  resetResourceForm()
  newResourceError.value = ''
  clearPendingSave()
  newResourceDialogOpen.value = true
}

const closeNewResourceDialog = () => {
  newResourceDialogOpen.value = false
  newResourceError.value = ''
  resetResourceForm()
  if (!commentDialogOpen.value) {
    clearPendingSave()
  }
}

const closeCommentDialog = () => {
  commentDialogOpen.value = false
  clearPendingSave()
  newResourceError.value = ''
}

// Handle FormViewer action events
const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.type) {
    case 'close':
    case 'cancel':
      closeNewResourceDialog()
      break
    case 'save':
      prepareResourceSave(actionData.formData)
      break
    default:
      warning('Unknown FormViewer action type:', actionData.type)
  }
}

const handleCommentFormAction = (actionData: { type: string; action: string; formData: any }) => {
  switch (actionData.type) {
    case 'close':
    case 'cancel':
      closeCommentDialog()
      break
    case 'save':
      submitResourceWithComment(actionData.formData)
      break
    default:
      warning('Unknown FormViewer action type:', actionData.type)
  }
}

const buildResourceRequestData = (formData: any) => {
  const body = isEditMode.value && editingResourceId.value
    ? { ...formData, id: editingResourceId.value }
    : { ...formData }

  let config: Record<string, any> = {}

  switch (body.resource_type) {
    case 'oracle':
      config = {
        user: body.user,
        pass: body.pass,
        tns: body.tns
      }
      break

    case 'mysql':
    case 'sqlsrv':
      config = {
        user: body.user,
        pass: body.pass,
        name: body.db_name,
        server: body.server
      }
      break

    case 'value':
      config = {
        value: body.value
      }
      break

    case 'secret':
      config = {
        value: body.secret_value
      }
      break

    default:
      config = {}
  }

  return {
    name: body.name,
    type: body.type,
    resource_type: body.resource_type,
    config
  }
}

const prepareResourceSave = (formData: any) => {
  if (!resourceFormRef.value) {
    warning('Form is not ready. Please try again.', 'Validation Error');
    return;
  }
  const isValid = resourceFormRef.value?.validateForm();
  if (!isValid) {
    warning('Please fix validation errors before saving.', 'Validation Error');
    return;
  }
  newResourceError.value = ''

  // Duplicate name + environment check
  const duplicate = resources.value.find((res) =>
    res.name.toLowerCase().trim() === formData.name.trim().toLowerCase() &&
    res.type === formData.type &&
    (isEditMode.value ? res.id !== editingResourceId.value : true)
  )

  if (duplicate) {
    newResourceError.value = `A Resource with name "${formData.name}" already exists in environment "${formData.type}".`
    error(newResourceError.value, 'Duplicate Entry')
    return
  }

  pendingResourcePayload.value = buildResourceRequestData(formData)
  pendingRequestUrl.value = isEditMode.value && editingResourceId.value
    ? `ajax/resources/${editingResourceId.value}`
    : 'ajax/resources'
  pendingRequestMethod.value = isEditMode.value && editingResourceId.value ? 'PUT' : 'POST'

  commentForm.value = { comment: '' }
  commentDialogOpen.value = true
  newResourceDialogOpen.value = false
  newResourceError.value = ''
  resetResourceForm()
}

const submitResourceWithComment = async (formData: any) => {
  if (!commentFormRef.value) {
    warning('Form is not ready. Please try again.', 'Validation Error');
    return;
  }
  const isValid = commentFormRef.value?.validateForm();
  if (!isValid) {
    warning('Please enter a comment before saving.', 'Validation Error');
    return;
  }

  const comment = (formData?.comment ?? '').trim()
  if (!comment) {
    warning('Comment is required.', 'Validation Error');
    return;
  }

  if (!pendingResourcePayload.value) {
    error('Nothing to save. Please try again.', 'Error')
    closeCommentDialog()
    return
  }

  newResourceLoading.value = true
  newResourceError.value = ''

  try {
    const requestData = {
      ...pendingResourcePayload.value,
      comment,
    }

    const response = await fetch(pendingRequestUrl.value, {
      method: pendingRequestMethod.value,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken() || '',
      },
      body: JSON.stringify(requestData),
    })

    if (!response.ok) throw new Error('Failed to save Resource')

    if (isEditMode.value) {
      success('Updated successfully', 'Resource Updated')
    } else {
      success('Created successfully', 'Resource Created')
    }

    commentDialogOpen.value = false
    clearPendingSave()
    await fetchResources()
  } catch (err: any) {
    newResourceError.value = err.message || 'Error saving Resource'
    error(newResourceError.value, 'Error')
  } finally {
    newResourceLoading.value = false
  }
}

const handleDeleteResource = (resource: Resource, index: number) => {
  pendingDeleteResource.value = { resource, index }
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  if (deleting.value) return
  showDeleteModal.value = false
  pendingDeleteResource.value = null
}

const confirmDelete = async () => {
  if (!pendingDeleteResource.value) {
    closeDeleteModal()
    return
  }

  const { resource, index } = pendingDeleteResource.value
  deleting.value = true
  try {
    const response = await fetch(`ajax/resources/${resource.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken() || '',
      },
    })
    
    if (!response.ok) {
      error('Failed to delete Resource', 'Error')
      throw new Error('Failed to delete Resource')
    }

    resources.value.splice(index, 1);
    success(`Resource "${resource.name}" deleted successfully`, 'Resource Deleted')
    showDeleteModal.value = false
    pendingDeleteResource.value = null
  } catch (err: any) {
    error(err.message || 'Error deleting Resource', 'Error')
  } finally {
    deleting.value = false
  }
}

const openEditResourceDialog = (resource: Resource) => {
  isEditMode.value = true
  editingResourceId.value = resource.id
  newResourceForm.value = {
    name: resource.name || '',
    type: resource.type || '',
    resource_type: resource.resource_type || '',
    user: resource.config?.user || '',
    pass: resource.config?.pass || '',
    tns: resource.config?.tns || '',
    db_name: resource.config?.name || '',
    server: resource.config?.server || '',
    value: resource.resource_type === 'value' ? resource.config?.value : '',
    secret_value: resource.resource_type === 'secret' ? resource.config?.value : '',
    // config: {
    //   user: resource.config?.user || '',
    //   pass: resource.config?.pass || '',
    //   tns: resource.config?.tns || '',
    //   name: resource.config?.name || '',
    //   server: resource.config?.server || '',
    //   value: resource.config?.value || ''
    // }
  }
  newResourceDialogOpen.value = true
}

const fetchResources = async () => {
  loading.value = true
  try {
    const response = await fetch(`ajax/resources`)
    const data = await response.json()
    resources.value = data.map((res: Resource) => ({
      ...res,
      created_at: res.created_at ? new Date(res.created_at).toLocaleDateString() : '',
    }))
  } catch (e) {
    resources.value = []
    console.error('Error fetching Resources:', e)
  } finally {
    loading.value = false
  }
}

const fetchAllData = async () => {
  loading.value = true
  try {
    const [resourcesResponse, environmentsResponse] = await Promise.all([
      fetch(`ajax/resources`),
      fetch(`api/environments`),
    ])

    if (!resourcesResponse.ok) throw new Error('Failed to fetch resources')
    if (!environmentsResponse.ok) throw new Error('Failed to fetch environments')

    const [resourcesData, environmentsData] = await Promise.all([
      resourcesResponse.json(),
      environmentsResponse.json(),
    ])

    resources.value = resourcesData.map((res: Resource) => ({
      ...res,
       created_at: res.created_at ? new Date(res.created_at).toLocaleDateString() : '',
    }))
    environments.value = environmentsData

    // Update form config with environment types
    formConfig.value.fields[1].options = environmentTypes.value

    // Update schema with environment types for display
    resourcesSchema.fields[2].options = environmentTypes.value.map((env: any) => ({
      label: env.label,
      value: env.value,
      color: env.value === 'test' 
        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
        : env.value === 'dev' 
        ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' 
        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    }))

  } catch (error) {
    console.error('Error fetching data:', error)
  } finally {
    loading.value = false
  }
}

// DataGrid action handlers
const handleDataGridAction = (actionData: { type: string; payload: any, index: number }) => {
  switch (actionData.type) {
    case 'single-edit':
      openEditResourceDialog(actionData.payload)
      break
    case 'single-delete':
      handleDeleteResource(actionData.payload, actionData.index)
      break
    case 'view':
      openEditResourceDialog(actionData.payload)
      break
    default:
      warning('Unknown action type:', actionData.type)
  }
}

const handleDataGridCustomAction = (actionData: { action: string; selectedRows: any[]; selectedData: any[], selectedIndex: any[] }) => {
  switch (actionData.action) {
    case 'create':
      openNewResourceDialog()
      break
    case 'edit':
      openEditResourceDialog(actionData.selectedData[0])
      break
    case 'delete':
      handleDeleteResource(actionData.selectedData[0], actionData.selectedIndex[0])
      break
    default:
      info(`Please implement the ${actionData.action} function`, 'Action Not Implemented')
  }
}

const handleDataGridRowClick = (row: any) => {
  openEditResourceDialog(row)
}

// Lifecycle hooks
onMounted(() => {
  fetchAllData()
})
</script>

<template>
  <Head title="Resources" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      
      <!-- Loading State -->
      <div v-if="loading">
        <div class="flex items-center justify-center h-32">
          <div class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
            <p class="mt-2">Loading Resources...</p>
          </div>
        </div>
      </div>

      <!-- DataGrid Implementation -->
      <DataGrid v-else
        :schema="resourcesSchema"
        :data="resources"
        theme="default"
        :actions="[
          {name: 'create', type: 'success', min: 0, label: 'New', loc: 'left', icon: 'plus'},
          {name: 'edit', type: 'primary', min: 1, max: 1, label: 'Edit', icon: 'edit', loc: 'right'},
          {name: 'delete', type: 'danger', min: 1, max: 1, label: 'Delete', icon: 'trash', loc: 'right'}
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

      <!-- Form Viewer -->
      <AlertModal 
        :isOpen="newResourceDialogOpen"
        :title="isEditMode ? 'Edit Resource' : 'Create New Resource'"
        @close="closeNewResourceDialog"
      >
        <FormViewer 
          ref="resourceFormRef"
          :formConfig="formConfig" 
          :initialData="newResourceForm"
          :cancelAction="'close'"
          :actionHandler="handleFormAction"
          :actions="[
            { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
            { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
          ]"
          :disabled="newResourceLoading"
        />
      </AlertModal>

      <!-- Required comment before create/update request -->
      <AlertModal
        :isOpen="commentDialogOpen"
        title="Save comment"
        @close="() => { if (!newResourceLoading) closeCommentDialog() }"
      >
        <FormViewer
          ref="commentFormRef"
          :formConfig="commentFormConfig"
          :initialData="commentForm"
          :cancelAction="'close'"
          :actionHandler="handleCommentFormAction"
          :actions="[
            { type: 'save', action: 'save', label: 'Confirm', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
            { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
          ]"
          :isSubmitting="newResourceLoading"
          :disabled="newResourceLoading"
        />
      </AlertModal>

      <ConfirmDeleteModal
        :isOpen="showDeleteModal"
        :count="pendingDeleteResource ? 1 : 0"
        :deleting="deleting"
        @confirm="confirmDelete"
        @close="closeDeleteModal"
      />
    </div>
    <Toaster />
  </AppLayout>
</template>