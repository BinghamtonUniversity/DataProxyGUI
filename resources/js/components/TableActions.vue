<script setup lang="ts" generic="T extends { id: number | string; api_type?: string }">
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
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'


interface Props<T> {
  item: T
  viewDetailsHref?: string
  showViewDetails?: boolean
  showEdit?: boolean
  showDelete?: boolean
  editLabel?: string
  deleteLabel?: string
}

const props = withDefaults(defineProps<Props<T>>(), {
  showViewDetails: true,
  showEdit: true,
  showDelete: true,
  editLabel: (p) => p.item?.api_type ? 'Edit API' : 'Edit',
  deleteLabel: 'Delete'
})

const emit = defineEmits<{
  edit: [item: T]
  delete: [item: T]
}>()

// Generate view details href if not provided
const viewDetailsLink = computed(() => {
  if (props.viewDetailsHref) {
    return props.viewDetailsHref
  }
  
  // Default pattern for APIs
  if (props.item.api_type) {
    return `/apis/${props.item.api_type}/${props.item.id}/routes`
  }
  
  // Default fallback
  return `/items/${props.item.id}`
})

const handleEdit = () => {
  emit('edit', props.item)
}

const handleDelete = () => {
  emit('delete', props.item)
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
      <DropdownMenuSeparator />
      
      <DropdownMenuItem v-if="showViewDetails" :as-child="true">
        <Link
          class="block w-full text-left"
          :href="viewDetailsLink"
          as="button"
        >
          View details
        </Link>
      </DropdownMenuItem>
      
      <DropdownMenuItem v-if="showEdit" @click="handleEdit">
        {{ editLabel }}
      </DropdownMenuItem>
      
      <DropdownMenuItem v-if="showDelete" @click="handleDelete" class="text-red-600">
        {{ deleteLabel }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>