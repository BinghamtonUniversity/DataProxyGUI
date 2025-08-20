<template>
  <div v-if="show" class="range-field-container">
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

    <!-- Value Display -->
    <div class="flex items-center justify-between mb-2">
      <span class="text-xs text-gray-500 dark:text-gray-400">{{ min }}</span>
      <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ internalValue }}</span>
      <span class="text-xs text-gray-500 dark:text-gray-400">{{ max }}</span>
    </div>

    <!-- Range Input -->
    <input
      :id="fieldId"
      type="range"
      :min="min"
      :max="max"
      :step="step"
      :value="internalValue"
      :disabled="disabled || !edit"
      :required="required"
      class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
      @input="handleInput"
      @blur="handleBlur"
      @focus="handleFocus"
      :style="sliderBackground"
    />

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (props.errors && props.errors.length > 0)" class="mt-2 text-xs text-red-500">{{ localError || props.errors[0] }}</div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { validateField } from './validation.js';

const props = defineProps({
  name: { type: String, required: true },
  fieldId: { type: String, default: () => `field_${Math.random().toString(36).substr(2, 9)}` },
  label: { type: String, default: '' },
  value: { type: [Number, String], default: 0 },
  min: { type: [Number, String], default: 0 },
  max: { type: [Number, String], default: 100 },
  step: { type: [Number, String], default: 1 },
  required:  { type: [Boolean,String,Array], default: true },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  edit:  { type: [Boolean,String,Array], default: true },
  show:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  help: { type: String, default: '' },
  info: { type: String, default: '' },
  autofocus: { type: Boolean, default: false },
  validate: { type: Array, default: () => [] },
  errors: { type: Array, default: () => [] }
});

const emit = defineEmits(['update:value', 'validation-error', 'validation-success', 'blur', 'focus']);

const internalValue = ref(Number(props.value) || Number(props.min) || 0);
const localError = ref('');
const showInfo = ref(false);

// Add computed for slider background
const sliderBackground = computed(() => {
  const min = Number(props.min);
  const max = Number(props.max);
  const val = Number(internalValue.value);
  const percent = ((val - min) / (max - min)) * 100;
  // Blue left, gray right
  return {
    background: `linear-gradient(90deg, #3b82f6 ${percent}%, #e5e7eb ${percent}%)`
  };
});

const validate = () => {
  const errors = validateField(internalValue.value, { ...props, type: 'range' });
  localError.value = errors[0] || '';
  if (errors.length > 0) {
    emit('validation-error', { field: props.name, errors, value: internalValue.value });
  } else {
    emit('validation-success', { field: props.name, value: internalValue.value });
  }
  return errors.length === 0;
};

const handleInput = (event) => {
  internalValue.value = Number(event.target.value);
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

watch(() => props.value, (newValue) => {
  internalValue.value = Number(newValue);
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
.range-field-container {
  width: 100% !important;
  min-width: 0 !important;
  max-width: none !important;
  min-height: 0 !important;
  height: auto !important;
  max-height: none !important;
}
input[type="range"]::-webkit-slider-runnable-track {
  height: 10px;
  border-radius: 5px;
}
input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  margin-top: -5px; /* Center thumb on 10px track */
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 0 2px rgba(0,0,0,0.2);
  transition: background 0.2s;
}
input[type="range"]:focus::-webkit-slider-thumb {
  background: #2563eb;
}
input[type="range"]::-moz-range-thumb {
  width: 20px;
  height: 20px;
  margin-top: -5px;
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 0 2px rgba(0,0,0,0.2);
  transition: background 0.2s;
}
input[type="range"]:focus::-moz-range-thumb {
  background: #2563eb;
}
input[type="range"]::-ms-thumb {
  width: 20px;
  height: 20px;
  margin-top: 0px; /* Not supported in IE, but included for completeness */
  border-radius: 50%;
  background: #3b82f6;
  cursor: pointer;
  border: 2px solid #fff;
  box-shadow: 0 0 2px rgba(0,0,0,0.2);
  transition: background 0.2s;
}
input[type="range"]:focus::-ms-thumb {
  background: #2563eb;
}
input[type="range"]::-ms-fill-lower {
  /* background: #e5e7eb; */
}
input[type="range"]::-ms-fill-upper {
  /* background: #e5e7eb; */
}
input[type="range"]:focus {
  outline: none;
}
input[type="range"]:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.dark input[type="range"]::-webkit-slider-runnable-track {
  /* background: #374151; */
}
.dark input[type="range"]::-webkit-slider-thumb {
  background: #2563eb;
  border: 2px solid #1f2937;
}
.dark input[type="range"]::-moz-range-thumb {
  background: #2563eb;
  border: 2px solid #1f2937;
}
.dark input[type="range"]::-moz-range-track {
  /* background: #374151; */
}
.dark input[type="range"]::-ms-fill-lower, .dark input[type="range"]::-ms-fill-upper {
  /* background: #374151; */
}
</style> 