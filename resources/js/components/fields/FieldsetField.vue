<template>
  <div v-if="show" class="fieldset-field-container">
    <!-- Fieldset Content with integrated header -->
    <div class="fieldset-content">
      <!-- Fieldset Header inside the container -->
      <div v-if="label" class="fieldset-header mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white break-words leading-relaxed">
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
        </h3>
        
        <!-- Help Text -->
        <div v-if="help" class="mt-1 text-sm text-gray-600 dark:text-gray-400" v-html="help"></div>
      </div>

      <!-- Render child fields here -->
      <div v-if="fields && fields.length > 0" class="grid grid-cols-12 gap-4 auto-rows-auto items-start">
        <div 
          v-for="(field, index) in fields" 
          :key="`${field.name || field.type}_${index}`"
          :class="getFieldLayoutClasses(field)"
          class="field-item"
        >
          <!-- ArrayField for fields with array attribute -->
          <ArrayField
            v-if="field.array"
            :field="field"
            :value="internalValue[field.name] || []"
            :disabled="disabled || field.disabled"
            :edit="edit && shouldEditField(field, combinedData)"
            @update:value="(value) => handleChildFieldChange(field.name, value)"
            @validation-error="(data) => handleChildValidationError(field.name, data)"
            @validation-success="(data) => handleChildValidationSuccess(field.name, data)"
          />
          <!-- Regular field component -->
          <component
            v-else-if="getFieldComponent(field.type, field)"
            :is="getFieldComponent(field.type, field)"
            v-bind="field.type === 'output' ? { field } : 
                   field.type === 'fieldset' ? {
                     ...field,
                     show: shouldShowField(field, combinedData),
                     edit: shouldEditField(field, combinedData),
                     formData: combinedData
                   } : {
                     ...field,
                     required: (() => {
                       if (field.required === undefined) return false;
                       if (typeof field.required === 'boolean') return field.required;
                       if (field.required === 'true' || field.required === true) return true;
                       if (field.required === 'false' || field.required === false) return false;
                       // For conditional logic (string 'conditional' or arrays), pass through as-is
                       return field.required;
                     })(),
                     disabled: typeof field.disabled === 'boolean' ? field.disabled : false,
                     show: shouldShowField(field, combinedData),
                     edit: shouldEditField(field, combinedData),
                     inFieldset: true
                   }"
            :value="internalValue[field.name] || field.value || ''"
            :disabled="disabled || field.disabled"
            :edit="edit && shouldEditField(field, formData)"
            @update:value="(value) => handleChildFieldChange(field.name, value)"
            @validation-error="(data) => handleChildValidationError(field.name, data)"
            @validation-success="(data) => handleChildValidationSuccess(field.name, data)"
          />
          <div v-else class="text-sm text-red-500 dark:text-red-400 italic">
            Unknown field type: {{ field.type || 'undefined' }}
          </div>
        </div>
      </div>
      
      <!-- Empty state -->
      <div v-else class="text-sm text-gray-500 dark:text-gray-400 italic text-center py-4">
        No fields in this section
      </div>
    </div>

    <!-- Error Message -->
    <div v-if="localError" class="mt-2 text-xs text-red-500">{{ localError }}</div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import { shouldShowField, shouldEditField } from './conditionalLogic.js';
import { validateField } from './validation.js';
import { getFieldLayoutClasses } from './functions.js';
import TextField from './TextField.vue';
import TextAreaField from './TextAreaField.vue';
import TelField from './TelField.vue';
import EmailField from './EmailField.vue';
import PasswordField from './PasswordField.vue';
import URLField from './URLField.vue';
import DateField from './DateField.vue';
import NumberField from './NumberField.vue';
import CurrencyField from './CurrencyField.vue';
import ColorField from './ColorField.vue';
import HiddenField from './HiddenField.vue';
import SelectField from './SelectField.vue';
import RadioField from './RadioField.vue';
import ComboboxField from './ComboboxField.vue';
import RangeField from './RangeField.vue';
import CheckboxField from './CheckboxField.vue';
import SwitchField from './SwitchField.vue';
import OutputField from './OutputField.vue';
import FieldsetField from './FieldsetField.vue';
import ArrayField from './ArrayField.vue';

const props = defineProps({
  name: { type: String, required: true },
  fieldId: { type: String, default: () => `fieldset_${Math.random().toString(36).substr(2, 9)}` },
  label: { type: String, default: '' },
  value: { type: [Object, Array], default: () => ({}) },
  required:  { type: [Boolean,String,Array], default: false },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  edit:  { type: [Boolean,String,Array], default: true },
  show:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  help: { type: String, default: '' },
  info: { type: String, default: '' },
  autofocus: { type: Boolean, default: false },
  validate: { type: Array, default: () => [] },
  showColumn: { type: Boolean, default: false },
  fields: { type: Array, default: () => [] },
  formData: { type: Object, default: () => ({}) },
  errors: { type: Array, default: () => [] }
});

