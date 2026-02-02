<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DataGrid from '@/components/datagrid/DataGrid.vue';
import FormViewer from '@/components/formviewer/FormViewer.vue';
import AlertModal from '@/components/AlertModal.vue';
import Toaster from '@/components/toaster/Toaster.vue';
import { useToaster } from '@/composables/useToaster';
import { ref, watch, nextTick } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'DataGrid Example',
        href: '/datagrid-example',
    },
];

// Modal state
const showModal = ref(false);
const modalMode = ref<'new' | 'edit'>('new');
const editingRow = ref<any>(null);

// Row selection state (will be managed by DataGrid)
const selectedRows = ref<number[]>([]);
const dataGridRef = ref<any>(null);

// Toaster
const { success, error, warning, info } = useToaster();

// Form configuration (similar to what FormBuilder generates)
const formConfig = {
    label: 'Sample Users DataGrid',
    description: 'A list of sample users with their information.',
    fields: [
        { name: 'name', label: 'Name', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true },
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            options: [
                { value: 'active', label: 'Active' },
                { value: 'inactive', label: 'Inactive' },
                { value: 'pending', label: 'Pending' }
            ]
        }
    ]
};

// Form data (array of objects with field values)
const formData = ref([
    { id: 1, name: 'Alice', email: 'alice@example.com', status: 'active' },
    { id: 2, name: 'Bob', email: 'bob@example.com', status: 'inactive' },
    { id: 3, name: 'Charlie', email: 'charlie@example.com', status: 'pending' },
    { id: 4, name: 'Diana', email: 'diana@example.com', status: 'active' },
    { id: 5, name: 'Eve', email: 'eve@example.com', status: 'inactive' }
]);

// Custom action functions
const viewUser = (user: any) => {
    console.log('View user:', user);
    // Implement view user logic
};

// Row selection functions (managed by DataGrid)
const syncSelection = () => {
    if (dataGridRef.value && dataGridRef.value.selectedRows) {
        selectedRows.value = [...dataGridRef.value.selectedRows];
    }
};

const clearSelection = () => {
    selectedRows.value = [];
    if (dataGridRef.value && dataGridRef.value.selectedRows) {
        dataGridRef.value.selectedRows = [];
    }
};

// Modal functions
const openNewModal = () => {
    modalMode.value = 'new';
    editingRow.value = null;
    showModal.value = true;
};

const openEditModal = (row?: any) => {
    // If row is provided (from DataGrid edit event), use it directly
    if (row) {
        modalMode.value = 'edit';
        editingRow.value = { ...row };
        showModal.value = true;
        return;
    }
    
    // Show warning if no row is provided
    warning('Please select exactly one row to edit.', 'Selection Required');
};

const closeModal = () => {
    showModal.value = false;
    editingRow.value = null;
};

const handleFormAction = (actionData: { type: string; action: string; formData: any }) => {
    switch (actionData.type) {
        case 'save':
            handleFormSubmit(actionData.formData);
            break;
        case 'close':
            closeModal();
            break;
        default:
            console.log('Unknown FormViewer action type:', actionData.type);
    }
};
const handleFormSubmit = (formValues: any) => {
    try {
        if (modalMode.value === 'new') {
            // Add new row
            const newId = Math.max(...formData.value.map((row: any) => row.id)) + 1;
            const newRow = { id: newId, ...formValues };
            formData.value.push(newRow);
            success(`User "${formValues.name}" added successfully!`, 'User Added');
        } else if (modalMode.value === 'edit' && editingRow.value) {
            // Update existing row
            const index = formData.value.findIndex((row: any) => row.id === editingRow.value.id);
            if (index !== -1) {
                formData.value[index] = { ...editingRow.value, ...formValues };
                success(`User "${formValues.name}" updated successfully!`, 'User Updated');
            }
        }
        closeModal();
    } catch (err) {
        error('Failed to save user. Please try again.', 'Error');
        console.error('Form submission error:', err);
    }
};

const handleDelete = (selectedRowIds?: number[]) => {
    // If selectedRowIds is provided (from DataGrid delete event), use it directly
    if (selectedRowIds && selectedRowIds.length > 0) {
        const rowsToDelete = formData.value.filter(row => selectedRowIds.includes(row.id));
        
        // Remove the selected rows
        rowsToDelete.forEach(row => {
            const index = formData.value.findIndex(r => r.id === row.id);
            if (index !== -1) {
                formData.value.splice(index, 1);
            }
        });
        
        // Show success message
        if (rowsToDelete.length === 1) {
            success(`User "${rowsToDelete[0].name}" deleted successfully!`, 'User Deleted');
        } else {
            success(`${rowsToDelete.length} users deleted successfully!`, 'Users Deleted');
        }
    } else {
        warning('Please select at least one row to delete.', 'Selection Required');
    }
};

// Watch for changes in formData to sync selection when rows are deleted
watch(formData, () => {
    nextTick(() => {
        syncSelection();
    });
}, { deep: true });

// Sync selection when component mounts
nextTick(() => {
    if (dataGridRef.value) {
        syncSelection();
    }
});
</script>

<template>
    <Head title="DataGrid Example" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <!-- New User Button -->
   
            
            <DataGrid    
                ref="dataGridRef"
                :schema="formConfig"
                :data="formData"
                theme="default"
                :showNew="true"
                :showEdit="true"
                :showDelete="true"
                @create="openNewModal"
                @edit="openEditModal"
                @delete="handleDelete">

                <!-- Custom row actions slot -->
                <template #row-actions="{ row, closeMenu }">
                    <button 
                        @click="viewUser(row); closeMenu()" 
                        class="block w-full text-left px-4 py-2 text-sm text-blue-600 hover:bg-blue-50">
                        View Details
                    </button>
                </template>
            </DataGrid>

            <!-- Modal for New/Edit Form -->
            <AlertModal 
                :isOpen="showModal"
                :title="modalMode === 'new' ? 'Add New User' : 'Edit User'"
                @close="closeModal"
            >
                <FormViewer 
                    :formConfig="formConfig" 
                    :initialData="editingRow"
                    :actionHandler="handleFormAction"
                    :actions="[
                        { type: 'save', action: 'save', label: 'Save', modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors' },
                        { type: 'cancel', action: 'close', label: 'Cancel', modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-500/20 transition-colors' }
                    ]"
                />
                
                <template #footer>
                    <div class="flex justify-end space-x-3">
                        <button 
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </template>
            </AlertModal>
            
            <!-- Global Toaster -->
            <Toaster />
        </div>
    </AppLayout>
</template>
