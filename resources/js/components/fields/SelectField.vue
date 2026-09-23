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

    <!-- Multiple: custom dropdown (Combobox-like) -->
    <div v-if="isMultiple" ref="containerRef" class="relative w-full">
      <button
        :id="fieldId"
        type="button"
        class="w-full py-2 px-3 pr-10 text-sm text-left border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white transition-colors duration-200 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
        :class="[
          !edit ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 opacity-75' : 'hover:border-gray-400 dark:hover:border-gray-500',
          localError || (props.errors && props.errors.length > 0) ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : ''
        ]"
        :disabled="!edit"
        :autofocus="autofocus"
        @mousedown.prevent="toggleDropdown"
        @keydown="handleMultiKeydown"
        @blur="handleMultiBlur"
        @focus="handleFocus"
      >
        <span :class="multiTriggerLabelClass">{{ multiTriggerLabel }}</span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
          <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </span>
      </button>

      <div
        v-if="isOpen"
        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      >
        <template v-for="(option, idx) in processedOptions" :key="option.value || option.group || idx">
          <div
            v-if="option.group && option.group.trim() !== ''"
            class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600"
          >
            {{ option.group }}
          </div>

          <template v-if="option.group !== undefined">
            <div
              v-for="(subOption, subIdx) in option.options"
              :key="subOption.value || subOption.label || subIdx"
              class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex items-center justify-between gap-2"
              :class="{
                'bg-blue-50 dark:bg-blue-900/20': isOptionSelected(subOption.value || subOption.label),
                'opacity-50 cursor-not-allowed': subOption.disabled || !edit
              }"
              @mousedown.prevent="!subOption.disabled && edit && toggleOption(subOption.value || subOption.label)"
            >
              <span>{{ subOption.label }}</span>
              <span v-if="isOptionSelected(subOption.value || subOption.label)" class="text-blue-600 dark:text-blue-400 text-xs">✓</span>
            </div>
          </template>

          <div
            v-else
            class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex items-center justify-between gap-2"
            :class="{
              'bg-blue-50 dark:bg-blue-900/20': isOptionSelected(option.value),
              'opacity-50 cursor-not-allowed': option.disabled || !edit
            }"
            @mousedown.prevent="!option.disabled && edit && toggleOption(option.value)"
          >
            <span>{{ option.label }}</span>
            <span v-if="isOptionSelected(option.value)" class="text-blue-600 dark:text-blue-400 text-xs">✓</span>
          </div>
        </template>

        <div v-if="processedOptions.length === 0" class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
          No options available.
        </div>
      </div>
    </div>

    <!-- Single: native select (non-fieldset) -->
    <div v-else-if="!inFieldset" class="flex items-stretch w-full">
      <select
        :id="fieldId"
        v-model="internalValue"
        :required="required"
        :disabled="!edit"
        :autocomplete="autocomplete"
        :autofocus="autofocus"
        :name="name"
        class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white transition-colors duration-200 rounded-md"
        :class="[
          'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
          !edit ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 opacity-75' : 'hover:border-gray-400 dark:hover:border-gray-500',
          localError ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : ''
        ]"
        @change="handleChange"
        @blur="handleBlur"
        @focus="handleFocus"
      >
        <option v-if="shouldShowPlaceholder" value="" disabled class="text-gray-500">
          {{ placeholder }}
        </option>

        <template v-for="option in processedOptions" :key="option.value || option.group">
          <optgroup v-if="option.group && option.group.trim() !== ''" :label="option.group">
            <option
              v-for="subOption in option.options"
              :key="subOption.value || subOption.label"
              :value="subOption.value || subOption.label"
              :disabled="subOption.disabled"
            >
              {{ subOption.label }}
            </option>
          </optgroup>

          <template v-else-if="option.group !== undefined && option.group.trim() === ''">
            <option
              v-for="subOption in option.options"
              :key="subOption.value || subOption.label"
              :value="subOption.value || subOption.label"
              :disabled="subOption.disabled"
            >
              {{ subOption.label }}
            </option>
          </template>

          <option
            v-else
            :value="option.value"
            :disabled="option.disabled"
          >
            {{ option.label }}
          </option>
        </template>
      </select>
    </div>

    <!-- Single: native select (fieldset) -->
    <select
      v-else
      :id="fieldId"
      v-model="internalValue"
      :required="required"
      :disabled="!edit"
      :autocomplete="autocomplete"
      :autofocus="autofocus"
      :name="name"
      class="w-full py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white transition-colors duration-200 rounded-md"
      :class="[
        'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
        !edit ? 'cursor-not-allowed bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-600 opacity-75' : 'hover:border-gray-400 dark:hover:border-gray-500',
        localError ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : ''
      ]"
      @change="handleChange"
      @blur="handleBlur"
      @focus="handleFocus"
    >
      <option v-if="shouldShowPlaceholder" value="" disabled class="text-gray-500">
        {{ placeholder }}
      </option>

      <template v-for="option in processedOptions" :key="option.value || option.group">
        <optgroup v-if="option.group && option.group.trim() !== ''" :label="option.group">
          <option
            v-for="subOption in option.options"
            :key="subOption.value || subOption.label"
            :value="subOption.value || subOption.label"
            :disabled="subOption.disabled"
          >
            {{ subOption.label }}
          </option>
        </optgroup>

        <template v-else-if="option.group !== undefined && option.group.trim() === ''">
          <option
            v-for="subOption in option.options"
            :key="subOption.value || subOption.label"
            :value="subOption.value || subOption.label"
            :disabled="subOption.disabled"
          >
            {{ subOption.label }}
          </option>
        </template>

        <option
          v-else
          :key="option.value"
          :value="option.value"
          :disabled="option.disabled"
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
    <div v-if="isMultiple && Array.isArray(internalValue) && internalValue.length > 0" class="mt-2">
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
            :disabled="!edit"
          >
            <FontAwesomeIcon :icon="faTimes" class="text-xs" />
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
            :disabled="!edit"
          >
            <FontAwesomeIcon :icon="faTimes" class="text-xs" />
          </button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { validateField } from './validation.js';
