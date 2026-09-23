<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { type BreadcrumbItem, type ProxyServer } from '@/types';    
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import { useToaster } from '@/composables/useToaster';
import { onMounted, ref, computed } from 'vue';
import CardWidget from '@/components/CardWidget.vue';
import ButtonWidget from '@/components/ButtonWidget.vue';
import AlertModal from '@/components/AlertModal.vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import { Plus, Pencil, Server, Trash, Check, Eye } from 'lucide-vue-next';
import { useProxyServer } from '@/composables/useProxyServer';
const { buildUrl, serverSlug } = useProxyServer();
const { success, error, warning, info } = useToaster();
const isLoading = ref(false);

// Delete confirmation dialog
const showDeleteModal = ref(false);
const pendingDeleteServerId = ref<number | null>(null);
const deleting = ref(false);

const page = usePage();
const canManage = computed(() => (page.props.can as { manage_users?: boolean })?.manage_users ?? false);
interface Props {
    server_slug?: string;
}

const props = defineProps<Props>();
const currentServerSlug = computed(() => serverSlug.value);
const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Servers settings',
        href: props.server_slug ? `${props.server_slug}/settings/servers` : '/settings/servers',
    },
];
// Get CSRF token from meta tag
const getCsrfToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return token;
};

const getServers = async () => {
    isLoading.value = true;
    const response = await fetch('/api/proxy-servers', {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken() || '',
        },
        credentials: 'same-origin'
    });
    const data = await response.json();
    initialData.value.servers = data.servers;
    isLoading.value = false;
}
const initialData = ref({
    servers: []
});

const serversFormRef = ref<InstanceType<typeof FormViewer> | null>(null);
const isModalOpen = ref(false);
const currentServer = ref<ProxyServer | null>(null);
const isEditing = ref(false);

// Form config for single server (used in modal)
const serverFormConfig = {
    label: 'Server Configuration',
    description: 'Configure your proxy server settings',
    name: 'server-form',
    fields: [
        { name: 'id', label: 'ID', type: 'hidden', required: false, show: false, value: null },
        { name: 'name', label: 'Name', type: 'text', required: true, width: 6, placeholder: 'Enter server name' },
        { name: 'slug', label: 'Slug', type: 'text', required: true, width: 6, placeholder: 'Enter a slug (e.g. server1)', pattern: '^[a-z]+(_[a-z0-9]+)*$' },
        { name: 'server', label: 'Server URL', type: 'url', required: true, placeholder: 'Enter the server URL (e.g. https://binghamton.edu)' },
        { name: 'type', label: 'Type', type: 'select', required: true, options: [
            { label: 'PHP Proxy Server', value: 'php' },
            { label: 'Python Proxy Server', value: 'python' },
        ] },
        { name: 'username', label: 'Username', type: 'text', required: true, width: 6, placeholder: 'Enter username' },
        { name: 'password', label: 'Password', type: 'password', required: true, width: 6, placeholder: 'Enter password' },
        { name: 'is_active', label: 'Active', type: 'checkbox', required: false, value: false, options: [
            { label: 'false', value: false },
            { label: 'true', value: true }
        ]},
    ]
};

