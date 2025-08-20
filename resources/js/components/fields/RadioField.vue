<template>
  <div v-if="show" class="radio-field-container">
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

    <!-- Radio Options -->
    <div class="space-y-2">
      <!-- Option Groups -->
      <template v-for="option in processedOptions" :key="option.value || option.group">
        <!-- Optgroup with label -->
        <div v-if="option.group && option.group.trim() !== ''" class="optgroup-container">
          <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 border-b border-gray-200 dark:border-gray-600 pb-1">
            {{ option.group }}
            <span v-if="option.min !== undefined || option.max !== undefined" class="text-xs text-gray-500 dark:text-gray-400 ml-2">
              ({{ option.min !== undefined ? `min: ${option.min}` : '' }}{{ option.min !== undefined && option.max !== undefined ? ', ' : '' }}{{ option.max !== undefined ? `max: ${option.max}` : '' }})
            </span>
          </div>
          <div class="ml-4 space-y-2">
            <label
              v-for="subOption in option.options"
              :key="subOption.value"
              :for="`${fieldId}_${subOption.value}`"
              class="flex items-center cursor-pointer"
              :class="{ 
                'opacity-50 cursor-not-allowed': subOption.disabled || !edit || (multiple && limit && !internalValue.includes(subOption.value) && internalValue.length >= limit)
              }"
            >
              <input
                :id="`${fieldId}_${subOption.value}`"
                :type="multiple ? 'checkbox' : 'radio'"
                :name="multiple ? `${name}_${subOption.value}` : name"
                :value="subOption.value"
                :checked="multiple ? internalValue.includes(subOption.value) : internalValue === subOption.value"
                :disabled="subOption.disabled || !edit || (multiple && limit && !internalValue.includes(subOption.value) && internalValue.length >= limit)"
                :required="required && !multiple"
                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                @change="handleChange"
                @blur="handleBlur"
                @focus="handleFocus"
              />
              <span class="ml-2 text-sm text-gray-900 dark:text-white">{{ subOption.label }}</span>
            </label>
          </div>
        </div>
        
        <!-- Options from optgroup with empty label - render directly -->
        <template v-else-if="option.group !== undefined && option.group.trim() === ''">
          <label
            v-for="subOption in option.options"
            :key="subOption.value"
            :for="`${fieldId}_${subOption.value}`"
            class="flex items-center cursor-pointer"
            :class="{ 
              'opacity-50 cursor-not-allowed': subOption.disabled || !edit || (multiple && limit && !internalValue.includes(subOption.value) && internalValue.length >= limit)
            }"
          >
            <input
              :id="`${fieldId}_${subOption.value}`"
              :type="multiple ? 'checkbox' : 'radio'"
              :name="multiple ? `${name}_${subOption.value}` : name"
              :value="subOption.value"
              :checked="multiple ? internalValue.includes(subOption.value) : internalValue === subOption.value"
              :disabled="subOption.disabled || !edit || (multiple && limit && !internalValue.includes(subOption.value) && internalValue.length >= limit)"
              :required="required && !multiple"
              class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
              @change="handleChange"
              @blur="handleBlur"
              @focus="handleFocus"
            />
            <span class="ml-2 text-sm text-gray-900 dark:text-white">{{ subOption.label }}</span>
          </label>
        </template>
        
        <!-- Regular Options -->
        <label
          v-else
          :for="`${fieldId}_${option.value}`"
          class="flex items-center cursor-pointer"
          :class="{ 
            'opacity-50 cursor-not-allowed': option.disabled || !edit || (multiple && limit && !internalValue.includes(option.value) && internalValue.length >= limit)
          }"
        >
          <input
            :id="`${fieldId}_${option.value}`"
            :type="multiple ? 'checkbox' : 'radio'"
            :name="multiple ? `${name}_${option.value}` : name"
            :value="option.value"
            :checked="multiple ? internalValue.includes(option.value) : internalValue === option.value"
            :disabled="option.disabled || !edit || (multiple && limit && !internalValue.includes(option.value) && internalValue.length >= limit)"
            :required="required && !multiple"
            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
            @change="handleChange"
            @blur="handleBlur"
            @focus="handleFocus"
          />
          <span class="ml-2 text-sm text-gray-900 dark:text-white">{{ option.label }}</span>
        </label>
      </template>
    </div>

         <!-- Help Text -->
     <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
     
     <!-- Selection Count (for multiple with limit) -->
     <div v-if="multiple && limit" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
       Selected: {{ internalValue.length }} / {{ limit }}
     </div>
     
     <!-- Error Message -->
     <div v-if="localError || (props.errors && props.errors.length > 0)" class="mt-2 text-xs text-red-500">{{ localError || props.errors[0] }}</div>
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
  required: { type: [Boolean,String,Array], default: true },
  disabled: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  edit:  { type: [Boolean,String,Array], default: true },
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
  autofocus: {
    type: Boolean,
    default: false
  },
  validate: {
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    default: () => []
  },
  multiple: {
    type: Boolean,
    default: false
  },
  limit: {
    type: Number,
    default: null
  },
  errors: {
    type: Array,
    default: () => []
  }
});

// Emits
const emit = defineEmits(['update:value', 'validation-error', 'validation-success', 'blur', 'focus']);

