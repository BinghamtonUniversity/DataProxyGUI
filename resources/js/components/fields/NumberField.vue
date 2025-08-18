<template>
  <div v-if="show" class="number-field-container">
    <!-- Label -->
    <label v-if="label" :for="fieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2" :class="{ 'text-red-500': localError }">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
      <span
        v-if="info"
        class="relative cursor-pointer ml-1"
        @mouseenter="showInfo = true"
        @mouseleave="showInfo = false"
      >
        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 inline" fill="currentColor" viewBox="0 0 20 20">
          <circle cx="10" cy="10" r="9" fill="currentColor"/>
          <text x="10" y="15" text-anchor="middle" font-size="12" fill="white">?</text>
        </svg>
        <span
          v-if="showInfo"
          class="absolute left-1/2 z-10 -translate-x-1/2 mt-2 w-48 p-2 rounded-lg bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white shadow-lg border border-gray-200 dark:border-gray-700"
          style="pointer-events: none;"
        >
          {{ info }}
        </span>
      </span>
    </label>

    <!-- Input Field -->
    <div class="flex items-stretch w-full">
      <!-- Pre (number icon) -->
      <span 
        class="inline-flex items-center justify-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-l-md min-w-[44px]"
      >
        <i class="fa-solid fa-hashtag text-base"></i>
      </span>
      
      <!-- Main Input -->
      <input
        :id="fieldId"
        type="number"
        v-model="internalValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="!edit"
        :readonly="!edit"
        :min="min"
        :max="max"
        :step="step"
        :autocomplete="autocomplete"
        :autofocus="autofocus"
        :name="name"
        class="flex-1 min-w-0 py-2 px-3 text-sm border bg-white dark:!bg-gray-800 text-gray-900 dark:!text-white transition-colors duration-200"
        :class="[
          // Border classes
          'border-l-0',
          'border-t border-b border-gray-300 dark:!border-gray-600',
          // Border radius classes
          'rounded-r-md',
          // Focus states
          'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
          // Disabled states
          !edit ? 'cursor-not-allowed bg-gray-100 dark:!bg-gray-700 text-gray-500 dark:!text-gray-400' : 'hover:border-gray-400 dark:hover:border-gray-500',
          // Error states
          (localError || (props.errors && props.errors.length > 0)) ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : '',
          // Readonly states
          !edit ? 'bg-gray-100 dark:!bg-gray-700' : ''
        ]"
        @input="handleInput"
        @change="handleChange"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
      >
    </div>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (props.errors && props.errors.length > 0)" class="mt-2 text-xs text-red-500">
      {{ localError || props.errors[0] }}
    </div>
    
    <!-- Character Count (if limit is set) -->
    <div v-if="limit" class="mt-1 text-xs text-gray-600 dark:text-gray-400 text-right">
      {{ (internalValue || '').length }} / {{ limit }}
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
    type: [String, Number],
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Enter a number...'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  edit: {
    type: Boolean,
    default: true
  },
  show: {
    type: Boolean,
    default: true
  },
  help: {
    type: String,
    default: ''
  },
  info: {
    type: String,
    default: ''
  },
  limit: {
    type: Number,
    default: null
  },
  min: {
    type: Number,
    default: null
  },
  max: {
    type: Number,
    default: null
  },
  step: {
    type: [Number, String],
    default: 'any'
  },
  autocomplete: {
    type: String,
    default: 'off'
  },
  autofocus: {
    type: Boolean,
    default: false
  },
  validate: {
    type: Array,
    default: () => []
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
const showInfo = ref(false);

// Computed properties
const isDisabled = computed(() => {
  return props.disabled || !props.edit;
});

// Methods
const validate = () => {
  const errors = validateField(internalValue.value, {
    ...props,
    type: 'number'
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

const handleInput = (event) => {
  const inputValue = event.target.value;
  
  // Handle empty value
  if (inputValue === '') {
    internalValue.value = '';
    emit('update:value', '');
    return;
  }

  // Convert to number and validate
  const numValue = Number(inputValue);
  
  // Check if it's a valid number
  if (isNaN(numValue)) {
    internalValue.value = inputValue; // Keep as string for validation
  } else {
    internalValue.value = numValue;
  }

  emit('update:value', internalValue.value);
};

const handleChange = (event) => {
  // Additional validation on change
  validate();
};

const handleBlur = () => {
  validate();
  emit('blur', internalValue.value);
};

const handleFocus = () => {
  emit('focus', internalValue.value);
};

const handleKeydown = (event) => {
  // Let the browser handle all number input behavior natively
  // This allows step controls, decimal input, etc.
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
/* Number input styles - keeping native spinner buttons for step functionality */
input[type="number"] {
  /* Keep native appearance for step controls */
}

/* Force dark mode styles with higher specificity */
.dark .bg-white {
  background-color: rgb(31 41 55) !important; /* gray-800 */
}

.dark .text-gray-900 {
  color: rgb(255 255 255) !important; /* white */
}

.dark .text-gray-600 {
  color: rgb(209 213 219) !important; /* gray-300 */
}

.dark .text-gray-400 {
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark .border-gray-300 {
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark .border-gray-600 {
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark .bg-gray-100 {
  background-color: rgb(55 65 81) !important; /* gray-700 */
}

.dark .bg-gray-700 {
  background-color: rgb(55 65 81) !important; /* gray-700 */
}

.dark .text-gray-500 {
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark .text-red-500 {
  color: rgb(248 113 113) !important; /* red-400 */
}

/* Focus styles */
.focus\:ring-blue-500\/20:focus {
  --tw-ring-opacity: 0.2 !important;
}

.focus\:border-blue-500:focus {
  border-color: rgb(59 130 246) !important; /* blue-500 */
}

/* Error styles */
.border-red-500 {
  border-color: rgb(239 68 68) !important; /* red-500 */
}

.focus\:ring-red-500\/20:focus {
  --tw-ring-opacity: 0.2 !important;
}

.focus\:border-red-500:focus {
  border-color: rgb(239 68 68) !important; /* red-500 */
}
</style> 