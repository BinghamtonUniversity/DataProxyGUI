<template>
  <div v-if="isOpen" :class="modalClasses.overlay">
    <!-- Backdrop -->
    <div 
      :class="modalClasses.backdrop"
      @click="closeModal">
    </div>
    
    <!-- Modal -->
    <div :class="modalClasses.container">
      <div 
        :class="modalClasses.dialog"
        @click.stop>
        
        <!-- Header -->
        <div :class="modalClasses.header">
          <h3 :class="modalClasses.title">
            {{ title }}
          </h3>
          <button 
            @click="closeModal"
            :class="modalClasses.closeButton">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <!-- Content -->
        <div :class="modalClasses.content">
          <slot></slot>
        </div>
        
        <!-- Footer -->
        <div v-if="$slots.footer" :class="modalClasses.footer">
          <slot name="footer"></slot>
        </div>
        
        <!-- Default footer with close button if no custom footer -->
        <!-- <div v-else :class="modalClasses.footer">
          <button 
            @click="closeModal"
            :class="modalClasses.secondaryButton">
            Close
          </button>
        </div> -->
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';
import { getThemeClasses } from './Theme.js';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Alert'
  }
});

const emit = defineEmits(['close']);

const modalClasses = computed(() => getThemeClasses('modal'));

function closeModal() {
  emit('close');
}
</script> 