import { isMultipleFlag } from './functions.js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faTimes } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
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
    type: [String, Number, Array, Boolean],
    default: false
  },
  placeholder: {
    type: String,
    default: 'Select an option...'
  },
  required:  { type: [Boolean,String,Array], default: false },
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
    type: [Boolean, String],
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

const emit = defineEmits(['update:value', 'validation-error', 'validation-success', 'blur', 'focus']);

const isMultiple = computed(() => isMultipleFlag(props.multiple));

const internalValue = ref(
  isMultiple.value
    ? (Array.isArray(props.value) ? [...props.value] : [])
    : (props.value || '')
);
const localError = ref('');
const showInfo = ref(false);
const isOpen = ref(false);
const containerRef = ref(null);
const blurTimeout = ref(null);

const shouldShowPlaceholder = computed(() => {
  return !isMultiple.value && 
         props.placeholder && 
         (internalValue.value === undefined || internalValue.value === null || internalValue.value === '');
});

const multiTriggerLabel = computed(() => {
  if (!Array.isArray(internalValue.value) || internalValue.value.length === 0) {
    return props.placeholder || 'Select options...';
  }
  if (internalValue.value.length === 1) {
    return getOptionLabel(internalValue.value[0]);
  }
  return `${internalValue.value.length} selected`;
});

const multiTriggerLabelClass = computed(() => {
  const empty = !Array.isArray(internalValue.value) || internalValue.value.length === 0;
  return empty ? 'text-gray-500 dark:text-gray-400' : '';
});

const valuesEqual = (a, b) => String(a) === String(b);

const processedOptions = computed(() => {
  return props.options.map(option => {
    if (option.type === 'optgroup') {
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
      
      return {
        group: option.label || option.group || '',
        options: option.options || [],
        min: option.min,
        max: option.max
      };
    }

    if (typeof option === 'string') {
      return { label: option, value: option };
    } 

    if (typeof option === 'boolean') {
      return { label: option ? 'true' : 'false', value: option ? true : false };
    }
    if (typeof option === 'number') {
      return { label: option.toString(), value: option.toString() };
    }
    if (typeof option === 'object') {
      return option;
    }
    
    return { label: String(option), value: option };
  });
});

const clearBlurTimeout = () => {
  if (blurTimeout.value) {
    clearTimeout(blurTimeout.value);
    blurTimeout.value = null;
  }
};

