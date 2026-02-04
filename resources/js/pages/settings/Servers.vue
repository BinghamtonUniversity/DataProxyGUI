<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem, type ProxyServer } from '@/types';    
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import { useToaster } from '@/composables/useToaster';
import { onMounted, ref } from 'vue';
import CardWidget from '@/components/CardWidget.vue';
import ButtonWidget from '@/components/ButtonWidget.vue';
import AlertModal from '@/components/AlertModal.vue';
import { Plus, Pencil, Server, Trash, Check, Eye } from 'lucide-vue-next';
const { success, error, warning, info } = useToaster();

interface Props {
    server_slug?: string;
}

const props = defineProps<Props>();

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
        { name: 'slug', label: 'Slug', type: 'text', required: true, width: 6, placeholder: 'Enter a slug (e.g. server1)', pattern: '^[a-z]+(_[0-9]+)?$' },
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
            console.log('Unknown action type:', action.type);
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
                initialData.value.servers = data;
                success(isEditing.value ? 'Server updated successfully' : 'Server added successfully', 'Success');
                closeModal();
                await getServers();
            } catch (err: any) {
                error(err.message || 'Failed to save server', 'Error');
            }
        } else {
            error('Please fix validation errors before saving', 'Validation Error');
        }
    } else if (action.type === 'cancel') {
        closeModal();
    }
}
const handleServerAction = async (action: { type: string; action: string; payload: any }) => {
    console.log('Server action:', action);
    debugger;
    switch (action.type) {
        case 'edit':
            editServer(action.payload);
            break;
        case 'delete':
            await deleteServer(action.payload.id);
            break;
        case 'check_password':
            // await checkPassword(action.payload.id);
            warning('Password checked is in development', 'Warning');
            break;
        default:
            console.log('Unknown action type:', action.type);
    }
}
// const checkPassword = async (id: number) => {
//     const confirmed = confirm('Are you sure you want to check the password for this server?');
//     if (!confirmed) {
//         return;
//     }
//     const response = await fetch(`/api/proxy-servers/${id}/check-password`, {
//         method: 'GET',
//         headers: {
//             'Accept': 'application/json',
//             'Content-Type': 'application/json',
//             'X-CSRF-TOKEN': getCsrfToken() || '',
//         },
//     });
//     if (!response.ok) {
//         const errorData = await response.json().catch(() => ({}));
//         error(errorData.message || `HTTP error! status: ${response.status}`);
//         return;
//     }
//     const data = await response.json();
//     success('Password checked successfully', 'Success');
// }
const deleteServer = async (id: number) => {
    if (confirm('Are you sure you want to delete this server?')) {
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
    }
}
onMounted(() => {
    getServers();
})

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Servers settings" />

        <SettingsLayout>
            <!-- <div class="w-full max-w-none">
                <FormViewer 
                ref="serversFormRef"
                :formConfig="formConfig" 
                :initialData="initialData"
                :validateOnSubmit="false"
                :actions="[{ type: 'save', action: 'save', label: 'Save', icon: 'save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' }]" 
                :actionHandler="handleFormAction"
                />
            </div> -->
            <!-- // For each server display a card widget with the server name, slug, server URL, username, password, and active status -->
            <ButtonWidget 
                :label="'Add Server'"
                :icon="Plus"
                :onClick="addServer"
            />
            <!-- grid of servers -->
            <div class="grid grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-4">
            <div v-for="server in initialData.servers as ProxyServer[]" :key="server.id" class="mb-4">
                <CardWidget
                    :payload="server" 
                    :title="server.name" 
                    :subtitle="'Slug: ' + server.slug" 
                    :footerText="'Server: ' + server.server" 
                    :footerIcon="Server"
                    :actions="[
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
                        },
                        {
                            type: 'check_password',
                            action: 'check_password',
                            icon: Eye,
                            iconClass: 'text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20',
                            label: 'Check Password',
                        }
                    ]"
                    :clickable="false"
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
        </SettingsLayout>
        
    
    </AppLayout>
</template>
    