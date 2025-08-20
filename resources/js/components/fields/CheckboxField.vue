<template>
  <div v-if="show" :class="{ 'checkbox-field-container': !inFieldset }">
    <!-- Main Label (only show if there are no options) -->
    <label v-if="label"  :for="fieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-3" :class="{ 'text-red-500': localError || (props.errors && props.errors.length > 0) }">
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

    <!-- Checkbox Input -->
    <div class="flex items-start space-x-3" :class="{ 'flex-col space-y-2': showColumn }">
      <div class="flex items-center">
        <input
          :id="fieldId"
          type="checkbox"
          :checked="internalValue"
          :disabled="disabled || !edit"
          :required="required"
          class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
          @change="handleChange"
          @blur="handleBlur"
          @focus="handleFocus"
        />
        <label 
          :for="fieldId" 
          class="ml-3 text-sm text-gray-900 dark:text-white cursor-pointer leading-tight"
          :class="{ 'cursor-not-allowed opacity-50': disabled || !edit }"
        >
          {{ getCheckboxLabel() }}
        </label>
      </div>
    </div>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (props.errors && props.errors.length > 0)" class="mt-2 text-xs text-red-500">
      {{ localError || props.errors[0] }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { validateField } from './validation.js';

const props = defineProps({
  name: { type: String, required: true },
  fieldId: { type: String, default: () => `field_${Math.random().toString(36).substr(2, 9)}` },
  label: { type: String, default: '' },
  value: { type: [Boolean, String], default: "false" },
  required: { type: [Boolean,String,Array], default: true },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  edit: { type: [Boolean,String,Array], default: true },
  show: { type: [Boolean,String,Array], default: true },
  parse: { type: [Boolean,String,Array], default: true },
  help: { type: String, default: '' },
  info: { type: String, default: '' },
  autofocus: { type: Boolean, default: false },
  validate: { type: Array, default: () => [] },
  showColumn: { type: Boolean, default: false },
  options: { type: Array, default: () => [
    { label: 'false', value: 'false' },
    { label: 'true', value: 'true' }
  ] },
  inFieldset: { type: Boolean, default: false },
  errors: { type: Array, default: () => [] }
});

const emit = defineEmits(['update:value', 'validation-error', 'validation-success', 'blur', 'focus']);

const internalValue = ref(props.value == props.options[1].value ? true : false);
const localError = ref('');
const showInfo = ref(false);

const validate = () => {
  const errors = validateField(internalValue.value, { ...props, type: 'checkbox' });
  localError.value = errors[0] || '';
  if (errors.length > 0) {
    emit('validation-error', { field: props.name, errors, value: internalValue.value });
  } else {
    emit('validation-success', { field: props.name, value: internalValue.value });
  }
  return errors.length === 0;
};

const handleChange = (event) => {
  internalValue.value = event.target.checked ? true : false;
  var updatedValue = internalValue.value ? props.options[1].value : props.options[0].value;
  emit('update:value', updatedValue);
  validate();
};

const handleBlur = () => {
  validate();
  emit('blur', internalValue.value);
};

const handleFocus = () => {
  emit('focus', internalValue.value);
};

const getCheckboxLabel = () => {
  if (props.options && props.options.length === 2) {
    // Use custom labels from options
    return internalValue.value ? props.options[1].label : props.options[0].label;
  }
  // Default labels
  return internalValue.value ? 'true' : 'false';
};

watch(() => props.value, (newValue) => {
  internalValue.value = Boolean(newValue)
}, { immediate: true });

watch(() => props.validate, () => {
  validate();
}, { deep: true });

onMounted(() => {
  if (props.autofocus) {
    setTimeout(() => {
      const el = document.getElementById(props.fieldId);
      if (el) el.focus();
    }, 100);
  }
  validate();
});
</script>

<style scoped>
.checkbox-field-container {
  width: 100% !important;
  min-width: 0 !important;
  max-width: none !important;
  min-height: 0 !important;
  height: auto !important;
  max-height: none !important;
  margin-bottom: 1rem !important;
}

/* Override @tailwindcss/forms styles for checkbox */
.checkbox-field-container {
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

/* Prevent Tailwind Forms from styling div elements as checkboxes */
div[type="checkbox"],
div[type="checkbox"]::before,
div[type="checkbox"]::after {
  appearance: none !important;
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  background: none !important;
  border: none !important;
  box-shadow: none !important;
  outline: none !important;
  content: none !important;
  display: block !important;
  width: auto !important;
  height: auto !important;
  min-width: auto !important;
  min-height: auto !important;
  max-width: none !important;
  max-height: none !important;
  margin: 0 !important;
  padding: 0 !important;
  position: static !important;
  transform: none !important;
  opacity: 1 !important;
  pointer-events: auto !important;
}

/* Ensure proper spacing between checkbox elements */
.checkbox-field-container > div {
  margin-top: 0.5rem !important;
}

.checkbox-field-container label {
  line-height: 1.4 !important;
  margin-bottom: 0 !important;
}

input[type="checkbox"] {
  appearance: none !important;
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  width: 1rem !important;
  height: 1rem !important;
  min-width: 1rem !important;
  min-height: 1rem !important;
  max-width: 1rem !important;
  max-height: 1rem !important;
  border: 1px solid rgb(209 213 219) !important;
  border-radius: 0.25rem !important;
  background-color: rgb(255 255 255) !important;
  color: rgb(59 130 246) !important;
  cursor: pointer !important;
  transition: all 0.2s !important;
  margin: 0 !important;
  padding: 0 !important;
  position: relative !important;
  display: inline-block !important;
  vertical-align: middle !important;
  box-sizing: border-box !important;
}

input[type="checkbox"]:checked {
  background-color: rgb(59 130 246) !important;
  border-color: rgb(59 130 246) !important;
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e") !important;
  background-size: 100% 100% !important;
  background-position: center !important;
  background-repeat: no-repeat !important;
}

input[type="checkbox"]:focus {
  outline: none !important;
  box-shadow: 0 0 0 2px rgb(59 130 246 / 0.2) !important;
  border-color: rgb(59 130 246) !important;
}

input[type="checkbox"]:disabled {
  opacity: 0.5 !important;
  cursor: not-allowed !important;
  background-color: rgb(243 244 246) !important;
  border-color: rgb(209 213 219) !important;
}

/* Remove any pseudo-elements that might be causing the double square */
input[type="checkbox"]::before,
input[type="checkbox"]::after {
  display: none !important;
  content: none !important;
}

/* Dark mode styles */
.dark input[type="checkbox"] {
  background-color: rgb(55 65 81) !important;
  border-color: rgb(75 85 99) !important;
  color: rgb(37 99 235) !important;
}

.dark input[type="checkbox"]:checked {
  background-color: rgb(37 99 235) !important;
  border-color: rgb(37 99 235) !important;
}

.dark input[type="checkbox"]:focus {
  box-shadow: 0 0 0 2px rgb(37 99 235 / 0.2) !important;
  border-color: rgb(37 99 235) !important;
}

.dark input[type="checkbox"]:disabled {
  background-color: rgb(55 65 81) !important;
  border-color: rgb(75 85 99) !important;
}
</style>