<template>
  <div v-if="show" :class="containerClass">
    <!-- Label -->
    <label v-if="label" :for="fieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2" :class="{ 'text-red-500': localError }">
      {{ label }}
      <span v-if="required === true || required === 'true'" class="text-red-500 ml-1">*</span>
      <span
        v-if="info"
        class="relative cursor-pointer ml-1"
        @mouseenter="showInfo = true || showInfo === 'true'"
        @mouseleave="showInfo = false || showInfo === 'false'"
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
    <div class="flex items-stretch w-full" c>
      <!-- Pre (prefix) -->
      <span 
        v-if="pre"
        class="inline-flex items-center justify-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-l-md"
      >
        {{ pre }}
      </span>
      
      <!-- Main Input -->
      <input
        :id="fieldId"
        type="text"
        v-model="internalValue"
        :placeholder="placeholder"
        :required="required"
        :disabled="!edit"
        :readonly="!edit"
        :pattern="pattern"
        :autocomplete="autocomplete"
        :autofocus="autofocus"
        :name="name"
        :class="inputClass"
        @input="handleInput"
        @change="handleChange"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="(event) => emit('keydown', event)"
      >
      
      <!-- Post (suffix) -->
      <span 
        v-if="post"
        class="inline-flex items-center justify-center px-3 border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-r-md"
      >
        {{ post }}
      </span>
    </div>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Character Count (if limit is set) -->
    <div v-if="limit" class="mt-1 text-xs text-gray-600 dark:text-gray-400 text-right">
      {{ (internalValue || '').length }} / {{ limit }}
    </div>
    
    <!-- Error Message -->
    <div v-if="localError || (errors && errors.length > 0)" class="mt-2 text-xs text-red-500">
      {{ localError || errors[0] }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { 
  generateFieldId, 
  createEventHandlers, 
  getFieldContainerClass,
  getSafeFieldValue 
} from './functions.js';
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
    default: () => generateFieldId('text')
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
  
  // Input properties
  required:  { type: [Boolean,String,Array], default: false },
  show:  { type: [Boolean,String,Array], default: true },
  edit:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  
  // Fieldset context
  inFieldset: {
    type: Boolean,
    default: false
  },
  
  // Validation
  limit: {
    type: Number,
    default: null
  },
  pattern: {
    type: String,
    default: ''
  },
  errors: {
    type: Array,
    default: () => []
  },
  
  // Accessibility
  autocomplete: {
    type: String,
    default: ''
  },
  autofocus: {
    type: Boolean,
    default: false
  },
  
  // Visual properties
  pre: {
    type: String,
    default: ''
  },
  post: {
    type: String,
    default: ''
  },
  
  // Error handling
  localError: {
    type: String,
    default: ''
  },
  
  // Features
  validate: {
    type: Array,
    default: () => []
  },
  matchValues: {
    type: Object,
    default: undefined
  }
});

// Emits
const emit = defineEmits([
  'update:value',
  'input',
  'change',
  'blur',
  'focus',
  'keydown',
  'validation-error',
  'validation-success'
]);

// Reactive state
const showInfo = ref(false);
const isFocused = ref(false);
const internalValue = ref(props.value);
const localError = ref('');

// Create event handlers using shared functions
const { handleChange, handleBlur, handleFocus, handleInput } = createEventHandlers(
  props, 
  emit, 
  { internalValue, localError }, 
  'text'
);

// Computed properties
const containerClass = computed(() => getFieldContainerClass(props, props.inFieldset));

const inputClass = computed(() => [
  'bg-white dark:bg-gray-900',
  'w-full px-3 py-2 text-sm border rounded-md transition-colors duration-200',
  'dark:!bg-gray-800 text-gray-900 dark:!text-white',
  'border-gray-300 dark:!border-gray-600',
  'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
  'placeholder-gray-500 dark:placeholder-gray-400',
  // Disabled states
  !props.edit ? 'cursor-not-allowed bg-gray-100 dark:!bg-gray-700 text-gray-500 dark:!text-gray-400 border-gray-200 dark:!border-gray-600 opacity-75' : 'hover:border-gray-400 dark:hover:border-gray-500',
  // Error states
  (localError.value || (props.errors && props.errors.length > 0)) ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : '',
  // Readonly states
  !props.edit ? 'bg-gray-100 dark:!bg-gray-700' : ''
]);

// Watchers
watch(() => props.value, (newValue) => {
  internalValue.value = newValue;
}, { immediate: true });

watch(() => props.validate, () => {
  // Validation will happen on blur instead
});

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
.text-field-container {
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
    box-shadow: 0 0 0 0 rgb(96 165 250 / 0.2); /* blue-400, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(96 165 250 / 0.2);
  }
}

/* Error focus ring */
.focus\:ring-error-20:focus {
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

.dark .focus\:ring-error-20:focus {
  animation: focusRingErrorDark 0.2s ease-out;
}

@keyframes focusRingErrorDark {
  0% {
    box-shadow: 0 0 0 0 rgb(248 113 113 / 0.2); /* red-400, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(248 113 113 / 0.2);
  }
}

/* Error text and border */
.text-error {
  color: rgb(239 68 68) !important; /* red-500 */
}

.dark .text-error {
  color: rgb(248 113 113) !important; /* red-400 */
}

.border-error {
  border-color: rgb(239 68 68) !important; /* red-500 */
}

.dark .border-error {
  border-color: rgb(248 113 113) !important; /* red-400 */
}

/* Smooth transitions */
.transition-colors {
  transition: all 0.2s ease-in-out;
}

/* Info tooltip positioning */
.info-tooltip {
  position: absolute;
  z-index: 1000;
  pointer-events: none;
}

/* Force dark mode styles for input fields */
.dark input[type="text"] {
  background-color: rgb(31 41 55) !important; /* gray-800 */
  color: rgb(255 255 255) !important; /* white */
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark input[type="text"]::placeholder {
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark input[type="text"]:disabled {
  background-color: rgb(55 65 81) !important; /* gray-700 */
  color: rgb(156 163 175) !important; /* gray-400 */
}
</style> 