// Original form config (for bulk editing - commented out)
const formConfig = {
    label: 'Servers settings',
    description: 'Update your account\'s servers settings',
    name: 'servers-form',
    fields: [
        { name: 'servers', label: 'Server', type: 'fieldset',
        array: {
            min: 0,
            max: 20
        },
            fields: [
                { name: 'id', label: 'ID', type: 'hidden', required: false, show:false, value: null, },
                { name: 'name', label: 'Name', type: 'text', required: true ,width: 6 },
                { name: 'slug', label: 'Slug', type: 'text', placeholder: 'Enter a slug for the server (e.g. server1)',required: true ,width: 6 },
                { name: 'server', label: 'Server', type: 'text', required: true ,placeholder: 'Enter the server URL (e.g. https://binghamton.edu)'},
                { name: 'type', label: 'Type', type: 'select', required: true, options: [
                    { label: 'PHP Proxy Server', value: 'php' },
                    { label: 'Phyton Proxy Server', value: 'python' },
                ] },
                { name: 'username', label: 'Username', type: 'text', required: true ,placeholder: 'Enter the username for the server (e.g. admin)'},
                { name: 'password', label: 'Password', type: 'password', required: true ,placeholder: 'Enter the password for the server (e.g. password)'},
                { name: 'is_active', label: 'Active', type: 'checkbox', required: false, value: false, options: [
                    { label: 'false', value: false,  },
                    { label: 'true', value: true,}
                ]},
            ]
        }
    ]
}
const saveServers = async () => {
    const response = await fetch('/api/proxy-servers/bulk', {
        method: 'PUT',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken() || '',
        },
        credentials: 'same-origin',
        body: JSON.stringify(initialData.value.servers),
    });
    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}))
        error(errorData.message || `HTTP error! status: ${response.status}`)
        return
    }
    const data = await response.json();
    initialData.value.servers = data;
    success('Servers saved successfully', 'Success');
    // Refresh the servers list
    await getServers();
}
const handleFormAction = (action: { type: string; action: string; formData: any }) => {

    
    switch (action.type) {
        case 'save':
            
            if (serversFormRef.value?.validateForm()) {
                saveServers();
                
            } else {
                error('Please fix validation errors before saving', 'Validation Error');
            }
            break;
        default:
            warning('Unknown action type:', action.type);
    }
}

const addServer = () => {
    currentServer.value = null;
    isEditing.value = false;
    isModalOpen.value = true;
}

const editServer = (server: ProxyServer) => {
    currentServer.value = { ...server };
    isEditing.value = true;
    isModalOpen.value = true;
}

const closeModal = () => {
    isModalOpen.value = false;
    currentServer.value = null;
    isEditing.value = false;
}

