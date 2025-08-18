<template>
  <div v-if="show" :class="{ 'select-field-container': !inFieldset }">
    <!-- Label -->
    <label v-if="label" :for="fieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2" :class="{ 'text-red-500': localError || (props.errors && props.errors.length > 0) }">
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
    <div v-if="!inFieldset" class="flex items-stretch w-full">
      <!-- Pre (icon) -->
      <span 
        class="inline-flex items-center justify-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-l-md min-w-[44px]"
      >
        <i class="fa-solid fa-chevron-down text-base"></i>
      </span>
      
      <!-- Main Select -->
      <select
        :id="fieldId"
        v-model="internalValue"
        :required="required"
        :disabled="!edit"
        :multiple="multiple"
        :size="multiple ? (size || 4) : undefined"
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
        @change="handleChange"
        @blur="handleBlur"
        @focus="handleFocus"
      >
        <!-- Placeholder option -->
        <option v-if="!multiple && placeholder" value="" disabled class="text-gray-500">
          {{ placeholder }}
        </option>
        
        <!-- Option groups -->
        <template v-for="option in processedOptions" :key="option.value || option.group">
          <optgroup v-if="option.group && option.group.trim() !== ''" :label="option.group">
            <option
              v-for="subOption in option.options"
              :key="subOption.value || subOption.label"
              :value="subOption.value || subOption.label"
              :disabled="subOption.disabled"
              :selected="isOptionSelected(subOption.value || subOption.label)"
            >
              {{ subOption.label }}
            </option>
          </optgroup>
          
          <!-- Options from optgroup with empty label - render directly -->
          <template v-else-if="option.group !== undefined && option.group.trim() === ''">
            <option
              v-for="subOption in option.options"
              :key="subOption.value || subOption.label"
              :value="subOption.value || subOption.label"
              :disabled="subOption.disabled"
              :selected="isOptionSelected(subOption.value || subOption.label)"
            >
              {{ subOption.label }}
            </option>
          </template>
          
          <!-- Regular options -->
          <option
            v-else
            :value="option.value"
            :disabled="option.disabled"
            :selected="isOptionSelected(option.value)"
          >
            {{ option.label }}
          </option>
        </template>
      </select>
    </div>

    <!-- Plain Select for Fieldset -->
    <select
      v-else
      :id="fieldId"
      v-model="internalValue"
      :required="required"
      :disabled="!edit"
      :multiple="multiple"
      :size="multiple ? (size || 4) : undefined"
      :autocomplete="autocomplete"
      :autofocus="autofocus"
      :name="name"
      class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white transition-colors duration-200 rounded-md"
      :class="[
        // Focus states
        'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
        // Disabled states
        !edit ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' : 'hover:border-gray-400 dark:hover:border-gray-500',
        // Error states
        localError ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : ''
      ]"
      @change="handleChange"
      @blur="handleBlur"
      @focus="handleFocus"
    >
      <!-- Placeholder option -->
      <option v-if="!multiple && placeholder" value="" disabled class="text-gray-500">
        {{ placeholder }}
      </option>
      
      <!-- Option groups -->
      <template v-for="option in processedOptions" :key="option.value || option.group">
        <optgroup v-if="option.group && option.group.trim() !== ''" :label="option.group">
          <option
            v-for="subOption in option.options"
            :key="subOption.value || subOption.label"
            :value="subOption.value || subOption.label"
            :disabled="subOption.disabled"
            :selected="isOptionSelected(subOption.value || subOption.label)"
          >
            {{ subOption.label }}
          </option>
        </optgroup>
        
        <!-- Options from optgroup with empty label - render directly -->
        <template v-else-if="option.group !== undefined && option.group.trim() === ''">
          <option
            v-for="subOption in option.options"
            :key="subOption.value || subOption.label"
            :value="subOption.value || subOption.label"
            :disabled="subOption.disabled"
            :selected="isOptionSelected(subOption.value || subOption.label)"
          >
            {{ subOption.label }}
          </option>
        </template>
        
        <!-- Regular options -->
        <option
          v-else
          :value="option.value"
          :disabled="option.disabled"
          :selected="isOptionSelected(option.value)"
        >
          {{ option.label }}
        </option>
      </template>
    </select>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (props.errors && props.errors.length > 0)" class="mt-2 text-xs text-red-500">
      {{ localError || props.errors[0] }}
    </div>
    
    <!-- Selected Values Display (for multiple selection) -->
    <div v-if="multiple && internalValue && internalValue.length > 0" class="mt-2">
      <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Selected:</div>
      <div v-if="showColumn" class="grid grid-cols-1 gap-1">
        <span
          v-for="value in internalValue"
          :key="value"
          class="inline-flex items-center justify-between px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-md"
        >
          <span>{{ getOptionLabel(value) }}</span>
          <button
            @click="removeValue(value)"
            class="ml-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
            type="button"
          >
            <i class="fa-solid fa-times text-xs"></i>
          </button>
        </span>
      </div>
      <div v-else class="flex flex-wrap gap-1">
        <span
          v-for="value in internalValue"
          :key="value"
          class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-md"
        >
          {{ getOptionLabel(value) }}
          <button
            @click="removeValue(value)"
            class="ml-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
            type="button"
          >
            <i class="fa-solid fa-times text-xs"></i>
          </button>
        </span>
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
    type: [String, Number, Array],
    default: () => []
  },
  placeholder: {
    type: String,
    default: 'Select an option...'
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
  options: {
    type: Array,
    default: () => []
  },
  multiple: {
    type: Boolean,
    default: false
  },
  size: {
    type: Number,
    default: 4
  },
  showColumn: {
    type: Boolean,
    default: false
  },
  inFieldset: {
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
const internalValue = ref(props.multiple ? (props.value || []) : (props.value || ''));
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
    type: 'select'
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

const validateOptgroupConstraints = () => {
  if (!props.multiple || !Array.isArray(internalValue.value)) {
    return null;
  }

  for (const option of processedOptions.value) {
    if (option.group && (option.min !== undefined || option.max !== undefined)) {
      // Get all values from this optgroup
      const optgroupValues = option.options.map(opt => 
        typeof opt === 'string' ? opt : opt.value
      );
      
      // Count how many selected values are from this optgroup
      const selectedFromGroup = internalValue.value.filter(value => 
        optgroupValues.includes(value)
      ).length;

      // Check min constraint
      if (option.min !== undefined && selectedFromGroup < option.min) {
        return `Please select at least ${option.min} option(s) from "${option.group}".`;
      }

      // Check max constraint
      if (option.max !== undefined && selectedFromGroup > option.max) {
        return `Please select no more than ${option.max} option(s) from "${option.group}".`;
      }
    }
  }

  return null;
};

const isOptionSelected = (value) => {
  if (props.multiple) {
    return Array.isArray(internalValue.value) && internalValue.value.includes(value);
  }
  return internalValue.value === value;
};

const getOptionLabel = (value) => {
  // Search through all options including optgroups
  for (const option of processedOptions.value) {
    if (option.group) {
      // Search in optgroup
      const found = option.options.find(opt => opt.value === value);
      if (found) return found.label;
    } else {
      // Search in regular options
      if (option.value === value) return option.label;
    }
  }
  return value;
};

const removeValue = (value) => {
  if (props.multiple && Array.isArray(internalValue.value)) {
    const newValue = internalValue.value.filter(v => v !== value);
    internalValue.value = newValue;
    emit('update:value', newValue);
    validate();
  }
};

const handleChange = (event) => {
  if (props.multiple) {
    // Handle multiple selection
    const selectedOptions = Array.from(event.target.selectedOptions).map(option => option.value);
    internalValue.value = selectedOptions;
  } else {
    // Handle single selection
    internalValue.value = event.target.value;
  }
  
  emit('update:value', internalValue.value);
  
  // Validate optgroup constraints
  const constraintError = validateOptgroupConstraints();
  if (constraintError) {
    localError.value = constraintError;
    emit('validation-error', {
      field: props.name,
      errors: [constraintError],
      value: internalValue.value
    });
  } else {
    validate();
  }
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
  internalValue.value = props.multiple ? (newValue || []) : (newValue || '');
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

/* Select option styles */
select option {
  background-color: rgb(255 255 255);
  color: rgb(17 24 39);
}

.dark select option {
  background-color: rgb(31 41 55);
  color: rgb(255 255 255);
}

/* Placeholder option styling */
select option[disabled] {
  color: rgb(107 114 128) !important; /* gray-500 */
}

.dark select option[disabled] {
  color: rgb(156 163 175) !important; /* gray-400 */
}

/* Selected values display */
.dark .bg-blue-100 {
  background-color: rgb(30 58 138) !important; /* blue-900 */
}

.dark .text-blue-800 {
  color: rgb(191 219 254) !important; /* blue-200 */
}

.dark .text-blue-600 {
  color: rgb(147 197 253) !important; /* blue-400 */
}
</style> 