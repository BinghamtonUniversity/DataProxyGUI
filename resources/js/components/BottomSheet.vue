<template>
  <div v-if="isOpen" class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div 
      class="fixed inset-0 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
      @click="closeModal">
    </div>
    
    <!-- Bottom Sheet -->
    <div 
      :class="[
        'fixed inset-x-0 bottom-0 bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 rounded-t-xl shadow-xl overflow-hidden transition-transform duration-300 ease-out',
        isOpen ? 'translate-y-0' : 'translate-y-full',
        maxHeightClass
      ]">
      <!-- Header -->
      <div class="relative flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ title }}
        </h3>
        <button 
          @click="closeModal"
          class="ring-offset-background focus:ring-ring data-[state=open]:bg-accent data-[state=open]:text-muted-foreground absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-offset-2 focus:outline-hidden disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          <span class="sr-only">Close</span>
        </button>
      </div>
      
      <!-- Content -->
      <div :class="contentClass">
        <slot></slot>
      </div>
      
      <!-- Footer -->
      <div v-if="$slots.footer" class="flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700">
        <slot name="footer"></slot>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineProps, defineEmits } from 'vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Bottom Sheet'
  },
  maxHeight: {
    type: String,
    default: '85vh',
    validator: (value) => {
      // Allow common height values like '85vh', '90vh', '500px', etc.
      return /^\d+(vh|px|rem)$/.test(value)
    }
  },
  showCloseButton: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['close'])

const maxHeightClass = computed(() => {
  return `max-h-[${props.maxHeight}]`
})

const contentClass = computed(() => {
  const baseClass = 'overflow-y-auto bg-gray-100 dark:bg-gray-700'
  const heightClass = props.maxHeight === '85vh' ? 'max-h-[calc(85vh-80px)]' : `max-h-[calc(${props.maxHeight}-80px)]`
  return `${baseClass} ${heightClass}`
})

const closeModal = () => {
  emit('close')
}
</script>