// Reactive state
const internalValue = ref(props.multiple ? (Array.isArray(props.value) ? props.value : []) : (props.value || ''));
const localError = ref('');
const showInfo = ref(false);

// Computed properties
const isDisabled = computed(() => {
  return props.disabled || !props.edit;
});

const processedOptions = computed(() => {
  return props.options.map(option => {
    // Handle optgroup format
    if (option.type === 'optgroup') {
      // If optgroup has min/max but no options, generate numeric options
      if ((option.min !== undefined || option.max !== undefined) && (!option.options || option.options.length === 0)) {
        const min = option.min || 0;
        const max = option.max || 10;
        const generatedOptions = [];
        
        for (let i = min; i <= max; i++) {
          generatedOptions.push({
            label: i.toString(),
            value: i.toString()
          });
        }
        
        return {
          group: option.label || option.group || '',
          options: generatedOptions,
          min: option.min,
          max: option.max
        };
      }
      
      // Regular optgroup with options
      return {
        group: option.label || option.group || '',
        options: option.options || [],
        min: option.min,
        max: option.max
      };
    }
    
    // Handle string options
    if (typeof option === 'string') {
      return { label: option, value: option };
    } 
    
    // Handle object options
    if (typeof option === 'object') {
      return option;
    }
    
    return { label: String(option), value: option };
  });
});

// Methods
const validate = () => {
  const errors = validateField(internalValue.value, {
    ...props,
    type: props.multiple ? 'checkbox' : 'radio'
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

const handleChange = (event) => {
  if (props.multiple) {
    const value = event.target.value;
    if (event.target.checked) {
      // Check if we're at the limit
      if (props.limit && internalValue.value.length >= props.limit) {
        // Don't add more items if we're at the limit
        return;
      }
      if (!internalValue.value.includes(value)) {
        internalValue.value = [...internalValue.value, value];
      }
    } else {
      internalValue.value = internalValue.value.filter(v => v !== value);
    }
  } else {
    internalValue.value = event.target.value;
  }
  emit('update:value', internalValue.value);
  validate();
};

const handleBlur = () => {
  validate();
  emit('blur', internalValue.value);
};

const handleFocus = () => {
  emit('focus', internalValue.value);
};

// Watchers
watch(() => props.value, (newValue) => {
  if (props.multiple) {
    internalValue.value = Array.isArray(newValue) ? newValue : [];
  } else {
    internalValue.value = newValue || '';
  }
}, { immediate: true });

watch(() => props.validate, () => {
  validate();
}, { deep: true });

// Lifecycle
onMounted(() => {
  if (props.multiple) {
    if (internalValue.value.length > 0) {
      validate();
    }
  } else {
    if (internalValue.value !== '' && internalValue.value !== null && internalValue.value !== undefined) {
      validate();
    }
  }
});
</script>

<style scoped>
/* Override any unwanted styles from @tailwindcss/forms */
.radio-field-container {
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  appearance: none !important;
  background-color: transparent !important;
  border: none !important;
  border-radius: 0 !important;
  padding: 0 !important;
  margin: 0 !important;
  box-shadow: none !important;
  outline: none !important;
}

/* Ensure radio inputs maintain their styling */
.radio-field-container input[type="radio"],
.radio-field-container input[type="checkbox"] {
  -webkit-appearance: auto !important;
  -moz-appearance: auto !important;
  appearance: auto !important;
}

/* Allow container to grow to maximum size */
.radio-field-container {
  width: 100% !important;
  max-width: none !important;
  min-width: 0 !important;
  height: auto !important;
  max-height: none !important;
  min-height: 0 !important;
}

/* Force dark mode styles with higher specificity */
.dark .text-gray-900 {
  color: rgb(255 255 255) !important; /* white */
}

.dark .text-gray-700 {
  color: rgb(209 213 219) !important; /* gray-300 */
}

.dark .text-gray-600 {
  color: rgb(209 213 219) !important; /* gray-300 */
}

.dark .text-gray-500 {
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark .text-gray-400 {
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark .border-gray-200 {
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

.dark .text-red-500 {
  color: rgb(248 113 113) !important; /* red-400 */
}

/* Radio button and checkbox specific styles */
.dark input[type="radio"],
.dark input[type="checkbox"] {
  background-color: rgb(55 65 81) !important; /* gray-700 */
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark input[type="radio"]:checked,
.dark input[type="checkbox"]:checked {
  background-color: rgb(59 130 246) !important; /* blue-500 */
  border-color: rgb(59 130 246) !important; /* blue-500 */
}

/* Focus styles */
.focus\:ring-blue-500:focus {
  --tw-ring-color: rgb(59 130 246) !important; /* blue-500 */
}

.dark .focus\:ring-blue-600:focus {
  --tw-ring-color: rgb(37 99 235) !important; /* blue-600 */
}

/* Optgroup styling */
.optgroup-container {
  border-left: 2px solid rgb(229 231 235); /* gray-200 */
  padding-left: 1rem;
  margin-left: 0.5rem;
}

.dark .optgroup-container {
  border-left-color: rgb(75 85 99) !important; /* gray-600 */
}
</style> 