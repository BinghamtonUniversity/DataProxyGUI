<template>
  <div v-if="show" class="date-field-container">
    <!-- Label -->
    <label v-if="label" :for="fieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
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
      <!-- Pre (dynamic icon based on type) -->
      <span 
        class="inline-flex items-center justify-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-l-md min-w-[44px]"
      >
        <i :class="iconClass" class="text-base"></i>
      </span>
      
      <!-- Main Input -->
      <input
        :id="fieldId"
        :type="inputType"
        v-model="internalValue"
        :placeholder="placeholder || defaultPlaceholder"
        :required="required"
        :disabled="!edit"
        :readonly="!edit"
        :min="minDate"
        :max="maxDate"
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
    
    <!-- Date Range Info (if min/max set) -->
    <div v-if="minDate || maxDate" class="mt-1 text-xs text-gray-600 dark:text-gray-400">
      <span v-if="minDate && maxDate">Date range: {{ formatDate(minDate) }} to {{ formatDate(maxDate) }}</span>
      <span v-else-if="minDate">From: {{ formatDate(minDate) }}</span>
      <span v-else-if="maxDate">Until: {{ formatDate(maxDate) }}</span>
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
  
  // Display properties
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  help: {
    type: String,
    default: ''
  },
  info: {
    type: String,
    default: ''
  },
  
  // Value and model
  value: {
    type: [String, Number],
    default: ''
  },
  
  // Form builder attributes
  show: {
    type: Boolean,
    default: true
  },
  edit: {
    type: Boolean,
    default: true
  },
  parse: {
    type: Boolean,
    default: true
  },
  required: {
    type: Boolean,
    default: false
  },
  
  // Date specific properties
  minDate: {
    type: String,
    default: ''
  },
  maxDate: {
    type: String,
    default: ''
  },
  format: {
    type: Object,
    default: () => ({})
  },
  
  // Accessibility
  autocomplete: {
    type: String,
    default: 'off'
  },
  autofocus: {
    type: Boolean,
    default: false
  },
  
  // Error handling
  localError: {
    type: String,
    default: ''
  },
  errors: {
    type: Array,
    default: () => []
  },
  
  // Features
  validate: {
    type: Array,
    default: () => []
  },
  matchValues: {
    type: Object,
    default: undefined
  },
  
  // Field type for validation
  type: {
    type: String,
    default: 'date'
  }
});

// Emits
const emit = defineEmits([
  'update:value',
  'change',
  'blur',
  'focus',
  'keydown',
  'validation-error',
  'validation-success'
]);

// Reactive state
const showInfo = ref(false);
const internalValue = ref(props.value);
const localError = ref('');

watch(() => props.value, (val) => {
  internalValue.value = val;
});

// Computed properties
const inputType = computed(() => {
  if (!props.format || !props.format.input) return 'date';
  
  const format = props.format.input;
  
  // Map specific accepted formats to HTML input types
  switch (format) {
    case 'h:mm A':
      return 'time';
    case 'MM':
    case 'YYYY':
      return 'number';
    case 'MM/YYYY':
      return 'month';
    case 'MM/DD':
      return 'date';
    case 'MM/DD/YYYY':
    case 'MM/DD/YYYY h:mm A':
    default:
      return 'date';
  }
});

const defaultPlaceholder = computed(() => {
  if (!props.format || !props.format.input) return 'Select date';
  
  const format = props.format.input;
  
  switch (format) {
    case 'h:mm A':
      return 'Select time';
    case 'MM':
      return 'Enter month (1-12)';
    case 'YYYY':
      return 'Enter year';
    case 'MM/YYYY':
      return 'Select month and year';
    case 'MM/DD':
      return 'Select month and day';
    case 'MM/DD/YYYY':
      return 'Select date';
    case 'MM/DD/YYYY h:mm A':
      return 'Select date and time';
    default:
      return 'Select date';
  }
});

const iconClass = computed(() => {
  const type = inputType.value;
  const format = props.format?.input;
  
  // Special handling for number inputs based on format
  if (type === 'number') {
    if (format === 'MM') return 'fa-regular fa-calendar';
    if (format === 'YYYY') return 'fa-solid fa-calendar';
  }
  
  switch (type) {
    case 'time':
      return 'fa-regular fa-clock';
    case 'month':
      return 'fa-regular fa-calendar';
    case 'number':
      return 'fa-solid fa-calendar';
    default:
      return 'fa-solid fa-calendar';
  }
});

