<template>
  <div class="array-field-container">
    <!-- Array Header -->
    <!-- <div v-if="field.array && field.label" class="array-header mb-2">
      <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ field.label }}</h3>
      <p v-if="field.help" class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ field.help }}</p>
    </div> -->

    <!-- Array Items -->
    <div v-if="arrayValues.length > 0" class="array-items flex flex-col gap-2">
      <div 
        v-for="(item, index) in arrayValues" 
        :key="`${field.name}-${index}`"
        class="array-item"
      >
        <!-- Field Component -->
        <component
          :is="fieldComponent"
          v-bind="fieldProps"
          :value="item"
          :disabled="disabled"
          :edit="edit"
          @update:value="(value) => updateItem(index, value)"
          @validation-error="(data) => handleValidationError(index, data)"
          @validation-success="(data) => handleValidationSuccess(index, data)"
        />
      </div>
    </div>

    <!-- Add First Item Button (when array is empty) -->
    <div v-else-if="edit && !disabled && canAdd()" class="mt-2">
      <button
        @click="addItem"
        type="button"
        class="w-full px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors"
      >
        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        {{ getAddLabel() }}
      </button>
    </div>

    <!-- Array Validation Errors -->
    <div v-if="arrayErrors.length > 0" class="mt-3">
      <div class="text-sm text-red-600 dark:text-red-400">
        <ul class="list-disc list-inside space-y-1">
          <li v-for="error in arrayErrors" :key="error">{{ error }}</li>
        </ul>
      </div>
    </div>

    <!-- Bottom Right Controls -->
    <div v-if="arrayValues.length > 0 && edit && !disabled" class="flex justify-end gap-1 mt-2">
      <!-- Add (+) Button -->
      <button
        v-if="canAdd()"
        @click="addItem"
        type="button"
        class="icon-btn plus-btn"
        :title="getAddLabel()"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
        </svg>
      </button>
      <!-- Duplicate Button -->
      <button
        v-if="canDuplicate(arrayValues.length - 1) && canAdd()"
        @click="duplicateItem(arrayValues.length - 1)"
        type="button"
        class="icon-btn duplicate-btn"
        :title="getDuplicateLabel()"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      </button>
      <!-- Remove (-) Button -->
      <button
        v-if="canRemove(arrayValues.length - 1)"
        @click="removeItem(arrayValues.length - 1)"
        type="button"
        class="icon-btn minus-btn"
        :title="getRemoveLabel()"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
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
import FieldsetField from './FieldsetField.vue';
import OutputField from './OutputField.vue';

const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  value: {
    type: Array,
    default: () => []
  },
  disabled: {
    type: Boolean,
    default: false
  },
  edit: {
    type: Boolean,
    default: true
  },
  errors: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:value', 'validation-error', 'validation-success']);

const arrayValues = ref([]);
const arrayErrors = ref([]);

// Get the appropriate component for the field type
const fieldComponent = computed(() => {
  const fieldType = props.field.type;
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
    default: return TextField;
  }
});

// Prepare field props (remove array-specific props)
const fieldProps = computed(() => {
  const { array, ...fieldProps } = props.field;
  return fieldProps;
});

// Array configuration
const arrayConfig = computed(() => {
  return props.field.array || {};
});

const minItems = computed(() => arrayConfig.value.min || 0);
const maxItems = computed(() => arrayConfig.value.max || 10);
const addConfig = computed(() => arrayConfig.value.add || {});
const duplicateConfig = computed(() => arrayConfig.value.duplicate || {});
const removeConfig = computed(() => arrayConfig.value.remove || {});

// Initialize array values
const initializeArray = () => {
  if (Array.isArray(props.value)) {
    arrayValues.value = [...props.value];
  } else {
    // Initialize with minimum items or default
    const initialCount = Math.max(minItems.value, 0); // Start with 0 if no min requirement
    arrayValues.value = Array(initialCount).fill('').map(() => getDefaultValue());
  }
  // Ensure we have at least minItems
  while (arrayValues.value.length < minItems.value) {
    arrayValues.value.push(getDefaultValue());
  }
  emit('update:value', arrayValues.value);
};

// Get default value for the field type
const getDefaultValue = () => {
  const fieldType = props.field.type;
  if (fieldType === 'fieldset') {
    return {}; // Fieldset items should be objects
  } else if (fieldType === 'boolean' || fieldType === 'checkbox' || fieldType === 'switch') {
    return false;
  } else if ([
    'select', 'radio', 'combobox', 'range'
  ].includes(fieldType)) {
    return props.field.multiple ? [] : '';
  } else {
    return '';
  }
};

