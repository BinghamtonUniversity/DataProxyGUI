<script setup lang="ts">
import { MoreHorizontal } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

interface Api {
  id: number
  name: string
  description: string
  tags: string
  api_type: string
  user_id: number
  created_at: string
  updated_at: string
  created_by_id: number
  updated_by_id: number
  deleted_at: string | null
}

interface Props {
  api: Api
  onExpand?: () => void
}

const props = defineProps<Props>()
const emit = defineEmits(['edit'])


// const handleCopyId = () => {
//   navigator.clipboard.writeText(props.api.id.toString())
// }

const handleEditApi = () => {
  emit('edit', props.api)
}

</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" class="h-8 w-8 p-0">
        <span class="sr-only">Open menu</span>
        <MoreHorizontal class="h-4 w-4" />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuLabel>Actions</DropdownMenuLabel>
      <!-- <DropdownMenuItem @click="handleCopyId">
        Copy API ID
      </DropdownMenuItem> -->
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="onExpand">
        View details
      </DropdownMenuItem>
      <DropdownMenuItem @click="handleEditApi">Edit API</DropdownMenuItem>
      <DropdownMenuItem class="text-red-600">Delete API</DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>