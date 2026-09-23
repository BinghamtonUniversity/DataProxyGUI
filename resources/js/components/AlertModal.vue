<template>
  <div v-if="isOpen" class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div 
      class="fixed inset-0 bg-black/80 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
      @click="closeModal">
    </div>
    
    <!-- Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
      <div 
        :class="[
          'relative bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 w-full max-w-[calc(100%-2rem)] grid gap-4 rounded-lg border p-6 shadow-lg duration-200 my-auto',
          width
        ]"
        @click.stop>
        
        <!-- Header -->
        <div class="flex flex-col gap-2 text-center sm:text-left">
          <h3 class="text-lg leading-none font-semibold">
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
        <div>
          <slot></slot>
        </div>
        
        <!-- Footer -->
        <div v-if="$slots.footer" class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <slot name="footer"></slot>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Alert'
  },
  width: {
    type: String,
    default: 'sm:max-w-lg'
  }
});

const emit = defineEmits(['close']);

function closeModal() {
  emit('close');
}
</script> 