const emit = defineEmits(['update:value', 'validation-error', 'validation-success', 'blur', 'focus']);

const internalValue = ref(props.value || {});
const localError = ref('');
const showInfo = ref(false);

// Create combined data for conditional logic (main form data + fieldset internal data)
const combinedData = computed(() => {
  return {
    ...props.formData,
    ...internalValue.value
  };
});

// Get the appropriate component for a field type
const getFieldComponent = (fieldType, field = null) => {
  // Check if fieldset has array attribute
  if (fieldType === 'fieldset' && field && field.array) {
    return ArrayField;
  }
  
  switch (fieldType) {
    case 'text': return TextField;
    case 'textarea': return TextAreaField;
    case 'tel': return TelField;
    case 'email': return EmailField;
    case 'password': return PasswordField;
    case 'url': return URLField;
    case 'date': return DateField;
    case 'number': return NumberField;
    case 'currency': return CurrencyField;
    case 'color': return ColorField;
    case 'hidden': return HiddenField;
    case 'select': return SelectField;
    case 'radio': return RadioField;
    case 'combobox': return ComboboxField;
    case 'range': return RangeField;
    case 'checkbox': return CheckboxField;
    case 'switch': return SwitchField;
    case 'output': return OutputField;
    case 'fieldset': return FieldsetField;
    case 'array': return ArrayField;
    default: return null;
  }
};

// Handle child field value changes
const handleChildFieldChange = (fieldName, value) => {
  internalValue.value = {
    ...internalValue.value,
    [fieldName]: value
  };
  emit('update:value', internalValue.value);
  validate();
};

const validate = () => {
  const errors = validateField(internalValue.value, { ...props, type: 'fieldset' });
  localError.value = errors[0] || '';
  if (errors.length > 0) {
    emit('validation-error', { field: props.name, errors, value: internalValue.value });
  } else {
    emit('validation-success', { field: props.name, value: internalValue.value });
  }
  return errors.length === 0;
};

const handleBlur = () => {
  validate();
  emit('blur', internalValue.value);
};

const handleFocus = () => {
  emit('focus', internalValue.value);
};

// Handle child field validation errors
const handleChildValidationError = (fieldName, validationData) => {
  // Store child field errors in internalValue for validation
  if (!internalValue.value.errors) {
    internalValue.value.errors = {};
  }
  internalValue.value.errors[fieldName] = validationData.errors;
  validate();
};

// Handle child field validation success
const handleChildValidationSuccess = (fieldName, validationData) => {
  // Clear child field errors
  if (internalValue.value.errors && internalValue.value.errors[fieldName]) {
    delete internalValue.value.errors[fieldName];
  }
  validate();
};

watch(() => props.value, (newValue) => {
  internalValue.value = newValue || {};
}, { immediate: true });

watch(() => props.validate, () => {
  validate();
}, { deep: true });

watch(() => props.fields, () => {
  // Re-validate when fields change
  validate();
}, { deep: true });

// Initialize internalValue with field values from props.value or field defaults
watch(() => props.fields, (newFields) => {
  if (newFields && newFields.length > 0) {
    const initialValues = {};
    newFields.forEach(field => {
      if (field.name) {
        // Use value from props.value if available, otherwise use field's default value
        initialValues[field.name] = props.value && props.value[field.name] !== undefined 
          ? props.value[field.name] 
          : field.value || '';
      }
    });
    internalValue.value = { ...internalValue.value, ...initialValues };
  }
}, { immediate: true });

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
.fieldset-field-container {
  width: 100% !important;
  min-width: 0 !important;
  max-width: none !important;
  min-height: 0 !important;
  height: auto !important;
  max-height: none !important;
}

/* Override @tailwindcss/forms styles for fieldset */
.fieldset-field-container {
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

/* Fieldset specific styles */
.fieldset-header {
  border-bottom: 1px solid rgb(229 231 235);
  margin-bottom: 1.5rem !important;
  padding-bottom: 0.5rem !important;
}

.fieldset-header h3 {
  line-height: 1.4 !important;
  margin: 0 !important;
  padding: 0 !important;
  word-wrap: break-word !important;
  overflow-wrap: break-word !important;
}

.dark .fieldset-header {
  border-bottom-color: rgb(75 85 99);
}

.fieldset-content {
  margin-top: 1rem;
  margin-bottom: 1.5rem;
  border-radius: 0.75rem;
  border: 1px solid rgb(114, 114, 114);
  padding: 1rem;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

/* Ensure proper spacing between field items */
.field-item {
  margin-bottom: 1rem !important;
}

.field-item:last-child {
  margin-bottom: 0 !important;
}

/* Force next element to start on new row but respect column width */
.force-new-row {
  grid-column-start: 1;
  grid-row-start: auto;
}

/* Ensure field items have proper width */
.field-item > * {
  width: 100%;
}
</style> 