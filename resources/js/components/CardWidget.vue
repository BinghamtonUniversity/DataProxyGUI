<template>
  <div
    :class="[
      'relative overflow-hidden rounded-xl border bg-card p-6 transition-all duration-200 card-widget-container  ',
      clickable ? 'cursor-pointer hover:bg-accent/50 hover:shadow-md card-widget-container-clickable' : '',
      disabled ? 'opacity-50 cursor-not-allowed' : '',
      customClass ? customClass : '',
    ]"
    @click="handleClick"
    @mouseenter="isHovered = true"
    @mouseleave="isHovered = false"
  >
    <!-- Icon/Header Section (optional) -->
    <div v-if="icon || title" class="flex items-center justify-between mb-4">
      <div v-if="title" class="flex-1">
        <h3 class="text-lg font-semibold text-card-foreground">{{ title }}</h3>
        <p v-if="subtitle" class="text-sm text-muted-foreground mt-1">{{ subtitle }}</p>
      </div>
      <div 
        v-if="icon" 
        :class="[
          'rounded-full p-3 transition-colors',
          iconBgColor || 'bg-blue-100 dark:bg-blue-900/30',
          clickable && isHovered ? (iconHoverColor || 'bg-blue-200 dark:bg-blue-900/50') : ''
        ]"
      >
        <component 
          :is="icon" 
          :class="[
            'h-6 w-6',
            iconColor || 'text-blue-600 dark:text-blue-400'
          ]"
        />
      </div>
    </div>

    <!-- Main Content Slot -->
    <div class="card-content">
      <slot />
    </div>

    <!-- Action Buttons (icon buttons) -->
    <div v-if="actions && actions.length > 0" class="absolute top-4 right-4 flex items-center gap-1">
      <button
        v-for="(action, index) in actions"
        :key="action.type || index"
        @click.stop="handleActionClick(action, props.payload)"
        type="button"
        :disabled="action.disabled || disabled"
        :title="action.tooltip || action.label || action.type"
        :class="[
          'icon-action-btn',
          action.class || '',
          action.disabled || disabled ? 'opacity-50 cursor-not-allowed' : ''
        ]"
      >
        <component
          v-if="action.icon"
          :is="action.icon"
          :class="['h-4 w-4', action.iconClass || '']"
        />
      </button>
    </div>

    <!-- Footer/Action Section (optional) -->
    <div v-if="footerText || $slots.footer" class="mt-4 flex items-center text-sm text-muted-foreground" :class="clickable && isHovered ? 'text-foreground' : ''">
      <slot name="footer">
        <span v-if="footerText">{{ footerText }}</span>
        <component 
          v-if="footerIcon && footerText" 
          :is="footerIcon" 
          class="ml-2 h-4 w-4"
        />
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Component } from 'vue';

interface Action {
  type: string;
  action: string;
  icon?: Component;
  label?: string;
  tooltip?: string;
  disabled?: boolean;
  class?: string;
  iconClass?: string;
}

interface Props {
  title?: string;
  subtitle?: string;
  payload?: any;
  icon?: Component;
  iconColor?: string;
  iconBgColor?: string;
  iconHoverColor?: string;
  footerText?: string;
  footerIcon?: Component;
  clickable?: boolean;
  disabled?: boolean;
  onClick?: () => void | Promise<void>;
  customClass?: string;
  actions?: Action[];
  actionHandler?: (action: { type: string; action: string , payload: any }) => void | Promise<void>;
}

const props = withDefaults(defineProps<Props>(), {
  clickable: true,
  disabled: false,
  customClass: '',
  actions: () => [],
});

const emit = defineEmits(['action', 'customAction']);

const isHovered = ref(false);

const handleClick = () => {
  if (props.disabled || !props.clickable) return;
  
  if (props.onClick) {
    props.onClick();
  }
};

const handleActionClick = async ( action: Action, payload: any ) => {
  if (props.disabled || action.disabled) return;
  
  // If actionHandler is provided, call it first
  if (props.actionHandler && typeof props.actionHandler === 'function') {
    try {
      await props.actionHandler({ type: action.type, action: action.action, payload: props.payload });
      return;
    } catch (error) {
      console.error('Error in actionHandler:', error);
    }
  }
  
  // Emit action events for parent to handle
  emit('action', { type: action.type, action: action.action });
  emit('customAction', { type: action.type, action: action.action });
};
</script>

<style scoped>

.card-widget-container {
  position: relative;
  overflow: hidden;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  transition: all 0.2s ease-in-out;

}
.card-widget-container-clickable:hover {
  background-color: rgb(231, 231, 231);
  box-shadow: 0 1px 2px 0 rgb(255, 255, 255, 0.05);
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}
.dark .card-widget-container {
  border-color: #374151;
  background-color: #1f2937;
  color: #e5e7eb;
  box-shadow: 0 1px 2px 0 rgb(255, 255, 255, 0.05);
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}
.dark .card-widget-container:hover {
  border-color: #374151;
  background-color: rgb(0, 32, 77);
  color: #e5e7eb;
  box-shadow: 0 1px 2px 0 rgb(255, 255, 255, 0.05);
  transition: all 0.2s ease-in-out;
  cursor: pointer;
}
.card-content {
  min-height: 1rem;
}

.icon-action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.375rem;
  border-radius: 0.375rem;
  background: transparent;
  color: #6b7280;
  transition: all 0.15s;
  border: none;
  cursor: pointer;
}

.icon-action-btn:hover:not(:disabled) {
  background: rgba(0, 0, 0, 0.05);
  color: #374151;
}

.dark .icon-action-btn {
  color: #9ca3af;
}

.dark .icon-action-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
  color: #e5e7eb;
}

.icon-action-btn:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}

.icon-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