const validate = () => {
  const errors = validateField(internalValue.value, {
    ...props,
    type: 'select',
    multiple: isMultiple.value
  });

  localError.value = errors[0] || '';

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
  if (!isMultiple.value || !Array.isArray(internalValue.value)) {
    return null;
  }

  for (const option of processedOptions.value) {
    if (option.group && (option.min !== undefined || option.max !== undefined)) {
      const optgroupValues = option.options.map(opt => 
        typeof opt === 'string' ? opt : opt.value
      );
      
      const selectedFromGroup = internalValue.value.filter(value => 
        optgroupValues.some(v => valuesEqual(v, value))
      ).length;

      if (option.min !== undefined && selectedFromGroup < option.min) {
        return `Please select at least ${option.min} option(s) from "${option.group}".`;
      }

      if (option.max !== undefined && selectedFromGroup > option.max) {
        return `Please select no more than ${option.max} option(s) from "${option.group}".`;
      }
    }
  }

  return null;
};

const emitValueWithValidation = () => {
  emit('update:value', internalValue.value);

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

const isOptionSelected = (value) => {
  if (isMultiple.value) {
    return Array.isArray(internalValue.value) && internalValue.value.some(v => valuesEqual(v, value));
  }
  return valuesEqual(internalValue.value, value);
};

const getOptionLabel = (value) => {
  for (const option of processedOptions.value) {
    if (option.group !== undefined) {
      const found = option.options.find(opt => valuesEqual(opt.value, value) || valuesEqual(opt.label, value));
      if (found) return found.label;
    } else if (valuesEqual(option.value, value)) {
      return option.label;
    }
  }
  return value;
};

const removeValue = (value) => {
  if (!isMultiple.value || !props.edit || !Array.isArray(internalValue.value)) return;
  internalValue.value = internalValue.value.filter(v => !valuesEqual(v, value));
  emitValueWithValidation();
};

const toggleOption = (value) => {
  if (!isMultiple.value || !props.edit) return;
  clearBlurTimeout();

  const current = Array.isArray(internalValue.value) ? [...internalValue.value] : [];
  const existingIndex = current.findIndex(v => valuesEqual(v, value));
  if (existingIndex >= 0) {
    current.splice(existingIndex, 1);
  } else {
    current.push(value);
  }
  internalValue.value = current;
  // Keep dropdown open for multi-select
  emitValueWithValidation();
};

const toggleDropdown = () => {
  if (!props.edit) return;
  clearBlurTimeout();
  isOpen.value = !isOpen.value;
};

const handleMultiKeydown = (event) => {
  if (event.key === 'Escape') {
    event.preventDefault();
    isOpen.value = false;
  } else if (event.key === 'Enter' || event.key === 'ArrowDown' || event.key === ' ') {
    event.preventDefault();
    isOpen.value = true;
  }
};

const handleMultiBlur = () => {
  clearBlurTimeout();
  blurTimeout.value = setTimeout(() => {
    if (containerRef.value?.contains(document.activeElement)) {
      return;
    }
    isOpen.value = false;
    validate();
    emit('blur', internalValue.value);
  }, 150);
};

const handleChange = (event) => {
  if (isMultiple.value) {
    const selectedOptions = Array.from(event.target.selectedOptions).map(option => option.value);
    internalValue.value = selectedOptions;
  } else {
    internalValue.value = event.target.value;
  }
  
  emitValueWithValidation();
};

const handleBlur = () => {
  validate();
  emit('blur', internalValue.value);
};

const handleFocus = () => {
  clearBlurTimeout();
  emit('focus', internalValue.value);
};

watch(() => props.value, (newValue) => {
  if (isMultiple.value) {
    internalValue.value = Array.isArray(newValue) ? [...newValue] : [];
  } else {
    if (newValue !== undefined && newValue !== null && newValue !== '') {
      internalValue.value = newValue;
    } else {
      internalValue.value = '';
    }
  }
}, { immediate: true });

watch(() => props.validate, () => {
  validate();
}, { deep: true });

onMounted(() => {
  if (isMultiple.value) {
    if (Array.isArray(props.value) && props.value.length > 0) {
      internalValue.value = [...props.value];
      validate();
    } else {
      internalValue.value = [];
    }
    return;
  }

  if (props.value !== undefined && props.value !== null && props.value !== '') {
    internalValue.value = props.value;
  } else {
    internalValue.value = '';
  }

  if (internalValue.value !== '' && internalValue.value !== null && internalValue.value !== undefined) {
    validate();
  }
});
</script>

<script>
export default {
  components: {
    FontAwesomeIcon
  }
}
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
