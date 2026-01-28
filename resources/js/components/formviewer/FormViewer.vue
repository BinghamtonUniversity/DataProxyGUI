<template>
  <div class="form-viewer-container">
    <!-- Form Header -->
    <div v-if="formConfig.label || formConfig.name || formConfig.title || formConfig.description" class="form-header mb-6">
      <h1 v-if="formConfig.label || formConfig.name || formConfig.title" class="text-2xl font-bold text-gray-900 dark:text-white mb-2">
        {{ formConfig.label || formConfig.name || formConfig.title }}
      </h1>
      <p v-if="formConfig.description" class="text-gray-600 dark:text-gray-300">
        {{ formConfig.description }}
      </p>
    </div>

    <!-- Form Content -->
    <div v-if="formConfig.fields && formConfig.fields.length > 0 && isFormDataInitialized" class="form-content">
      <div class="grid grid-cols-12 gap-4 auto-rows-auto items-start">
        <div 
          v-for="(field, index) in formConfig.fields.filter(field => field && typeof field === 'object' && field.name)" 
          :key="field?.name || index" 
          v-show="shouldShowField(field, { ...formData })"
          :class="[
            getFieldLayoutClasses(field),
            'field-wrapper',
            { 'has-error': fieldErrors[field.name] && fieldErrors[field.name].length > 0 }
          ]"
        >
          <!-- Render field using our modern field components -->
          <ArrayField
            v-if="debugArrayCondition(field)"
            :field="field"
            :value="formData[field.name] || []"
            :disabled="disabled || field.disabled"
            :edit="edit && shouldEditField(field, { ...formData })"
            @update:value="(value) => handleFieldChange(field.name, value)"
            @validation-error="(data) => handleValidationError(field.name, data)"
            @validation-success="(data) => handleValidationSuccess(field.name, data)"
          />
          <component
            v-else-if="getFieldComponent(field.type) && (field.type !== 'fieldset' || isFieldsetReady(field))"
            :is="getFieldComponent(field.type)"
            v-bind="field.type === 'fieldset' ? {
              ...field,
              show: shouldShowField(field, { ...formData }),
              edit: shouldEditField(field, { ...formData }),
              formData: { ...formData }
            } : field.type === 'output' ? { field } : {
              ...field,
              errors: fieldErrors[field.name] || []
            }"
            :value="getSafeFieldValue(field)"
            :disabled="disabled || field.disabled"
            :edit="edit && shouldEditField(field, { ...formData })"
            @update:value="(value) => handleFieldChange(field.name, value)"
            @validation-error="(data) => handleValidationError(field.name, data)"
            @validation-success="(data) => handleValidationSuccess(field.name, data)"
          />
          <div v-else class="text-sm text-red-500 dark:text-red-400 italic">
            Unknown field type: {{ field.type || 'undefined' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8 text-gray-600 dark:text-gray-300">
      <p>No fields to display. Please provide a valid form configuration.</p>
    </div>

    <!-- Form Actions -->
    <div v-if="shouldShowActions" class="form-actions mt-8 flex justify-end space-x-4">
      <button
        v-for="action in mergedActions"
        :key="action.type"
        @click="handleAction(action)"
        type="button"
        :class="getActionClasses(action)"
        :disabled="action.disabled || isSubmitting"
      >
        <font-awesome-icon v-if="action.icon" :icon="action.icon" class="w-4 h-4 mr-2" />
        <span v-if="isSubmitting">Submitting...</span>
        <span v-else v-html="action.label"></span>
      </button>
    </div>

    <!-- Validation Summary -->
    <div v-if="validationErrors.length > 0" class="validation-summary mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg shadow-sm">
      <div class="flex items-center mb-3">
        <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <h3 class="text-sm font-semibold text-red-800 dark:text-red-200">
          Please fix the following {{ validationErrors.length === 1 ? 'error' : 'errors' }}:
        </h3>
      </div>
      <ul class="text-sm text-red-700 dark:text-red-300 space-y-2">
        <li v-for="error in validationErrors" :key="error.field" class="flex items-start">
          <span class="text-red-500 mr-2 mt-0.5">•</span>
          <span>
            <strong class="font-medium">{{ getFieldLabel(error.field) }}:</strong> 
            {{ error.message }}
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed, nextTick } from 'vue';
import { shouldShowField, shouldEditField, shouldParseField, resolveFieldProperties } from '../fields/conditionalLogic.js';
import { validateField } from '../fields/validation.js';
import {
  TextField,
  TextAreaField,
  TelField,
  EmailField,
  PasswordField,
  URLField,
  DateField,
  NumberField,
  CurrencyField,
  ColorField,
  HiddenField,
  SelectField,
  RadioField,
  ComboboxField,
  RangeField,
  CheckboxField,
  SwitchField,
  FieldsetField,
  ArrayField,
  OutputField,
  CronField,
  MonacoEditorField
} from '../fields';

const props = defineProps({
  formConfig: {
    type: Object,
    required: true
  },
  initialData: {
    type: Object,
    default: () => ({})
  },
  disabled: {
    type: Boolean,
    default: false
  },
  edit: {
    type: Boolean,
    default: true
  },
  showActions: {
    type: Boolean,
    default: true
  },
  showSubmitButton: {
    type: Boolean,
    default: true
  },
  showResetButton: {
    type: Boolean,
    default: true
  },
  submitButtonText: {
    type: String,
    default: 'Submit'
  },
  // Custom actions to override defaults
  // Format: [{ type: 'save', action: 'save', label: 'Save', modifiers: 'btn btn-success' }]
  // If custom actions are provided, they will replace the default Submit/Cancel buttons
  actions: {
    type: Array,
    default: () => [],
    validator: (actions) => {
      return actions.every(action => {
        return action && typeof action === 'object' && action.type && action.action && action.label;
      });
    }
  },
  actionHandler: {
    type: Function,
    default: null
  },
  // Whether to show default actions when no custom actions are provided
  showDefaultActions: {
    type: Boolean,
    default: null
  },
  // Custom cancel action - can be 'reset', 'close', or a custom function
  cancelAction: {
    type: String,
    default: 'reset',
    validator: (value) => ['reset', 'close'].includes(value)
  },
  // Whether to automatically validate before calling actionHandler for 'save' actions
  // When true, validation will run automatically and actionHandler will only be called if validation passes
  validateOnSubmit: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:modelValue', 'change', 'submit', 'reset', 'validation-error', 'validation-success', 'action', 'customAction', 'actionHandler']);

const formData = ref({});
const validationErrors = ref([]);
const isSubmitting = ref(false);
const fieldErrors = ref({}); // Track errors for individual fields


// Check if form data is initialized
const isFormDataInitialized = computed(() => {
  const result = Object.keys(formData.value).length > 0 || (props.formConfig && props.formConfig.fields && props.formConfig.fields.length === 0);
  return result;
});

// Default actions
const defaultActions = computed(() => [
  {
    type: 'save',
    action: 'save',
    label: 'Submit',
    modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors',
    disabled: isSubmitting.value
  },
  {
    type: 'cancel',
    action: props.cancelAction,
    label: 'Cancel',
    modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors'
  }
]);

// Merge default and custom actions
const mergedActions = computed(() => {
  if (props.actions && props.actions.length > 0) {
    // Use custom actions, but ensure they have proper structure
    return props.actions.map(action => ({
      ...action,
      disabled: action.disabled || (action.type === 'save' && isSubmitting.value)
    }));
  }
  
  if (props.showDefaultActions) {
    return defaultActions.value;
  }
  
  return [];
});

// Check if we should show actions
const shouldShowActions = computed(() => {
  return props.showActions && mergedActions.value.length > 0;
});

// Debug array condition
const debugArrayCondition = (field) => {
  const condition = field.array || (field.type === 'fieldset' && field.array);

  return condition;
};

// Ensure fieldset values are always objects, and other types have appropriate defaults
const getSafeFieldValue = (field) => {
  const fieldValue = formData.value[field.name];

  // Handle fieldsets: must be an object (unless they have array attribute)
  if (field.type === 'fieldset') {
    // If fieldset has array attribute, it should be handled as an array
    if (field.array) {
      return Array.isArray(fieldValue) ? fieldValue : [];
    }
    // Regular fieldset: must be an object
    const value = formData.value[field.name] || field.defaultValue || {};
    return (typeof value === 'object' && value !== null) ? value : {};
  }

  // Handle booleans
  if (field.type === 'checkbox' || field.type === 'switch') {
    // For checkbox fields, preserve string values if they exist
    if (typeof fieldValue === 'string') {
      return fieldValue;
    }
    // For boolean values, return as-is
    if (typeof fieldValue === 'boolean') {
      return fieldValue;
    }
    // Default to false for undefined/null values
    return false;
  }

  // Handle arrays/multiple-selection
  if (field.array || field.multiple) {
    return Array.isArray(fieldValue) ? fieldValue : [];
  }

  // Handle all other types (text, number, etc.)
  // Use nullish coalescing operator (??) to allow "0" or "false" as valid values
  return fieldValue ?? field.defaultValue ?? '';
};

// Check if a fieldset field is ready to render (has proper object value)
const isFieldsetReady = (field) => {
  if (field.type !== 'fieldset') return true;
  const value = formData.value[field.name];
  
  // If fieldset has array attribute, it should be an array
  if (field.array) {
    return Array.isArray(value);
  }
  
  // Regular fieldset should be an object
  return typeof value === 'object' && value !== null;
};

// Get the appropriate component for a field type
const getFieldComponent = (fieldType) => {
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
    case 'fieldset': return FieldsetField;
    case 'output': return OutputField;
    case 'cron': return CronField;
    case 'monaco': return MonacoEditorField;
    case 'monaco-editor': return MonacoEditorField;
    case 'code': return MonacoEditorField;
    default: return null;
  }
};

// Handle field value changes
const handleFieldChange = (fieldName, value) => {

  // Check if this is a fieldset field
  const field = props.formConfig.fields.find(f => f && f.name === fieldName);
  if (field && field.type === 'fieldset') {

    // If fieldset has array attribute, ensure it's an array
    if (field.array) {
      if (!Array.isArray(value)) {

        value = [];
      }
    } else {
      // Regular fieldset: ensure it's an object
      if (typeof value !== 'object' || value === null) {

        value = {};
      }
    }

  }
  
  formData.value = {
    ...formData.value,
    [fieldName]: value
  };
  
  // Emit change event for user-initiated changes (only from handleFieldChange)
  emit('change', formData.value, fieldName);
  emit('update:modelValue', formData.value);
  
  const errors = validateField(value, field);
  if (errors.length > 0) {
    handleValidationError(fieldName, { errors: errors });
  } else {
    handleValidationSuccess(fieldName, { value: value });
  }
  emit('validation-error', { field: fieldName, errors: errors });
  
};

// Handle field validation errors
const handleValidationError = (fieldName, validationData) => {
  // Remove existing error for this field
  validationErrors.value = validationErrors.value.filter(error => error.field !== fieldName);
  fieldErrors.value[fieldName] = validationData.errors || []; // Update field-specific errors
  
  // Add new error
  if (validationData.errors && validationData.errors.length > 0) {
    validationErrors.value.push({
      field: fieldName,
      message: validationData.errors[0]
    });
  }
  
  emit('validation-error', { field: fieldName, errors: validationData.errors });
};

// Handle field validation success
const handleValidationSuccess = (fieldName, validationData) => {
  // Remove error for this field
  validationErrors.value = validationErrors.value.filter(error => error.field !== fieldName);
  fieldErrors.value[fieldName] = []; // Clear field-specific errors on success
  
  emit('validation-success', { field: fieldName, value: validationData.value });
};

// Get field layout classes
const getFieldLayoutClasses = (field) => {
  if (!field) return 'col-span-12';
  
  const columns = parseInt(field.columns || field.width || '12');
  const offset = parseInt(field.offset || '0');
  const forceRow = field.forceRow || false;
  
  let classes = '';
  
  if (columns === 12) {
    classes = 'col-span-12';
  } else {
    const colSpanMap = {
      1: 'col-span-1', 2: 'col-span-2', 3: 'col-span-3', 4: 'col-span-4',
      5: 'col-span-5', 6: 'col-span-6', 7: 'col-span-7', 8: 'col-span-8',
      9: 'col-span-9', 10: 'col-span-10', 11: 'col-span-11'
    };
    classes = colSpanMap[columns] || 'col-span-12';
    
    if (offset > 0 && !forceRow) {
      const colStartMap = {
        1: 'col-start-2', 2: 'col-start-3', 3: 'col-start-4', 4: 'col-start-5',
        5: 'col-start-6', 6: 'col-start-7', 7: 'col-start-8', 8: 'col-start-9',
        9: 'col-start-10', 10: 'col-start-11', 11: 'col-start-12'
      };
      classes += ' ' + (colStartMap[offset] || '');
    }
  }
  
  if (forceRow) {
    classes += ' force-new-row';
  }
  
  return classes.trim();
};

// Get field label for validation summary
const getFieldLabel = (fieldName) => {
  const field = props.formConfig.fields.find(f => f && f.name === fieldName);
  if (field && field.label) {
    return field.label;
  }
  // Fallback to field name with proper formatting
  return fieldName.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase());
};

// Get action classes - use custom modifiers or default classes
const getActionClasses = (action) => {
  // If custom actions have modifiers, use them
  if (props.actions && props.actions.length > 0 && action.modifiers) {
    return action.modifiers;
  }
  
  // Otherwise use the default modifiers from the action object
  return action.modifiers || 'px-4 py-2 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors';
};

// Validation functions
const validateFieldLocal = (fieldName) => {

  const field = props.formConfig.fields.find(f => f.name === fieldName);
  if (!field) return true;

  const fieldValue = formData.value[fieldName];
  
  // Ensure config has all required properties for validation
  const config = {
    type: field.type || 'text',
    required: field.required || false,
    minLength: field.minLength,
    maxLength: field.maxLength,
    pattern: field.pattern,
    min: field.min,
    max: field.max,
    ...field
  };
  
  // Use the imported validation function (which now handles fieldsets and arrays recursively)
  // validateField already handles nested fields in fieldsets and arrays, so we don't need to recurse here
  const errors = validateField(fieldValue, config, formData.value);

  // If there are errors, add them to validation errors
  if (errors.length > 0) {
    validationErrors.value.push({
      field: fieldName,
      message: errors[0] // For now, just show the first error
    });
    fieldErrors.value[fieldName] = errors; // Update field-specific errors
    return false;
  }

  return true;
};

const validateForm = () => {
  // Clear existing errors
  validationErrors.value = [];
  fieldErrors.value = {}; // Clear field-specific errors
  
  // Validate all fields (including nested fields recursively)
  if (props.formConfig && props.formConfig.fields) {
    props.formConfig.fields.forEach(field => {
      if (field && field.name) {
        validateFieldLocal(field.name);
      }
    });
  }
  
  // Force re-render of all fields to show validation errors
  // This ensures that failed fields show their red styling immediately
  if (validationErrors.value.length > 0) {
    // Trigger a reactive update to force field components to re-render with errors
    const currentFormData = { ...formData.value };
    formData.value = {};
    nextTick(() => {
      formData.value = currentFormData;
    });
  }
  
  return validationErrors.value.length === 0;
};

// Handle action clicks
const handleAction = async (action) => {
  const { type, action: actionName } = action;
  
  // If validateOnSubmit is enabled and this is a save action, validate first
  if (props.validateOnSubmit && (type === 'save' ||type === 'submit' ||  actionName === 'save' || actionName === 'submit')) {
    const isValid = validateForm();
    if (!isValid) {
      // Validation failed - errors are already displayed by FormViewer
      // Don't call actionHandler if validation fails
      return;
    }
  }
  
  // If actionHandler is provided, call it first
  if (props.actionHandler && typeof props.actionHandler === 'function') {
    try {
      await props.actionHandler({ type, action: actionName, formData: formData.value });
      return; // If actionHandler handles the action, don't continue with default behavior
    } catch (error) {
      console.error('Error in actionHandler:', error);
      // Continue with default behavior if actionHandler fails
    }
  }
  
  switch (type) {
    case 'save':
      await submitForm();
      break;
    case 'cancel':
      if (actionName === 'close') {
        // Emit close event for parent to handle
        emit('action', { type: 'close', action: 'close', formData: formData.value });
      } else {
        // Default to reset behavior
        resetForm();
      }
      break;
    default:
      // Emit custom action for parent to handle
      emit('action', { type, action: actionName, formData: formData.value });
      emit('customAction', { type, action: actionName, formData: formData.value });
      break;
  }
};

// Enhanced submit form with toastr support
const submitForm = async () => {
  isSubmitting.value = true;

  try {
    const isValid = validateForm();
    
    if (isValid) {
      emit('submit', formData.value);
     
      // Show success message if toastr is available
      if (typeof window !== 'undefined' && window.toastr) {
        window.toastr.success('Form submitted successfully!');
      } else if (typeof window !== 'undefined' && window.showToast) {
        window.showToast('Form submitted successfully!', 'success');
      }
    } else {
 
      // Don't show toast messages for validation errors - let parent handle this
      // Just scroll to the first validation error field
      if (validationErrors.value.length > 0) {
        const firstErrorField = validationErrors.value[0];
        const fieldElement = document.querySelector(`[name="${firstErrorField.field}"]`);
        if (fieldElement) {
          fieldElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
          fieldElement.focus();
        }
      }
    }
  } catch (error) {
    // Show error message if toastr is available
    if (typeof window !== 'undefined' && window.toastr) {
      window.toastr.error('Error submitting form: ' + error.message);
    } else if (typeof window !== 'undefined' && window.showToast) {
      window.showToast('Error submitting form: ' + error.message, 'error');
    }
  } finally {
    isSubmitting.value = false;
  }
};

const resetForm = () => {
  formData.value = {};
  validationErrors.value = [];
  fieldErrors.value = {}; // Clear field-specific errors on reset
  emit('reset');
  initializeFormData();
};

// Initialize form data
const initializeFormData = () => {

  if (!props.formConfig || !props.formConfig.fields || !Array.isArray(props.formConfig.fields)) {

    return;
  }
  
  const newData = {};
  
  props.formConfig.fields.filter(field => field && typeof field === 'object' && field.name).forEach(field => {
    if (field.type === 'fieldset') {
      // If fieldset has array attribute, initialize as array
      if (field.array) {
        const minItems = field.array.min || 1;
        newData[field.name] = Array(minItems).fill({});
      } else {
        newData[field.name] = {};
      }
    } else if (field.type === 'boolean' || field.type === 'checkbox' || field.type === 'switch') {
  
      // Check initialData first, then fall back to field.value
      const raw = (props.initialData && props.initialData[field.name] !== undefined) 
        ? props.initialData[field.name] 
        : field.value;
      
      // For checkbox fields, always preserve string values from initialData
      if (field.type === 'checkbox') {
        // If we have initialData with string values, preserve them
        if (props.initialData && props.initialData[field.name] !== undefined && typeof props.initialData[field.name] === 'string') {
          newData[field.name] = raw; // Keep as string
        } else if (field.options && field.options.length > 0) {
          newData[field.name] = raw; // Keep as string to match options
        } else {
          // Normalize to boolean for checkbox fields without options
          const normalized = raw === true || raw === 'true' ? true : false;
          newData[field.name] = normalized;
        }
      } else {
        // For other boolean types (boolean, switch), normalize to boolean
        const normalized = raw === true || raw === 'true' ? true : false;
        newData[field.name] = normalized;
      }

    } else if (['select', 'radio', 'combobox', 'range'].includes(field.type)) {
      if (field.multiple) {
        newData[field.name] = [];
      } else {
        // Check initialData first, then fall back to field.value
        newData[field.name] = (props.initialData && props.initialData[field.name] !== undefined) 
          ? props.initialData[field.name] 
          : (field.value || '');
      }
    } else if (field.array) {
      const minItems = field.array.min || 1;
      newData[field.name] = Array(minItems).fill('').map(() => {
        if (field.type === 'boolean' || field.type === 'checkbox' || field.type === 'switch') {
          return false;
        } else if (['select', 'radio', 'combobox', 'range'].includes(field.type)) {
          return field.multiple ? [] : (field.value || '');
        } else {
          return '';
        }
      });
    } else if (field.type === 'text') {
      // Check initialData first, then fall back to field.value
      newData[field.name] = (props.initialData && props.initialData[field.name] !== undefined) 
        ? props.initialData[field.name] 
        : (field.value || '');
    } else {
      // Check initialData first, then fall back to empty string
      newData[field.name] = (props.initialData && props.initialData[field.name] !== undefined) 
        ? props.initialData[field.name] 
        : '';
    }
  });
  
  // Merge with initial data, but preserve fieldset objects
  if (props.initialData && typeof props.initialData === 'object') {
    Object.keys(props.initialData).forEach(key => {
      const field = props.formConfig.fields.find(f => f && f.name === key);
      if (field && field.type === 'fieldset') {
        // If fieldset has array attribute, it should be an array
        if (field.array) {
          // For array fieldsets, use the initialData array directly if it's an array
          if (Array.isArray(props.initialData[key])) {
            newData[key] = props.initialData[key];
          } else if (typeof props.initialData[key] === 'object' && props.initialData[key] !== null) {
            // If it's an object but should be array, convert it
            newData[key] = [];
          }
        } else {
          // Regular fieldset: merge objects
          if (typeof props.initialData[key] === 'object' && props.initialData[key] !== null) {
            newData[key] = { ...newData[key], ...props.initialData[key] };
          }
        }
      } else {
        newData[key] = props.initialData[key];
      }
    });
  }
  formData.value = newData;
};

// Watch for changes
watch(() => props.formConfig, () => {
  initializeFormData();
}, { immediate: true, deep: true });

watch(() => props.initialData, () => {
  initializeFormData();
}, { deep: true });
// watch(formData, (newData) => {
//   emit('update:modelValue', newData);
// }, { deep: true });
// Watch for changes in formData to re-evaluate conditions
// Note: We don't emit update:modelValue here to avoid loops - it's only emitted from handleFieldChange
watch(formData, () => {
  // Force re-render when form data changes to update conditional logic
}, { deep: true });

onMounted(() => {
  initializeFormData();
});

// Expose methods for parent components
defineExpose({
  formData,
  getFormData: () => formData.value,
  setFormData: (data) => {
    formData.value = { ...formData.value, ...data };
  },
  validateForm,
  submitForm,
  resetForm,
  validationErrors,
  fieldErrors,
  clearValidationErrors: () => {
    validationErrors.value = [];
    fieldErrors.value = {};
  }
});
</script>

<style scoped>
.form-viewer-container {
  width: 100%;
  max-width: none;
}

/* Force next element to start on new row but respect column width */
.force-new-row {
  grid-column-start: 1;
  grid-row-start: auto;
}

.field-wrapper {
  min-height: 0;
  overflow: visible;
}

/* Validation error styling for field wrappers */
.field-wrapper.has-error {
  animation: errorShake 0.5s ease-in-out;
}

@keyframes errorShake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}

/* Enhanced validation summary styling */
.validation-summary {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style> 