<template>
  <div v-if="show" :class="{ 'switch-field-container': !inFieldset }">
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

    <!-- Switch Input -->
    <div class="flex items-start space-x-3" :class="{ 'flex-col space-y-2': showColumn }">
      <div class="flex items-center">
                 <button
           :id="fieldId"
           type="button"
           :disabled="disabled || !edit"
           :required="required"
           class="relative inline-flex h-7 w-12 items-center rounded-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 shadow-sm"
                                  :class="[
              internalValue 
                ? 'focus:ring-blue-500/20 bg-blue-600 dark:bg-blue-500 shadow-md' 
                : 'focus:ring-gray-500/20 bg-gray-300 dark:bg-gray-600 shadow-inner',
              disabled || !edit 
                ? 'cursor-not-allowed opacity-50' 
                : 'cursor-pointer hover:shadow-md'
            ]"
           @click="handleToggle"
           @blur="handleBlur"
           @focus="handleFocus"
         >
           <span
             class="inline-block h-5 w-5 transform rounded-full transition-all duration-200 ease-in-out shadow-md border border-gray-200"
             :class="internalValue ? 'translate-x-6' : 'translate-x-1'"
           ></span>
         </button>
                 <label 
           :for="fieldId" 
           class="ml-3 text-sm font-medium text-gray-900 dark:text-white cursor-pointer select-none"
           :class="{ 'cursor-not-allowed opacity-50': disabled || !edit }"
         >
           {{ getSwitchLabel() }}
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
  required:  { type: [Boolean,String,Array], default: false },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  edit:  { type: [Boolean,String,Array], default: true },
  show:  { type: [Boolean,String,Array], default: true },
  parse :  { type: [Boolean,String,Array], default: true },
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
  const errors = validateField(internalValue.value, { ...props, type: 'switch' });
  localError.value = errors[0] || '';
  if (errors.length > 0) {
    emit('validation-error', { field: props.name, errors, value: internalValue.value });
  } else {
    emit('validation-success', { field: props.name, value: internalValue.value });
  }
  return errors.length === 0;
};

const handleToggle = () => {

  internalValue.value = !internalValue.value ? true : false;
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

const getSwitchLabel = () => {
  if (props.options && props.options.length >= 2) {
    // Use custom labels from options
    return internalValue.value ? props.options[1].label : props.options[0].label;
  }
  // Default labels
  return internalValue.value ? 'true' : 'false';
};

watch(() => props.value, (newValue) => {
  internalValue.value = Boolean(newValue);
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
.switch-field-container {
  width: 100% !important;
  min-width: 0 !important;
  max-width: none !important;
  min-height: 0 !important;
  height: auto !important;
  max-height: none !important;
}

/* Override @tailwindcss/forms styles for switch */
.switch-field-container {
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

/* Switch button styles */
button[type="button"] {
  -webkit-appearance: none !important;
  -moz-appearance: none !important;
  appearance: none !important;
  border: none !important;
  background: none !important;
  padding: 0 !important;
  margin: 0 !important;
  outline: none !important;
}

button[type="button"]:focus {
  outline: none !important;
  ring: 2px !important;
}

button[type="button"]:disabled {
  opacity: 0.5 !important;
  cursor: not-allowed !important;
}

button[type="button"]:active {
  transform: scale(0.95) !important;
}

/* Enhanced switch thumb styles */
button[type="button"] span {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

.dark button[type="button"] span {
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3) !important;
  border-color: rgb(75 85 99) !important;
}

/* Force background colors to override @tailwindcss/forms */
button[type="button"].bg-gray-200 {
  background-color: rgb(229 231 235) !important;
}

button[type="button"].bg-gray-300 {
  background-color: rgb(209 213 219) !important;
}

button[type="button"].bg-blue-600 {
  background-color: rgb(37 99 235) !important;
}

button[type="button"].bg-blue-500 {
  background-color: rgb(59 130 246) !important;
}

button[type="button"].bg-red-500 {
  background-color: rgb(239 68 68) !important;
}

button[type="button"].bg-red-600 {
  background-color: rgb(220 38 38) !important;
}

/* Dark mode overrides */
.dark button[type="button"].dark\:bg-gray-600 {
  background-color: rgb(75 85 99) !important;
}

.dark button[type="button"].dark\:bg-blue-500 {
  background-color: rgb(59 130 246) !important;
}

.dark button[type="button"].dark\:bg-red-600 {
  background-color: rgb(220 38 38) !important;
}
</style> 