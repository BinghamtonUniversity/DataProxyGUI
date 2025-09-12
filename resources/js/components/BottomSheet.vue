<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Backdrop -->
    <div 
      class="fixed inset-0   backdrop-blur-sm transition-opacity"
      @click="closeModal">
    </div>
    
    <!-- Bottom Sheet -->
    <div 
      :class="[
        'fixed inset-x-0 bottom-0 bg-white dark:bg-gray-800 rounded-t-xl shadow-xl overflow-hidden transition-transform duration-300 ease-out',
        isOpen ? 'translate-y-0' : 'translate-y-full',
        maxHeightClass
      ]">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ title }}
        </h3>
        <button 
          @click="closeModal"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
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
