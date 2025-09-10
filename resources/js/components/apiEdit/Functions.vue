<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import Editor from '@/pages/Editor.vue'
import { type ApiData, type ApiVersionFunction } from '@/types'
import {
  Dialog,
  DialogTrigger,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogClose
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { getCsrfToken } from '@/lib/utils'

interface Props {
    api_id: string
    api_type: string
    apiData: ApiData | null
    loadingApiData: boolean
    apiError: string
    updateApiData: (updatedApiData: ApiData) => void
    refreshApiData: () => void
}

const props = defineProps<Props>()

const selectedFunction = ref<ApiVersionFunction | null>(null)
const isSaving = ref(false)
const saveError = ref<string | null>(null)
const saveSuccess = ref(false)
const djangoBaseUrl = import.meta.env.VITE_DJANGO_BASEURL || ''

// New view dialog state
const isNewViewDialogOpen = ref(false)
const newViewName = ref('')
const isCreatingView = ref(false)
const createViewError = ref<string | null>(null)

const handleSave = async (updatedCode: string) => {
    if (!selectedFunction.value || !props.apiData) {
        saveError.value = 'No function selected or API data not available'
        return
    }

    isSaving.value = true
    saveError.value = null
    saveSuccess.value = false

    try {
        const updatedApiData = {
            ...props.apiData,
            version_views: props.apiData.version_views.map(func => 
                func.name === selectedFunction.value?.name 
                    ? { ...func, content: updatedCode }
                    : func
            )
        }

        const response = await fetch(`/ajax/apis/${props.api_id}/code`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken() || '',
            },
            body: JSON.stringify(updatedApiData)
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }

        const result = await response.json()
        
        // Update the local state through parent
        props.updateApiData(result || updatedApiData)
        
        if (selectedFunction.value) {
            selectedFunction.value.content = updatedCode
        }
        
        saveSuccess.value = true
        setTimeout(() => {
            saveSuccess.value = false
        }, 3000)

    } catch (error) {
        console.error('Save error:', error)
        saveError.value = error instanceof Error ? error.message : 'Failed to save changes'
    } finally {
        isSaving.value = false
    }
}

const handleCreateNewView = async () => {
    if (!newViewName.value.trim() || !props.apiData) {
        createViewError.value = 'Please enter a valid function name'
        return
    }

    // Check if function name already exists
    const existingFunction = props.apiData.version_views.find(func => func.name === newViewName.value.trim())
    if (existingFunction) {
        createViewError.value = 'A function with this name already exists'
        return
    }

    isCreatingView.value = true
    createViewError.value = null

    try {
        // TO-DO:: PHP function template
        const newFunction: ApiVersionFunction = {
            name: newViewName.value.trim(),
            content: ``
        }

        const updatedApiData = {
            ...props.apiData,
            version_views: [...props.apiData.version_views, newFunction]
        }
        console.log('Updated API Data:', updatedApiData)
        const response = await fetch(`${djangoBaseUrl}/api/apis/${props.api_id}/code`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(updatedApiData)
        })

        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}))
            throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }

        const result = await response.json()
        
        // Update the local state through parent
        props.updateApiData(result || updatedApiData)
        
        // Select the newly created function
        selectedFunction.value = newFunction
        
        // Reset dialog state
        newViewName.value = ''
        isNewViewDialogOpen.value = false

    } catch (error) {
        console.error('Create view error:', error)
        createViewError.value = error instanceof Error ? error.message : 'Failed to create new function'
    } finally {
        isCreatingView.value = false
    }
}

const resetNewViewDialog = () => {
    newViewName.value = ''
    createViewError.value = null
    isCreatingView.value = false
}

</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
        <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-4 bg-white dark:bg-gray-900">
            
            <!-- Loading State -->
            <template v-if="loadingApiData">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white mx-auto"></div>
                        <p class="mt-2">Loading functions...</p>
                    </div>
                </div>
            </template>

            <!-- Error State -->
            <template v-else-if="apiError">
                <div class="flex items-center justify-center h-32">
                    <div class="text-center text-red-600">
                        <p>Error loading functions: {{ apiError }}</p>
                    </div>
                </div>
            </template>

            <!-- Functions Content -->
            <template v-else-if="apiData?.version_views">
                <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-y-0 lg:space-x-8 h-full">
                    <!-- Function List Sidebar -->
                   <aside class="max-w-xs lg:w-40 lg:min-w-40 lg:flex-shrink-0">
                        <!-- New View Button -->
                        <div class="mb-4">
                            <Dialog v-model:open="isNewViewDialogOpen" @update:open="resetNewViewDialog">
                                <DialogTrigger as-child>
                                    <Button variant="outline" class="w-full text-xs">
                                        + New View
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-md">
                                    <DialogHeader>
                                        <DialogTitle>Create New Function</DialogTitle>
                                    </DialogHeader>
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <Label for="function-name">Function Name</Label>
                                            <Input
                                                id="function-name"
                                                v-model="newViewName"
                                                placeholder="Enter function name"
                                                :disabled="isCreatingView"
                                                @keyup.enter="handleCreateNewView"
                                            />
                                        </div>
                                        
                                        <div v-if="createViewError" class="p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                                            {{ createViewError }}
                                        </div>
                                        
                                        <div class="flex justify-end space-x-2">
                                            <Button 
                                                variant="outline" 
                                                @click="isNewViewDialogOpen = false"
                                                :disabled="isCreatingView"
                                            >
                                                Cancel
                                            </Button>
                                            <Button 
                                                @click="handleCreateNewView"
                                                :disabled="!newViewName.trim() || isCreatingView"
                                            >
                                                <div v-if="isCreatingView" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                                {{ isCreatingView ? 'Creating...' : 'Create' }}
                                            </Button>
                                        </div>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </div>

                        <!-- Function List -->
                        <nav class="flex flex-col space-y-1">
                            <Button
                                v-for="item in apiData.version_views"
                                :key="item.name"
                                variant="ghost"
                                :class="[
                                    'justify-start', 
                                    'px-3', 
                                    'py-1', 
                                    'w-auto', 
                                    'inline-flex', 
                                    'text-xs',
                                    selectedFunction?.name === item.name ? 'bg-accent' : ''
                                ]" 
                                @click="selectedFunction = item"             
                            >
                                {{ item.name }}
                            </Button>
                        </nav>
                    </aside>

                    <!-- Editor Area -->
                    <div class="flex-1 min-w-0">
                        <!-- Save status messages -->
                        <div v-if="saveError" class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
                            {{ saveError }}
                        </div>
                        <div v-if="saveSuccess" class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
                            Changes saved successfully!
                        </div>
                        
                        <!-- Code Editor -->
                        <Editor 
                            v-if="selectedFunction" 
                            :code="selectedFunction.content" 
                            :language="api_type === 'python' || api_type === 'php' ? api_type : undefined"
                            :is-saving="isSaving"
                            @save="handleSave"
                        />
                        
                        <!-- No Function Selected State -->
                        <div v-else class="text-muted-foreground text-sm p-4 text-center">
                            Select a function to view its code.
                        </div>
                    </div>
                </div>
            </template>

            <!-- No Functions Available State -->
            <template v-else>
                <div class="flex items-center justify-center h-32">
                    <div class="text-center">
                        <p>No functions available for this API version.</p>
                    </div>
                </div>
            </template>
        </div>
    </div>
</template>