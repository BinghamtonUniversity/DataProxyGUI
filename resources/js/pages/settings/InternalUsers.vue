<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { ref, onMounted, computed } from 'vue';
import { getCsrfToken } from '@/lib/utils';

const apiBaseUrl = '/api/internal-users';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Internal Users', href: '/settings/users' },
];

const page = usePage();
const currentUserId = computed(() => (page.props.auth?.user as { id?: number } | undefined)?.id ?? null);
const isSuperAdmin = computed(() => (page.props.auth?.user as { super_admin?: boolean } | undefined)?.super_admin ?? false);

const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<InternalUser | null>(null);
const submitting = ref(false);

const users = ref<InternalUser[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const { success, error: showError, warning } = useToaster();

interface InternalUser {
    id: number;
    name: string;
    email: string;
    unique_id: string | null;
    super_admin: boolean;
    created_at?: string;
    updated_at?: string;
}

const formConfig = {
    label: 'Internal Users',
    description: 'Manage GUI (internal) users',
    name: 'internal-users-form',
    files: false,
    fields: [
        {
            name: 'id',
            label: 'ID',
            type: 'hidden',
            value: '',
            width: '12',
            offset: '0',
            required: false,
            showColumn: false,
            edit: false,
            show: false,
        }, 
        {
            name: 'name',
            label: 'Name',
            type: 'text',
            placeholder: 'Enter the user\'s name',
            value: '',
            width: '12',
            offset: '0',
            edit: false,
            required: true,
        },
        {
            name: 'email',
            label: 'Email',
            type: 'email',
            placeholder: 'Enter the email address',
            value: '',
            width: '12',
            offset: '0',
            edit: false,
            required: true,
        },
        {
            name: 'unique_id',
            label: 'Unique ID',
            type: 'text',
            placeholder: 'Optional unique identifier (e.g. from OIDC)',
            value: '',
            help: 'Unique identifier for the user',
            width: '12',
            offset: '0',
            edit: false,
            required: false,
        },
        {
            name: 'super_admin',
            label: 'Super Admin',
            type: 'checkbox',
            placeholder: '',
            value: false,
            options: [
                { label: 'No', value: false, color: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' },
                { label: 'Yes', value: true, color: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' },
            ],
            help: 'Super admins can manage internal users and proxy servers.',
            width: '12',
            offset: '0',
            required: false,
        },
    ],
};

function cleanFormData(formData: Record<string, unknown>) {
    const cleaned = { ...formData };
    delete (cleaned as Record<string, unknown>).id;
    if (cleaned.super_admin !== undefined && cleaned.super_admin !== null) {
        cleaned.super_admin = cleaned.super_admin === true || cleaned.super_admin === 'true' || cleaned.super_admin === 1;
    }
    Object.keys(cleaned).forEach((key) => {
        if (cleaned[key] === '') {
            cleaned[key] = null;
        }
    });
    return cleaned;
}

async function fetchUsers() {
    try {
        loading.value = true;
        error.value = null;
        const response = await fetch(apiBaseUrl, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const data = await response.json();
        users.value = data;
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Failed to fetch internal users';
        error.value = message;
        showError('Failed to fetch internal users. Please try again.', 'Error');
        console.error('Error fetching internal users:', err);
    } finally {
        loading.value = false;
    }
}

function openNewModal() {
    modalMode.value = 'new';
    editingRow.value = null;
    showModal.value = true;
}

function openEditModal(row?: InternalUser) {
    if (row) {
        modalMode.value = 'edit';
        editingRow.value = {
            id: row.id,
            name: row.name,
            email: row.email,
            unique_id: row.unique_id ?? '',
            super_admin: !!row.super_admin,
        };
        showModal.value = true;
    } else {
        warning('Please select exactly one row to edit.', 'Selection Required');
    }
}

function closeModal() {
    showModal.value = false;
    editingRow.value = null;
}

async function handleFormSubmit(formValues: Record<string, unknown>) {
    try {
        submitting.value = true;
        const cleaned = cleanFormData(formValues);

        if (modalMode.value === 'new') {
            const response = await fetch(apiBaseUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(cleaned),
            });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error((errorData as { message?: string }).message || `HTTP error! status: ${response.status}`);
            }
            const newUser = await response.json();
            users.value.push(newUser);
            success(`User "${String(formValues.name)}" added successfully!`, 'User Added');
        } else if (editingRow.value) {
            const response = await fetch(`${apiBaseUrl}/${editingRow.value.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin',
                body: JSON.stringify(cleaned),
            });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error((errorData as { message?: string }).message || `HTTP error! status: ${response.status}`);
            }
            const updatedUser = await response.json();
            const index = users.value.findIndex((u) => u.id === editingRow.value!.id);
            if (index !== -1) users.value[index] = updatedUser;
            success(`User "${String(formValues.name)}" updated successfully!`, 'User Updated');
        }
        closeModal();
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Failed to save user. Please try again.';
        showError(message, 'Error');
        console.error('Form submission error:', err);
    } finally {
        submitting.value = false;
    }
}

function handleFormAction(actionData: { type: string; action: string; formData: Record<string, unknown> }) {

    switch (actionData.type) {
        case 'close':
        case 'cancel':
            closeModal();
            break;
        case 'save':
            handleFormSubmit(actionData.formData);
            break;
        default:
            break;
    }
}

async function handleDelete(selectedRowIds?: number[]) {
    if (!selectedRowIds?.length) {
        warning('Please select at least one user to delete.', 'Selection Required');
        return;
    }
    const toDelete = users.value.filter((u) => selectedRowIds.includes(u.id));
    if (toDelete.some((u) => u.id === currentUserId.value)) {
        showError('You cannot delete your own account.', 'Error');
        return;
    }
    try {
        for (const user of toDelete) {
            const response = await fetch(`${apiBaseUrl}/${user.id}`, {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken() || '',
                },
                credentials: 'same-origin',
            });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error((errorData as { message?: string }).message || `Failed to delete user ${user.name}`);
            }
        }
        toDelete.forEach((user) => {
            const index = users.value.findIndex((u) => u.id === user.id);
            if (index !== -1) users.value.splice(index, 1);
        });
        if (toDelete.length === 1) {
            success(`User "${toDelete[0].name}" deleted successfully!`, 'User Deleted');
        } else {
            success(`${toDelete.length} users deleted successfully!`, 'Users Deleted');
        }
    } catch (err: unknown) {
        const message = err instanceof Error ? err.message : 'Failed to delete users. Please try again.';
        showError(message, 'Error');
        console.error('Delete error:', err);
    }
}

const gridActions = computed(() => {
    const actions: Array<{ name: string; type: string; min: number; max: number; label: string; icon: string; loc: string }> = [
        { name: 'edit', type: 'primary', min: 1, max: 1, label: 'Edit', icon: 'edit', loc: 'right' },
    ];
    if (isSuperAdmin.value) {
        actions.unshift({
            name: 'impersonate',
            type: 'warning',
            min: 1,
            max: 1,
            label: 'Impersonate',
            icon: 'user',
            loc: 'left',
        });
    }
    return actions;
});

async function impersonateUser(user: InternalUser) {
    if (!isSuperAdmin.value) {
        showError('Only super admins can impersonate.', 'Unauthorized');
        return;
    }
    if (user.id === currentUserId.value) {
        showError('You cannot impersonate yourself.', 'Error');
        return;
    }
    try {
        const response = await fetch(`${apiBaseUrl}/${user.id}/impersonate`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            credentials: 'same-origin',
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            showError((data as { message?: string }).message || 'Impersonation failed.', 'Error');
            return;
        }
        const redirect = (data as { redirect?: string }).redirect;
        if (redirect) {
            window.location.href = redirect;
        } else {
            window.location.reload();
        }
    } catch (err) {
        console.error('Impersonate error:', err);
        showError('Failed to impersonate user.', 'Error');
    }
}

function handleDataGridActionHandler(actionData: {
    action: string;
    selectedRows: unknown[];
    selectedData: InternalUser[];
}) {
    switch (actionData.action) {
        // case 'create':
        //     openNewModal();
        //     break;
        case 'edit':
            openEditModal(actionData.selectedData[0]);
            break;
        case 'impersonate':
            impersonateUser(actionData.selectedData[0]);
            break;
        // case 'delete':
        //     if (actionData.selectedData[0]?.id === currentUserId.value) {
        //         showError('You cannot delete your own account.', 'Error');
        //         return;
        //     }
        //     handleDelete([actionData.selectedData[0].id]);
        //     break;
        default:
            warning(`Unknown action type: ${actionData.action}`, 'Error');
            break;
    }
}

onMounted(() => {
    fetchUsers();
});
</script>

<template>
    <Head title="Internal Users" />
    <AppLayout :breadcrumbs="breadcrumbItems">
        <SettingsLayout>
            <div class="space-y-6">
                <div v-if="loading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                    <span class="ml-3 text-muted-foreground">Loading internal users...</span>
                </div>

                <div v-else-if="error" class="flex justify-center items-center py-12">
                    <div class="text-destructive text-center">
                        <p class="font-semibold">Error loading internal users</p>
                        <p class="text-sm mt-1">{{ error }}</p>
                        <button
                            type="button"
                            @click="fetchUsers"
                            class="mt-3 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:opacity-90"
                        >
                            Try Again
                        </button>
                    </div>
                </div>

                <DataGrid
                    v-else
                    :schema="formConfig"
                    :data="users"
                    theme="default"
                    :actions="gridActions"
                    @action-handler="handleDataGridActionHandler"
                />

                <AlertModal
                    :is-open="showModal"
                    :title="modalMode === 'new' ? 'Add Internal User' : 'Edit Internal User'"
                    @close="closeModal"
                >
                    <FormViewer
                        :form-config="formConfig"
                        :initial-data="editingRow ?? undefined"
                        cancel-action="close"
                        :action-handler="handleFormAction"
                        :actions="[
                                                { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                                                { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                        ]"
                        :disabled="submitting"
                    />
                </AlertModal>

                <Toaster />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