const handleServerFormAction = async (action: { type: string; action: string; formData: any }) => {
    if (action.type === 'save' || action.type === 'submit') {
        if (serversFormRef.value?.validateForm()) {
            const serverData = action.formData;
            
            try {
                // Prepare the server data for bulk update
                
                
                const response = await fetch(isEditing.value ? '/api/proxy-servers/' + currentServer.value?.id : '/api/proxy-servers', {
                    method: isEditing.value ? 'PUT' : 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken() || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(serverData),
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    error(errorData.message || `HTTP error! status: ${response.status}`);
                    return;
                }
                
                const data = await response.json();
                if (isEditing.value) {
                    const server = initialData.value.servers.find((server: ProxyServer) => server.id === data.id);
                    if (server) {
                        Object.assign(server, data);
                    }
                } else {    
                    initialData.value.servers.push(data as never);
                }
                success(isEditing.value ? 'Server updated successfully' : 'Server added successfully', 'Success');
                
                closeModal();
                // await getServers();
            } catch (err: any) {
                error(err.message || 'Failed to save server', 'Error');
            }
        } else {
            error('Please fix validation errors before saving', 'Validation Error');
        }
    } else if (action.type === 'close' || action.type === 'cancel') {
        closeModal();
    }
}
const makeCurrent = async (slug: string) => {
    router.visit(`/${slug}/settings/servers`);
}
const handleServerAction = async (action: { type: string; action: string; payload: any }) => {

    switch (action.type) {
        case 'edit':
            editServer(action.payload);
            break;
        case 'delete':
            await deleteServer(action.payload.id);
            break;
        case 'make_current':
            await makeCurrent(action.payload.slug);
            break;
        default:
            error('Unknown action type:', action.type);
            warning('Unknown action type:', action.type);
    }
}

const deleteServer = (id: number) => {
    pendingDeleteServerId.value = id;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    if (deleting.value) return;
    showDeleteModal.value = false;
    pendingDeleteServerId.value = null;
};

const confirmDelete = async () => {
    const id = pendingDeleteServerId.value;
    if (id == null) {
        closeDeleteModal();
        return;
    }

    deleting.value = true;
    try {
        const response = await fetch(`/api/proxy-servers/${id}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            error(errorData.message || `HTTP error! status: ${response.status}`);
            return;
        }
        const data = await response.json();
        initialData.value.servers = data;
        success('Server deleted successfully', 'Success');
        await getServers();
        showDeleteModal.value = false;
        pendingDeleteServerId.value = null;
    } finally {
        deleting.value = false;
    }
};
onMounted(() => {
    getServers();
})

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Servers settings" />

        <SettingsLayout>

            <div v-if="initialData.servers.length == 0 && !isLoading" class="flex justify-center items-center py-12">
                <div class="text-center">
                    <span class="text-gray-600 dark:text-gray-300">No servers found. Please add a server to get started.</span>
                </div>
            </div>
            <div v-if="isLoading" class="flex justify-center items-center py-12">
                <div class="text-center">
                    <span class="text-gray-600 dark:text-gray-300">Loading servers...</span>
                </div>
            </div>
            <!-- // For each server display a card widget with the server name, slug, server URL, username, password, and active status -->
            <ButtonWidget 
                v-if="canManage"
                :label="'Add Server'"
                :icon="Plus"
                :onClick="addServer"
            />
            <!-- grid of servers -->
            <div class="grid sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3  gap-4">
            <div v-for="server in initialData.servers as ProxyServer[]" :key="server.id" class="mb-2">
                <CardWidget
                    :customClass="currentServerSlug == server.slug ? 'border-green-600 dark:border-green-600 border-2' : ''"
                    :payload="server" 
                    :clickable="true"
                    :onClick="() => makeCurrent(server.slug)"
                    :title="currentServerSlug == server.slug ? server.name + ' ( Current )' : server.name" 
                    :subtitle="'Slug: ' + server.slug" 
                    :footerText="'Server: ' + server.server" 
                    :footerIcon="Server"
                    :actions="currentServerSlug == server.slug ? 
                    
                    (canManage ? [{
                            type: 'edit',
                            action: 'edit',
                            icon: Pencil,
                            iconClass: 'text-blue-600 hover:bg-blue-50 dark:text-white-400 dark:hover:bg-white-900/20',
                            label: 'Edit',
                        }] : [])
                         : [
                        {
                            type: 'make_current', 
                            action: 'make_current', 
                            icon: Check, 
                            iconClass: 'text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20', 
                            label: 'Make Current'
                        },
                        ...(canManage ? [
                        {
                            type: 'edit',
                            action: 'edit',
                            icon: Pencil,
                            iconClass: 'text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20',
                            label: 'Edit',
                        },
                        {
                            type: 'delete',
                            action: 'delete',
                            icon: Trash,
                            iconClass: 'text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20',
                            label: 'Delete',
                        }] : []),
                        
                    ]"
                  
                    :actionHandler="handleServerAction"
                >
                    <!-- Content goes here between the opening and closing tags -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Type:</span>
                            <span class="text-sm font-medium capitalize", :class="server.type === 'php' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-1 rounded-md' : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 px-2 py-1 rounded-md'">{{ server.type || 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Username:</span>
                            <span class="text-sm font-medium">{{ server.username || 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Password:</span>
                            <span class="text-sm font-medium">{{ server.password ? '••••••••' : 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-muted-foreground">Status:</span>
                            <span 
                                :class="[
                                    'text-sm font-medium px-2 py-1 rounded',
                                    server.is_active 
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' 
                                        : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                ]"
                            >
                                {{ server.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    </CardWidget>
                </div>
            </div>
            
            <!-- AlertModal with FormViewer for Add/Edit Server -->
            <AlertModal
                :isOpen="isModalOpen"
                :title="isEditing ? 'Edit Server' : 'Add New Server'"
                width="sm:max-w-2xl"
                @close="closeModal"
            >
                <FormViewer
                    ref="serversFormRef"
                    :formConfig="serverFormConfig"
                    :initialData="currentServer || {}"
                    :validateOnSubmit="true"
                    :actions="[
                        { 
                            type: 'save', 
                            action: 'save', 
                            label: isEditing ? 'Update Server' : 'Add Server', 
                            modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' 
                        },
                        { 
                            type: 'cancel', 
                            action: 'close', 
                            label: 'Cancel', 
                            modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' 
                        },
                    ]"
                    :actionHandler="handleServerFormAction"
                />
            </AlertModal>

            <ConfirmDeleteModal
                :isOpen="showDeleteModal"
                :count="pendingDeleteServerId != null ? 1 : 0"
                :deleting="deleting"
                @confirm="confirmDelete"
                @close="closeDeleteModal"
            />
        </SettingsLayout>
        
    
    </AppLayout>
</template>
    