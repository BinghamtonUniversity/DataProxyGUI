<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ApiInstance, ApiUser, Resource, type ApiData } from '@/types'

interface Props {
    instance_id: string
    apiInstanceData: ApiInstance | null,
    apiUsers: ApiUser | null,
    resources: Resource | null,
    loading: boolean,
    apiInstanceError: string
    updateApiInstanceData: (updatedApiInstanceData: ApiInstance) => void
}

const props = defineProps<Props>()


</script>

<template>
   
</template>