<template>
  <div v-if="show" class="combobox-field-container">
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

    <!-- Combobox Container -->
    <div ref="containerRef" class="relative">
      <!-- Input Field -->
      <div class="relative">
        <input
          :id="fieldId"
          ref="inputRef"
          type="text"
          :value="displayValue"
          :placeholder="placeholder"
          :disabled="disabled || !edit"
          :required="required"
          :autocomplete="autocomplete"
          class="w-full py-2 px-3 pr-10 text-sm border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-white rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
          :class="[
            (localError || (props.errors && props.errors.length > 0)) ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : 'border-gray-300 dark:border-gray-600',
            disabled || !edit ? 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 cursor-not-allowed' : ''
          ]"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          @keydown="handleKeydown"
        />
        
        <!-- Dropdown Toggle Button -->
        <button
          type="button"
          class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          :class="{ 'cursor-not-allowed': disabled || !edit }"
          @mousedown.prevent="toggleDropdown"
          :disabled="disabled || !edit"
        >
          <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
          </svg>
        </button>
      </div>

      <!-- Dropdown Options -->
      <div
        v-if="isOpen && filteredOptions.length > 0"
        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      >
        <template v-for="(option, idx) in filteredOptions" :key="option.type === 'optgroup' ? 'optgroup-' + option.label : option.value">
          <div v-if="option.type === 'optgroup' && option.label && option.label.trim() !== ''" class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
            {{ option.label }}
          </div>
          <div
            v-for="(subOption, subIdx) in option.type === 'optgroup' ? option.options : [option]"
            :key="subOption.value"
            class="px-3 py-2 text-sm cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white flex items-center justify-between gap-2"
            :class="{
              'bg-blue-50 dark:bg-blue-900/20': highlightedIndex === (option.type === 'optgroup' ? (idx + '-' + subIdx) : idx) || isSelected(subOption.value)
            }"
            @mousedown.prevent="selectOption(subOption)"
            @mouseenter="highlightedIndex = option.type === 'optgroup' ? (idx + '-' + subIdx) : idx"
          >
            <span>{{ subOption.label }}</span>
            <span v-if="isMultiple && isSelected(subOption.value)" class="text-blue-600 dark:text-blue-400 text-xs">✓</span>
          </div>
        </template>
      </div>

      <!-- No Results Message -->
      <div
        v-if="isOpen && filteredOptions.length === 0 && searchValue"
        class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg"
      >
        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
          No options found. Type to add custom value.
        </div>
      </div>
    </div>

    <!-- Selected Values Display (for multiple selection) -->
    <div v-if="isMultiple && Array.isArray(internalValue) && internalValue.length > 0" class="mt-2">
      <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Selected:</div>
      <div class="flex flex-wrap gap-1">
        <span
          v-for="selectedValue in internalValue"
          :key="selectedValue"
          class="inline-flex items-center px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-md"
        >
          {{ getOptionLabel(selectedValue) }}
          <button
            type="button"
            class="ml-1 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200"
            :disabled="disabled || !edit"
            @click="removeValue(selectedValue)"
          >
            ×
          </button>
        </span>
      </div>
    </div>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (props.errors && props.errors.length > 0)" class="mt-2 text-xs text-red-500">{{ localError || props.errors[0] }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { validateField } from './validation.js';
import { isMultipleFlag } from './functions.js';

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
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Select or type...'
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
  autofocus: {
    type: Boolean,
    default: false
  },
  autocomplete: {
    type: String,
    default: 'off'
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
  errors: {
    type: Array,
    default: () => []
  }
});

// Emits
const emit = defineEmits(['update:value', 'validation-error', 'validation-success', 'blur', 'focus']);

const isMultiple = computed(() => isMultipleFlag(props.multiple));

