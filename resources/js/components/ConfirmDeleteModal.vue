<template>
  <AlertModal
    :isOpen="isOpen"
    title="Confirm delete"
    width="sm:max-w-md"
    @close="handleClose"
  >
    <div class="space-y-4">
      <div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
        <p>Are you sure you want to delete {{ count }} records?</p>
        <p>This operation can not be undone.</p>
      </div>
      <div class="flex justify-end gap-2">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium text-white bg-teal-700 hover:bg-teal-800 rounded-full focus:outline-none focus:ring-2 focus:ring-teal-500/20 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          :disabled="deleting"
          @click="$emit('confirm')"
        >
          <span v-if="deleting" class="inline-flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            OK
          </span>
          <span v-else>OK</span>
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium text-gray-800 bg-cyan-100 hover:bg-cyan-200 rounded-full focus:outline-none focus:ring-2 focus:ring-cyan-500/20 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
          :disabled="deleting"
          @click="handleClose"
        >
          Cancel
        </button>
      </div>
    </div>
  </AlertModal>
</template>

<script setup>
import AlertModal from '@/components/AlertModal.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  count: {
    type: Number,
    default: 1,
  },
  deleting: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['confirm', 'close']);

function handleClose() {
  if (props.deleting) return;
  emit('close');
}
</script>