// Methods
const handleInput = (event) => {
  emit('update:value', event.target.value);
  // Don't validate on every input for better performance
  // Validation will happen on blur instead
};

const handleChange = (event) => {
  emit('change', event);
};

const handleBlur = (event) => {
  emit('blur', event);
  const errors = validateField(event.target.value, props, props.matchValues);
  if (errors.length > 0) {
    localError.value = errors[0];
    emit('validation-error', { field: props.name, errors, value: event.target.value });
  } else {
    localError.value = '';
    emit('validation-success', { field: props.name, value: event.target.value });
  }
};

const handleFocus = (event) => {
  emit('focus', event);
};

const handleKeydown = (event) => {
  emit('keydown', event);
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString();
  } catch {
    return dateString;
  }
};

// Lifecycle
onMounted(() => {
  if (props.autofocus) {
    const input = document.getElementById(props.fieldId);
    if (input) {
      input.focus();
    }
  }
});
</script>

<style scoped>
.date-field-container {
  width: 100%;
}

/* Custom focus ring animation */
.focus\:ring-2:focus {
  animation: focusRing 0.2s ease-out;
}

@keyframes focusRing {
  0% {
    box-shadow: 0 0 0 0 rgb(59 130 246 / 0.2); /* blue-500, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(59 130 246 / 0.2);
  }
}

.dark .focus\:ring-2:focus {
  animation: focusRingDark 0.2s ease-out;
}

@keyframes focusRingDark {
  0% {
    box-shadow: 0 0 0 0 rgb(59 130 246 / 0.3); /* blue-500, 30% opacity for dark mode */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(59 130 246 / 0.3);
  }
}

/* Error focus ring */
.focus\:ring-red-500\/20:focus {
  animation: focusRingError 0.2s ease-out;
}

@keyframes focusRingError {
  0% {
    box-shadow: 0 0 0 0 rgb(239 68 68 / 0.2); /* red-500, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(239 68 68 / 0.2);
  }
}

.dark .focus\:ring-red-500\/20:focus {
  animation: focusRingErrorDark 0.2s ease-out;
}

@keyframes focusRingErrorDark {
  0% {
    box-shadow: 0 0 0 0 rgb(239 68 68 / 0.3); /* red-500, 30% opacity for dark mode */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(239 68 68 / 0.3);
  }
}

/* Smooth transitions */
.transition-colors {
  transition: all 0.2s ease-in-out;
}

/* Force dark mode styles with higher specificity */
.dark .date-field-container input[type="date"],
.dark .date-field-container input[type="time"],
.dark .date-field-container input[type="month"],
.dark .date-field-container input[type="number"] {
  background-color: rgb(31 41 55) !important; /* gray-800 */
  color: rgb(255 255 255) !important; /* white */
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark .date-field-container input[type="date"]:focus,
.dark .date-field-container input[type="time"]:focus,
.dark .date-field-container input[type="month"]:focus,
.dark .date-field-container input[type="number"]:focus {
  border-color: rgb(59 130 246) !important; /* blue-500 */
  box-shadow: 0 0 0 2px rgb(59 130 246 / 0.2) !important;
}

.dark .date-field-container input[type="date"]:hover:not(:disabled),
.dark .date-field-container input[type="time"]:hover:not(:disabled),
.dark .date-field-container input[type="month"]:hover:not(:disabled),
.dark .date-field-container input[type="number"]:hover:not(:disabled) {
  border-color: rgb(107 114 128) !important; /* gray-500 */
}

.dark .date-field-container input[type="date"]:disabled,
.dark .date-field-container input[type="time"]:disabled,
.dark .date-field-container input[type="month"]:disabled,
.dark .date-field-container input[type="number"]:disabled {
  background-color: rgb(55 65 81) !important; /* gray-700 */
  color: rgb(156 163 175) !important; /* gray-400 */
}

/* Date/Time input specific styles */
input[type="date"]::-webkit-calendar-picker-indicator,
input[type="time"]::-webkit-calendar-picker-indicator,
input[type="month"]::-webkit-calendar-picker-indicator {
  filter: invert(0.5);
}

.dark input[type="date"]::-webkit-calendar-picker-indicator,
.dark input[type="time"]::-webkit-calendar-picker-indicator,
.dark input[type="month"]::-webkit-calendar-picker-indicator {
  filter: invert(1);
}

/* Info tooltip positioning */
.info-tooltip {
  position: absolute;
  z-index: 1000;
  pointer-events: none;
}
</style> 