// Reactive state
const internalValue = ref(isMultiple.value ? (Array.isArray(props.value) ? [...props.value] : []) : (props.value || ''));
const searchValue = ref('');
const isOpen = ref(false);
const highlightedIndex = ref(-1);
const localError = ref('');
const showInfo = ref(false);
const inputRef = ref(null);
const containerRef = ref(null);
const blurTimeout = ref(null);

// Computed properties
const processedOptions = computed(() => {
  // Flatten options for filtering/search, but keep optgroup info for rendering
  const result = [];
  props.options.forEach(option => {
    if (option.type === 'optgroup') {
      // Numeric generation for optgroups with min/max
      let groupOptions = [];
      if ((option.min !== undefined || option.max !== undefined) && (!option.options || option.options.length === 0)) {
        const min = option.min || 0;
        const max = option.max || 10;
        for (let i = min; i <= max; i++) {
          groupOptions.push({ label: i.toString(), value: i.toString() });
        }
      } else {
        groupOptions = (option.options || []).map(subOption => {
          if (typeof subOption === 'string') {
            return { label: subOption, value: subOption };
          } else if (typeof subOption === 'object') {
            return subOption;
          }
          return { label: String(subOption), value: subOption };
        });
      }
      result.push({
        type: 'optgroup',
        label: option.label || option.group || '',
        options: groupOptions
      });
    } else if (typeof option === 'string') {
      result.push({ label: option, value: option });
    } else if (typeof option === 'object') {
      result.push(option);
    } else {
      result.push({ label: String(option), value: option });
    }
  });
  return result;
});

// Flattened list for filtering/search
const flattenedOptions = computed(() => {
  const flat = [];
  processedOptions.value.forEach(option => {
    if (option.type === 'optgroup') {
      option.options.forEach(subOption => {
        flat.push({ ...subOption, _optgroup: option.label });
      });
    } else {
      flat.push(option);
    }
  });
  return flat;
});

const EMPTY_OPTIONS_LIMIT = 10;

const limitOptions = (options, limit) => {
  const result = [];
  let remaining = limit;

  for (const option of options) {
    if (remaining <= 0) break;

    if (option.type === 'optgroup') {
      const limitedGroupOptions = (option.options || []).slice(0, remaining);
      if (limitedGroupOptions.length === 0) continue;
      result.push({
        ...option,
        options: limitedGroupOptions
      });
      remaining -= limitedGroupOptions.length;
    } else {
      result.push(option);
      remaining -= 1;
    }
  }

  return result;
};

const filteredOptions = computed(() => {
  if (!searchValue.value) {
    return limitOptions(processedOptions.value, EMPTY_OPTIONS_LIMIT);
  }
  const search = searchValue.value.toLowerCase();
  // Filter flattened options
  const filteredFlat = flattenedOptions.value.filter(option => {
    const label = option.label.toLowerCase();
    return label.includes(search);
  });
  // Re-group filtered options by optgroup
  const grouped = {};
  filteredFlat.forEach(option => {
    if (option._optgroup) {
      if (!grouped[option._optgroup]) grouped[option._optgroup] = [];
      grouped[option._optgroup].push(option);
    }
  });
  // Build result: optgroups first, then flat options
  const result = [];
  Object.keys(grouped).forEach(label => {
    result.push({ type: 'optgroup', label, options: grouped[label] });
  });
  // Add flat options (not in optgroups)
  filteredFlat.forEach(option => {
    if (!option._optgroup) result.push(option);
  });
  return result;
});

const valuesEqual = (a, b) => String(a) === String(b);

const displayValue = computed(() => {
  // While open, always show the editable search text (including empty)
  if (isOpen.value) {
    return searchValue.value;
  }
  // If we have a search value, show it
  if (searchValue.value !== '') {
    return searchValue.value;
  }
  if (isMultiple.value) {
    return '';
  }
  const flat = flattenedOptions.value;
  const current = flat.find(opt => valuesEqual(opt.value, internalValue.value));
  return current ? current.label : (internalValue.value || '');
});

