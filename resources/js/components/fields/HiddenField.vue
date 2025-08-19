<template>
  <div v-if="show" class="hidden-field-container">
    <!-- Hidden Input -->
    <input
      :id="fieldId"
      type="hidden"
      v-model="internalValue"
      :name="name"
      :value="internalValue"
      :required="required"
      :disabled="!edit"
    >
    
    <!-- Optional Debug Display (only in development) -->
    <div v-if="showDebug && edit" class="mt-2 p-2 bg-gray-100 dark:bg-gray-700 rounded border border-gray-300 dark:border-gray-600">
      <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">
        <strong>Hidden Field:</strong> {{ label || name }}
      </div>
      <div class="text-xs text-gray-900 dark:text-white">
        <strong>Value:</strong> {{ internalValue || '(empty)' }}
      </div>
      <div v-if="help" class="text-xs text-gray-600 dark:text-gray-400 mt-1">
        <strong>Help:</strong> {{ help }}
      </div>
      <div v-if="localError || (props.errors && props.errors.length > 0)" class="text-xs text-red-500 mt-1">
        <strong>Error:</strong> {{ localError || props.errors[0] }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { validateField } from './validation.js';

// Props
const props = defineProps({
  // Field identification
  name: {
    type: String,
    required: true
  },
  fieldId: {
    type: String,
    default: () => `field_${Math.random().toString(36).substr(2, 9)}`
  },
  label: {
    type: String,
    default: ''
  },
  value: {
    type: [String, Number, Boolean],
    default: ''
  },
  required:  { type: [Boolean,String,Array], default: true },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  edit: { type: [Boolean,String,Array], default: true },
  show:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  help: {
    type: String,
    default: ''
  },
  info: {
    type: String,
    default: ''
  },
  autocomplete: {
    type: String,
    default: 'off'
  },
  validate: {
    type: Array,
    default: () => []
  },
  showDebug: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Array,
    default: () => []
  }
});

// Emits
const emit = defineEmits(['update:value', 'validation-error', 'validation-success', 'blur', 'focus']);

// Reactive state
const internalValue = ref(props.value);
const localError = ref('');

// Computed properties
const isDisabled = computed(() => {
  return props.disabled || !props.edit;
});

// Methods
const validate = () => {
  const errors = validateField(internalValue.value, {
    ...props,
    type: 'hidden'
  });

  localError.value = errors[0] || '';

  // Emit validation events
  if (errors.length > 0) {
    emit('validation-error', {
      field: props.name,
      errors: errors,
      value: internalValue.value
    });
  } else {
    emit('validation-success', {
      field: props.name,
      value: internalValue.value
    });
  }

  return errors.length === 0;
};

// Watchers
watch(() => props.value, (newValue) => {
  internalValue.value = newValue;
}, { immediate: true });

watch(() => props.validate, () => {
  validate();
}, { deep: true });

// Lifecycle
onMounted(() => {
  if (internalValue.value !== '' && internalValue.value !== null && internalValue.value !== undefined) {
    validate();
  }
});
</script>

<style scoped>
/* Hidden field styles - minimal styling since it's hidden */
.hidden-field-container {
  /* Hidden fields don't need much styling */
}

/* Debug display styles */
.dark .bg-gray-100 {
  background-color: rgb(55 65 81) !important; /* gray-700 */
}

.dark .text-gray-600 {
  color: rgb(209 213 219) !important; /* gray-300 */
}

.dark .text-gray-400 {
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark .text-gray-900 {
  color: rgb(255 255 255) !important; /* white */
}

.dark .text-gray-700 {
  color: rgb(229 231 235) !important; /* gray-200 */
}

.dark .border-gray-300 {
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark .border-gray-600 {
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark .text-red-500 {
  color: rgb(248 113 113) !important; /* red-400 */
}
</style> 