// Array control methods
const canAdd = () => {
  const enable = addConfig.value.enable;
  if (enable === false || enable === 'never') return false;
  if (enable === 'auto') return arrayValues.value.length < maxItems.value;
  return arrayValues.value.length < maxItems.value;
};

const canDuplicate = (index) => {
  const enable = duplicateConfig.value.enable;

  
  // If enable is explicitly false or 'never', return false
  if (enable === false || enable === 'never') {
  
    return false;
  }
  
  // If enable is 'auto', check if we can add more items
  if (enable === 'auto') {
    const canAddMore = arrayValues.value.length < maxItems.value;
 
    return canAddMore;
  }
  
  // If enable is true or any other truthy value, return true (if we can add more items)
  const result = arrayValues.value.length < maxItems.value;
 
  return result;
};

const canRemove = (index) => {
  const enable = removeConfig.value.enable;
  if (enable === false || enable === 'never') return false;
  if (enable === 'auto') return arrayValues.value.length > minItems.value;
  return true;
};

const addItem = () => {
  if (canAdd()) {
    const newValue = getDefaultValue();
    if (duplicateConfig.value.clone && arrayValues.value.length > 0) {
      // Clone the last item if clone is enabled
      arrayValues.value.push(JSON.parse(JSON.stringify(arrayValues.value[arrayValues.value.length - 1])));
    } else {
      arrayValues.value.push(newValue);
    }
    emit('update:value', arrayValues.value);
  }
};

const addItemAfter = (index) => {
  if (canAdd()) {
    let newValue;
    if (duplicateConfig.value.clone) {
      // Clone the current item if clone is enabled
      newValue = JSON.parse(JSON.stringify(arrayValues.value[index]));
    } else {
      // Add a new empty item
      newValue = getDefaultValue();
    }
    arrayValues.value.splice(index + 1, 0, newValue);
    emit('update:value', arrayValues.value);
  }
};

const duplicateItem = (index) => {

  if (canDuplicate(index) && canAdd()) {
    const clonedValue = JSON.parse(JSON.stringify(arrayValues.value[index]));

    arrayValues.value.splice(index + 1, 0, clonedValue);
    emit('update:value', arrayValues.value);
  }
};

const removeItem = (index) => {
  if (canRemove(index)) {
    arrayValues.value.splice(index, 1);
    emit('update:value', arrayValues.value);
  }
};

const updateItem = (index, value) => {
  arrayValues.value[index] = value;
  emit('update:value', arrayValues.value);
};

// Labels
const getItemLabel = (index) => {
  const baseLabel = props.field.label || props.field.name || 'Item';
  return `${baseLabel} ${index + 1}`;
};

const getAddLabel = () => {
  return duplicateConfig.value.label || `Add ${props.field.label || 'Item'}`;
};

const getDuplicateLabel = () => {
  return duplicateConfig.value.label || 'Duplicate';
};

const getRemoveLabel = () => {
  return removeConfig.value.label || 'Remove';
};

// Validation
const handleValidationError = (index, data) => {
  // Handle validation errors for specific array items
  emit('validation-error', {
    field: `${props.field.name}[${index}]`,
    errors: data.errors
  });
};

const handleValidationSuccess = (index, data) => {
  // Handle validation success for specific array items
  emit('validation-success', {
    field: `${props.field.name}[${index}]`,
    value: data.value
  });
};

// Watch for external value changes
watch(() => props.value, (newValue) => {
  if (Array.isArray(newValue) && JSON.stringify(newValue) !== JSON.stringify(arrayValues.value)) {
    arrayValues.value = [...newValue];
  }
}, { deep: true });

// Initialize on mount
onMounted(() => {
  initializeArray();
});
</script>

<style scoped>
.array-field-container {
  width: 100%;
}
.icon-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 0.25rem;
  background: transparent;
  color: #6b7280;
  transition: background 0.15s, color 0.15s;
}
.icon-btn:hover {
  background: #f3f4f6;
}
.plus-btn {
  color: #2563eb;
}
.plus-btn:hover {
  background: #e0e7ff;
  color: #1d4ed8;
}
.duplicate-btn {
  color: #059669;
}
.duplicate-btn:hover {
  background: #d1fae5;
  color: #047857;
}
.minus-btn {
  color: #dc2626;
}
.minus-btn:hover {
  background: #fee2e2;
  color: #b91c1c;
}
.dark .icon-btn:hover {
  background: #374151;
}
</style> 