const syncSearchFromValue = () => {
  if (isMultiple.value) {
    searchValue.value = '';
    return;
  }
  const flat = flattenedOptions.value;
  const current = flat.find(opt => valuesEqual(opt.value, internalValue.value));
  searchValue.value = current ? current.label : (internalValue.value || '');
};

const isSelected = (value) => {
  if (isMultiple.value) {
    return Array.isArray(internalValue.value) && internalValue.value.some(v => valuesEqual(v, value));
  }
  return valuesEqual(internalValue.value, value);
};

const getOptionLabel = (value) => {
  const flat = flattenedOptions.value;
  const match = flat.find(opt => valuesEqual(opt.value, value));
  return match ? match.label : value;
};

// Methods
const validate = () => {
  const errors = validateField(internalValue.value, {
    ...props,
    type: 'combobox',
    multiple: isMultiple.value
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

const clearBlurTimeout = () => {
  if (blurTimeout.value) {
    clearTimeout(blurTimeout.value);
    blurTimeout.value = null;
  }
};

const handleInput = (event) => {
  const value = event.target.value;
  searchValue.value = value;
  isOpen.value = true;
  highlightedIndex.value = -1;

  // Full clear deselects for single-select
  if (!isMultiple.value && value === '') {
    internalValue.value = '';
    emit('update:value', '');
  }
};

const handleFocus = () => {
  clearBlurTimeout();
  syncSearchFromValue();
  isOpen.value = true;
  emit('focus', internalValue.value);
};

const handleBlur = () => {
  clearBlurTimeout();
  blurTimeout.value = setTimeout(() => {
    // Keep open if focus moved to another element inside the combobox
    if (containerRef.value?.contains(document.activeElement)) {
      return;
    }
    isOpen.value = false;
    highlightedIndex.value = -1;
    if (searchValue.value !== '') {
      // Try to match an option
      const flat = flattenedOptions.value;
      const match = flat.find(opt => opt.label === searchValue.value || opt.value === searchValue.value);
      if (match) {
        if (isMultiple.value) {
          selectOption(match);
        } else {
          internalValue.value = match.value;
          emit('update:value', internalValue.value);
        }
      } else if (!isMultiple.value) {
        internalValue.value = searchValue.value;
        emit('update:value', internalValue.value);
      }
      searchValue.value = '';
    } else if (!isMultiple.value) {
      // Empty search clears selection
      internalValue.value = '';
      emit('update:value', '');
    }
    validate();
    emit('blur', internalValue.value);
  }, 150);
};

const handleKeydown = (event) => {
  if (!isOpen.value) {
    if (event.key === 'ArrowDown' || event.key === 'Enter') {
      event.preventDefault();
      syncSearchFromValue();
      isOpen.value = true;
      highlightedIndex.value = 0;
    }
    return;
  }
  // Flatten filtered options for keyboard navigation
  const flatFiltered = [];
  filteredOptions.value.forEach(option => {
    if (option.type === 'optgroup') {
      option.options.forEach(sub => flatFiltered.push(sub));
    } else {
      flatFiltered.push(option);
    }
  });
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      highlightedIndex.value = Math.min(
        highlightedIndex.value + 1,
        flatFiltered.length - 1
      );
      break;
    case 'ArrowUp':
      event.preventDefault();
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
      break;
    case 'Enter':
      event.preventDefault();
      if (highlightedIndex.value >= 0 && flatFiltered[highlightedIndex.value]) {
        selectOption(flatFiltered[highlightedIndex.value]);
      } else if (searchValue.value) {
        // Try to match an option
        const match = flatFiltered.find(opt => opt.label === searchValue.value || opt.value === searchValue.value);
        if (match) {
          selectOption(match);
        } else if (!isMultiple.value) {
          internalValue.value = searchValue.value;
          emit('update:value', internalValue.value);
          isOpen.value = false;
        }
      }
      break;
    case 'Escape':
      event.preventDefault();
      isOpen.value = false;
      highlightedIndex.value = -1;
      searchValue.value = '';
      break;
  }
};

const selectOption = (option) => {
  clearBlurTimeout();

  if (isMultiple.value) {
    const current = Array.isArray(internalValue.value) ? [...internalValue.value] : [];
    const existingIndex = current.findIndex(v => valuesEqual(v, option.value));
    if (existingIndex >= 0) {
      current.splice(existingIndex, 1);
    } else {
      current.push(option.value);
    }
    internalValue.value = current;
    searchValue.value = '';
    highlightedIndex.value = -1;
    // Keep dropdown open for multi-select without stealing focus back
    emit('update:value', internalValue.value);
    validate();
    return;
  }

  internalValue.value = option.value;
  searchValue.value = option.label;
  isOpen.value = false;
  highlightedIndex.value = -1;
  emit('update:value', internalValue.value);
  validate();
};

const removeValue = (value) => {
  if (!isMultiple.value || props.disabled || !props.edit) return;
  const current = Array.isArray(internalValue.value) ? [...internalValue.value] : [];
  internalValue.value = current.filter(v => !valuesEqual(v, value));
  emit('update:value', internalValue.value);
  validate();
};

const toggleDropdown = () => {
  if (props.disabled || !props.edit) return;

  clearBlurTimeout();
  isOpen.value = !isOpen.value;
  highlightedIndex.value = -1;

  // Only focus when opening so the user can type to filter
  if (isOpen.value) {
    syncSearchFromValue();
    nextTick(() => {
      inputRef.value?.focus();
    });
  }
};

// Watchers
watch(() => props.value, (newValue) => {
  if (isMultiple.value) {
    internalValue.value = Array.isArray(newValue) ? [...newValue] : [];
    return;
  }
  internalValue.value = newValue || '';
  // Set searchValue to label of current value
  const flat = flattenedOptions.value;
  const current = flat.find(opt => opt.value === internalValue.value);
  searchValue.value = current ? current.label : '';
}, { immediate: true });

watch(() => props.validate, () => {
  validate();
}, { deep: true });

// Lifecycle
onMounted(() => {
  if (props.autofocus) {
    nextTick(() => {
      inputRef.value?.focus();
    });
  }
  
  const hasValue = isMultiple.value
    ? Array.isArray(internalValue.value) && internalValue.value.length > 0
    : (internalValue.value !== '' && internalValue.value !== null && internalValue.value !== undefined);

  if (hasValue) {
    validate();
  }
});
</script>

<style scoped>
/* Override any unwanted styles from @tailwindcss/forms */
.combobox-field-container {
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

/* Allow container to grow to maximum size */
.combobox-field-container {
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

.dark .text-gray-600 {
  color: rgb(209 213 219) !important; /* gray-300 */
}

.dark .text-gray-500 {
  color: rgb(156 163 175) !important; /* gray-400 */
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

.dark .bg-gray-800 {
  background-color: rgb(31 41 55) !important; /* gray-800 */
}

.dark .text-red-500 {
  color: rgb(248 113 113) !important; /* red-400 */
}

/* Dropdown scrollbar styling */
.combobox-field-container .overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.combobox-field-container .overflow-y-auto::-webkit-scrollbar-track {
  background: rgb(229 231 235); /* gray-200 */
}

.dark .combobox-field-container .overflow-y-auto::-webkit-scrollbar-track {
  background: rgb(75 85 99) !important; /* gray-600 */
}

.combobox-field-container .overflow-y-auto::-webkit-scrollbar-thumb {
  background: rgb(156 163 175); /* gray-400 */
  border-radius: 3px;
}

.dark .combobox-field-container .overflow-y-auto::-webkit-scrollbar-thumb {
  background: rgb(107 114 128) !important; /* gray-500 */
}

.combobox-field-container .overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: rgb(107 114 128); /* gray-500 */
}

.dark .combobox-field-container .overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: rgb(156 163 175) !important; /* gray-400 */
}
</style> 