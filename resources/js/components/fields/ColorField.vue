<template>
  <div v-if="show" class="color-field-container">
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
      <!-- Pre (color preview) -->
      <span 
        class="inline-flex items-center justify-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-l-md min-w-[44px]"
      >
        <div 
          class="w-4 h-4 rounded border border-gray-300 dark:border-gray-600"
          :style="{ backgroundColor: internalValue || '#ffffff' }"
        ></div>
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
        :maxlength="7"
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
      
      <!-- Color Picker Button -->
      <button
        v-if="edit"
        type="button"
        @click="openColorPicker"
        class="inline-flex items-center justify-center px-3 border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-r-md min-w-[44px] hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
        :title="'Pick color'"
      >
        <i class="fa-solid fa-palette text-base"></i>
      </button>
    </div>

    <!-- Color Picker Modal -->
    <div v-if="showColorPicker" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" @click="closeColorPicker">
      <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-sm w-full mx-4" @click.stop>
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white">Pick a Color</h3>
          <button @click="closeColorPicker" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <i class="fa-solid fa-times text-lg"></i>
          </button>
        </div>
        
        <!-- Color Input -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hex Color</label>
          <input
            type="text"
            v-model="colorPickerValue"
            placeholder="#000000"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
            @input="handleColorPickerInput"
          >
        </div>
        
        <!-- Color Preview -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preview</label>
          <div class="flex items-center space-x-3">
            <div 
              class="w-12 h-12 rounded border border-gray-300 dark:border-gray-600"
              :style="{ backgroundColor: colorPickerValue || '#ffffff' }"
            ></div>
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ colorPickerValue || 'No color selected' }}</span>
          </div>
        </div>
        
        <!-- Preset Colors -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Preset Colors</label>
          <div class="grid grid-cols-8 gap-2">
            <button
              v-for="color in presetColors"
              :key="color"
              @click="selectPresetColor(color)"
              class="w-8 h-8 rounded border border-gray-300 dark:border-gray-600 hover:scale-110 transition-transform"
              :style="{ backgroundColor: color }"
              :title="color"
            ></button>
          </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-end space-x-3">
          <button
            @click="closeColorPicker"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
          >
            Cancel
          </button>
          <button
            @click="applyColor"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors"
          >
            Apply
          </button>
        </div>
      </div>
    </div>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (props.errors && props.errors.length > 0)" class="mt-2 text-xs text-red-500">{{ localError || props.errors[0] }}</div>
    
    <!-- Color Preview -->
    <div v-if="showColorPreview && internalValue" class="mt-2 flex items-center space-x-2">
      <div 
        class="w-6 h-6 rounded border border-gray-300 dark:border-gray-600"
        :style="{ backgroundColor: internalValue }"
      ></div>
      <span class="text-xs text-gray-600 dark:text-gray-400">{{ internalValue }}</span>
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
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: '#000000'
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
  limit: {
    type: Number,
    default: 7
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
  showColorPreview: {
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
const showInfo = ref(false);
const showColorPicker = ref(false);
const colorPickerValue = ref('');

// Preset colors
const presetColors = [
  '#000000', '#ffffff', '#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff',
  '#ffa500', '#800080', '#008000', '#ffc0cb', '#a52a2a', '#808080', '#c0c0c0', '#000080'
];

// Computed properties
const isDisabled = computed(() => {
  return props.disabled || !props.edit;
});

// Methods
const validate = () => {
  const errors = validateField(internalValue.value, {
    ...props,
    type: 'color'
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

  // Validate hex color format
  if (inputValue && !/^#[0-9A-Fa-f]{6}$/.test(inputValue)) {
    // Allow partial input while typing
    internalValue.value = inputValue;
  } else {
    internalValue.value = inputValue;
  }

  emit('update:value', internalValue.value);
};

const handleChange = (event) => {
  // Additional validation on change
  validate();
};

const handleBlur = () => {
  // Format the value on blur
  if (internalValue.value && !/^#[0-9A-Fa-f]{6}$/.test(internalValue.value)) {
    // Try to fix common issues
    let fixedValue = internalValue.value;
    
    // Add # if missing
    if (!fixedValue.startsWith('#')) {
      fixedValue = '#' + fixedValue;
    }
    
    // Pad with zeros if needed
    if (fixedValue.length < 7) {
      fixedValue = fixedValue + '0'.repeat(7 - fixedValue.length);
    }
    
    // Truncate if too long
    if (fixedValue.length > 7) {
      fixedValue = fixedValue.substring(0, 7);
    }
    
    // Validate final format
    if (/^#[0-9A-Fa-f]{6}$/.test(fixedValue)) {
      internalValue.value = fixedValue;
      emit('update:value', fixedValue);
    }
  }
  
  validate();
  emit('blur', internalValue.value);
};

const handleFocus = () => {
  emit('focus', internalValue.value);
};

const handleKeydown = (event) => {
  // Allow: backspace, delete, tab, escape, enter, and navigation keys
  const allowedKeys = [8, 9, 27, 13, 46, 37, 38, 39, 40];
  
  // Allow: numbers, letters A-F, #, and allowed keys
  const isHexChar = /[0-9A-Fa-f#]/.test(event.key);
  const isAllowedKey = allowedKeys.includes(event.keyCode);
  
  // Allow # only at the beginning
  if (event.key === '#' && event.target.selectionStart !== 0) {
    event.preventDefault();
    return;
  }
  
  // Allow hex characters and allowed keys
  if (!isHexChar && !isAllowedKey) {
    event.preventDefault();
  }
};

const openColorPicker = () => {
  colorPickerValue.value = internalValue.value || '#000000';
  showColorPicker.value = true;
};

const closeColorPicker = () => {
  showColorPicker.value = false;
  colorPickerValue.value = '';
};

const handleColorPickerInput = (event) => {
  const value = event.target.value;
  if (/^#[0-9A-Fa-f]{0,6}$/.test(value)) {
    colorPickerValue.value = value;
  }
};

const selectPresetColor = (color) => {
  colorPickerValue.value = color;
};

const applyColor = () => {
  if (colorPickerValue.value && /^#[0-9A-Fa-f]{6}$/.test(colorPickerValue.value)) {
    internalValue.value = colorPickerValue.value;
    emit('update:value', colorPickerValue.value);
    validate();
  }
  closeColorPicker();
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

.dark .text-gray-700 {
  color: rgb(229 231 235) !important; /* gray-200 */
}

.dark .text-gray-300 {
  color: rgb(209 213 219) !important; /* gray-300 */
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