<template>
  <Button
    :type="type"
    :variant="variant"
    :size="size"
    :disabled="disabled || loading"
    :class="customClass"
    @click="handleClick"
    :title="tooltip || label"
  >
    <!-- Loading Spinner -->
    <svg
      v-if="loading"
      class="animate-spin h-4 w-4"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle
        class="opacity-25"
        cx="12"
        cy="12"
        r="10"
        stroke="currentColor"
        stroke-width="4"
      ></circle>
      <path
        class="opacity-75"
        fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 2.627 0 6h2zm2 5.291A7.962 7.962 0 014 12H0c0 3.313 2.686 6 6 6v-2.709z"
      ></path>
    </svg>

    <!-- Left Icon -->
    <component
      v-else-if="icon && !iconRight"
      :is="icon"
      :class="['h-4 w-4', iconClass]"
    />

    <!-- Button Content -->
    <span v-if="label || $slots.default">
      <slot>{{ label }}</slot>
    </span>

    <!-- Right Icon -->
    <component
      v-if="icon && iconRight && !loading"
      :is="icon"
      :class="['h-4 w-4', iconClass]"
    />
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { Component } from 'vue';
import type { ButtonVariants } from '@/components/ui/button';

interface Props {
  label?: string;
  icon?: Component;
  iconRight?: boolean;
  iconClass?: string;
  variant?: ButtonVariants['variant'];
  size?: ButtonVariants['size'];
  type?: 'button' | 'submit' | 'reset';
  disabled?: boolean;
  loading?: boolean;
  tooltip?: string;
  onClick?: () => void | Promise<void>;
  customClass?: string;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'default',
  type: 'button',
  disabled: false,
  loading: false,
  iconRight: false,
  iconClass: '',
  customClass: '',
});

const handleClick = () => {
  if (props.disabled || props.loading) return;
  
  if (props.onClick) {
    props.onClick();
  }
};
</script>

