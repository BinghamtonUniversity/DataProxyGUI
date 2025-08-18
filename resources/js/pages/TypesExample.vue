<template>
    <Head title="Types Example" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-7xl mx-auto space-y-8">
        <!-- Header -->
        <div class="bg-white dark:!bg-gray-800 rounded-xl shadow-lg p-6">
          <h1 class="text-3xl font-bold text-gray-900 dark:!text-white mb-2">Types Example & Demo</h1>
          <p class="text-gray-600 dark:!text-gray-300">Interactive examples and JSON configuration testing for form field types</p>
        </div>

        <!-- JSON Configuration Editor -->
        <div class="bg-white dark:!bg-gray-800 rounded-xl shadow-lg p-6">
          <h2 class="text-2xl font-bold text-gray-900 dark:!text-white mb-6">JSON Configuration Editor</h2>
          <p class="text-gray-600 dark:!text-gray-300 mb-6">Edit the JSON configuration below to see the TextField component update in real-time:</p>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- JSON Editor -->
            <div>
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">JSON Configuration</h3>
              <div class="relative">
                <textarea
                  v-model="jsonConfig"
                  rows="20"
                  class="w-full px-4 py-3 text-sm font-mono border border-gray-300 dark:!border-gray-600 bg-white dark:!bg-gray-800 text-gray-900 dark:!text-white rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                  placeholder="Enter JSON configuration..."
                  @input="handleJsonChange"
                ></textarea>
                <div class="absolute top-2 right-2">
                  <button
                    @click="resetToDefault"
                    class="px-3 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors"
                  >
                    Reset
                  </button>
                </div>
              </div>
              
              <!-- JSON Validation Status -->
              <div class="mt-3">
                <div v-if="jsonError" class="text-red-500 text-sm">
                  <strong>JSON Error:</strong> {{ jsonError }}
                </div>
                <div v-else class="text-blue-500 text-sm">
                  ✓ Valid JSON configuration
                </div>
              </div>
            </div>

            <!-- Live Preview -->
            <div class="overflow-visible min-h-0">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Live Preview</h3>
              <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-gray-50 dark:bg-gray-700 min-h-[400px]">
                <div v-if="!parsedConfig" class="text-center text-gray-600 dark:text-gray-300 py-8">
                  Enter valid JSON configuration to see the preview
                </div>
                <div v-else-if="jsonError" class="text-center text-red-500 py-8">
                  Fix JSON errors to see the preview
                </div>
                <div v-else class="space-y-4">
                  <!-- Field Type Display -->
                  <div v-if="fieldComponent" class="mb-4 p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">Field Configuration:</h4>
                    <div class="text-sm text-gray-900 dark:text-white">
                      <span class="font-medium">Type:</span> 
                      <span class="ml-2 px-2 py-1 bg-blue-500 text-white rounded text-xs font-medium">
                        {{ parsedConfig.type }}
                      </span>
                    </div>
                  </div>

                  <!-- Field Component Container -->
                  <div class="field-component-container">
                    <component
                      :is="fieldComponent"
                      v-model:value="fieldValue"
                      v-bind="omit(parsedConfig, ['value'])"
                      :validate="parsedConfig.validate"
                      @update:value="handleFieldChange"
                      @validation-error="handleValidationError"
                      @validation-success="handleValidationSuccess"
                    />
                  </div>
                  
                  <!-- Field Value Display -->
                  <div class="mt-4 p-3 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">Current Value:</h4>
                    <div class="text-sm text-gray-900 dark:text-white">
                      <span v-if="fieldValue">{{ fieldValue }}</span>
                      <span v-else class="text-gray-600 dark:text-gray-300 italic">No value entered</span>
                    </div>
                  </div>

                  <!-- Validation Status -->
                  <div v-if="validationStatus" class="mt-4 p-3 rounded border" :class="validationStatus.isValid ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'bg-red-50 dark:bg-red-900/20 border-red-500'">
                    <h4 class="text-sm font-medium mb-2" :class="validationStatus.isValid ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400'">
                      {{ validationStatus.isValid ? '✓ Validation Passed' : '✗ Validation Failed' }}
                    </h4>
                    <div class="text-sm" :class="validationStatus.isValid ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400'">
                      <template v-if="!validationStatus.isValid && Array.isArray(validationStatus.errors)">
                        <ul class="list-disc ml-4">
                          <li v-for="(err, idx) in validationStatus.errors" :key="idx">{{ err }}</li>
                        </ul>
                      </template>
                      <template v-else>
                        {{ validationStatus.message }}
                      </template>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Example Configurations -->
          <div class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Example Configurations</h3>
            
            <!-- Basic Input Fields -->
            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Basic Input Fields</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                <button
                  v-for="example in basicFields"
                  :key="example.name"
                  @click="loadExample(example.config)"
                  class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-xs"
                >
                  <h5 class="font-medium text-gray-900 dark:text-white mb-1">{{ example.name }}</h5>
                  <p class="text-gray-600 dark:text-gray-300 text-xs">{{ example.description }}</p>
                </button>
              </div>
            </div>

            <!-- Specialized Input Fields -->
            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Specialized Input Fields</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                <button
                  v-for="example in specializedFields"
                  :key="example.name"
                  @click="loadExample(example.config)"
                  class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-xs"
                >
                  <h5 class="font-medium text-gray-900 dark:text-white mb-1">{{ example.name }}</h5>
                  <p class="text-gray-600 dark:text-gray-300 text-xs">{{ example.description }}</p>
                </button>
              </div>
            </div>

            <!-- Selection Fields -->
            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Selection Fields</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                <button
                  v-for="example in selectionFields"
                  :key="example.name"
                  @click="loadExample(example.config)"
                  class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-xs"
                >
                  <h5 class="font-medium text-gray-900 dark:text-white mb-1">{{ example.name }}</h5>
                  <p class="text-gray-600 dark:text-gray-300 text-xs">{{ example.description }}</p>
                </button>
              </div>
            </div>

            <!-- Container Fields -->
            <div class="mb-6">
              <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wide">Container Fields</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-2">
                <button
                  v-for="example in containerFields"
                  :key="example.name"
                  @click="loadExample(example.config)"
                  class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors text-left text-xs"
                >
                  <h5 class="font-medium text-gray-900 dark:text-white mb-1">{{ example.name }}</h5>
                  <p class="text-gray-600 dark:text-gray-300 text-xs">{{ example.description }}</p>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup >

import { ref, computed, watch, nextTick } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

const breadcrumbs = [
  {
    title: 'Types Example',
    href: '/types-example',
  },
];
import TextField from '../components/fields/TextField.vue';
import TextAreaField from '../components/fields/TextAreaField.vue';
import TelField from '../components/fields/TelField.vue';
import EmailField from '../components/fields/EmailField.vue';
import PasswordField from '../components/fields/PasswordField.vue';
import URLField from '../components/fields/URLField.vue';
import DateField from '../components/fields/DateField.vue';
import NumberField from '../components/fields/NumberField.vue';
import CurrencyField from '../components/fields/CurrencyField.vue';
import ColorField from '../components/fields/ColorField.vue';
import HiddenField from '../components/fields/HiddenField.vue';
import SelectField from '../components/fields/SelectField.vue';
import RadioField from '../components/fields/RadioField.vue';
import ComboboxField from '../components/fields/ComboboxField.vue';
import RangeField from '../components/fields/RangeField.vue';
import CheckboxField from '../components/fields/CheckboxField.vue';
import SwitchField from '../components/fields/SwitchField.vue';
import FieldsetField from '../components/fields/FieldsetField.vue';


// JSON configuration state
const jsonConfig = ref(`{
  "name": "custom-field",
  "label": "Custom Field",
  "placeholder": "Enter some text...",
  "type": "text",
  "required": false,
  "help": "This is a custom field with JSON configuration",
  "info": "Hover for more information",
  "limit": 100,
  "value": "Hello World"
}`);

// Field value and validation
const fieldValue = ref('');
const validationStatus = ref(null);

// JSON parsing and validation
const jsonError = ref('');
const parsedConfig = computed(() => {
  try {
    const parsed = JSON.parse(jsonConfig.value);
    jsonError.value = '';
    return parsed;
  } catch (error) {
    jsonError.value = error.message;
    return null;
  }
});

// Check if the type is valid for specific field components
const isValidTextFieldType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'text';
});

const isValidTextAreaType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'textarea';
});

const isValidTelType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'tel';
});

const isValidEmailType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'email';
});

const isValidPasswordType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'password';
});

const isValidURLType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'url';
});

const isValidDateType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'date';
});

const isValidNumberType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'number';
});

const isValidCurrencyType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'currency';
});

const isValidColorType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'color';
});

const isValidHiddenType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'hidden';
});

const isValidSelectType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'select';
});

const isValidRadioType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'radio';
});

const isValidComboboxType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  
  return parsedConfig.value.type === 'combobox';
});

const isValidRangeType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  return parsedConfig.value.type === 'range';
});

const isValidCheckboxType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  return parsedConfig.value.type === 'checkbox';
});

const isValidSwitchType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  return parsedConfig.value.type === 'switch';
});

const isValidFieldsetType = computed(() => {
  if (!parsedConfig.value || !parsedConfig.value.type) return false;
  return parsedConfig.value.type === 'fieldset';
});

// Get the appropriate component to render
const fieldComponent = computed(() => {
  if (isValidTextAreaType.value) return TextAreaField;
  if (isValidTelType.value) return TelField;
  if (isValidEmailType.value) return EmailField;
  if (isValidPasswordType.value) return PasswordField;
  if (isValidURLType.value) return URLField;
  if (isValidDateType.value) return DateField;
  if (isValidNumberType.value) return NumberField;
  if (isValidCurrencyType.value) return CurrencyField;
  if (isValidColorType.value) return ColorField;
  if (isValidHiddenType.value) return HiddenField;
  if (isValidSelectType.value) return SelectField;
  if (isValidRadioType.value) return RadioField;
  if (isValidComboboxType.value) return ComboboxField;
  if (isValidRangeType.value) return RangeField;
  if (isValidCheckboxType.value) return CheckboxField;
  if (isValidSwitchType.value) return SwitchField;
  if (isValidFieldsetType.value) return FieldsetField;
  if (isValidTextFieldType.value) return TextField;
  return null;
});

// Example configurations - one per field type
const exampleConfigs = [
  {
    name: 'Text Field',
    description: 'Basic text input field',
    config: `{
  "name": "text-field",
  "label": "Text Input",
  "placeholder": "Enter some text",
  "type": "text",
  "required": false,
  "help": "A simple text input field"
}`
  },
  {
    name: 'Textarea Field',
    description: 'Multi-line text input',
    config: `{
  "name": "textarea-field",
  "label": "Description",
  "placeholder": "Enter your description here...",
  "type": "textarea",
  "required": false,
  "help": "A multi-line text area field"
}`
  },
  {
    name: 'Email Field',
    description: 'Email input with validation',
    config: `{
  "name": "email-field",
  "label": "Email Address",
  "placeholder": "Enter your email",
  "type": "email",
  "required": false,
  "help": "A simple email input field"
}`
  },
  {
    name: 'URL Field',
    description: 'URL input with validation',
    config: `{
  "name": "url-field",
  "label": "Website URL",
  "placeholder": "https://example.com",
  "type": "url",
  "required": false,
  "help": "A simple URL input field"
}`
  },
  {
    name: 'Phone Field',
    description: 'Phone input with validation',
    config: `{
  "name": "phone-field",
  "label": "Phone Number",
  "placeholder": "Enter your phone number",
  "type": "tel",
  "required": false,
  "help": "A simple phone input field"
}`
  },
  {
    name: 'Password Field',
    description: 'Password input with character count',
    config: `{
  "name": "password-field",
  "label": "Password",
  "placeholder": "Enter your password",
  "type": "password",
  "required": false,
  "help": "A simple password input field"
}`
  },
  {
    name: 'Date Field',
    description: 'Date input with multiple formats',
    config: `{
  "name": "date-field",
  "label": "Event Date & Time",
  "type": "date",
  "format": {
    "input": "MM/DD/YYYY h:mm A"
  },
  "required": false,
  "help": "Select date and time"
}`
  },
  {
    name: 'Number Field',
    description: 'Number input with validation',
    config: `{
  "name": "number-field",
  "label": "Age",
  "placeholder": "Enter your age",
  "type": "number",
  "min": 0,
  "max": 120,
  "step": 1,
  "required": false,
  "help": "Enter your age in years"
}`
  },
  {
    name: 'Currency Field',
    description: 'Currency input with formatting',
    config: `{
  "name": "currency-field",
  "label": "Price",
  "placeholder": "0.00",
  "type": "currency",
  "min": 0,
  "max": 10000,
  "step": 0.01,
  "currency": "USD",
  "locale": "en-US",
  "showFormattedValue": true,
  "required": false,
  "help": "Enter the price in dollars"
}`
  },
  {
    name: 'Color Field',
    description: 'Color picker with hex input',
    config: `{
  "name": "color-field",
  "label": "Theme Color",
  "placeholder": "#000000",
  "type": "color",
  "showColorPreview": true,
  "required": false,
  "help": "Choose a color for your theme"
}`
  },
  {
    name: 'Hidden Field',
    description: 'Hidden input for internal values',
    config: `{
  "name": "hidden-field",
  "label": "Session ID",
  "type": "hidden",
  "value": "sess_123456789",
  "showDebug": true,
  "required": true,
  "help": "Internal session identifier"
}`
  },
  {
    name: 'Select Field',
    description: 'Dropdown select with options',
    config: `{
  "name": "select-field",
  "label": "Label",
  "type": "select",
  "multiple": true,
  "showColumn": true,
  "options": [
    {
      "label": "optional section label",
      "type": "optgroup",
      "options": [
        {
          "label": "label1",
          "value": "value1"
        },
        {
          "label": "label2",
          "value": "value2"
        }
      ]
    },
    {
      "label": "Numbers",
      "type": "optgroup",
      "min": 2,
      "max": 5
    }
  ],
  "required": false,
  "help": "Select one or more options from the dropdown"
}`
  },
  {
    name: 'Radio Field',
    description: 'Radio button group with options',
    config: `{
  "name": "radio-field",
  "label": "Preferred Contact Method",
  "type": "radio",
  "options": [
    {
      "label": "Contact Methods",
      "type": "optgroup",
      "options": [
        {
          "label": "Email",
          "value": "email"
        },
        {
          "label": "Phone",
          "value": "phone"
        },
        {
          "label": "SMS",
          "value": "sms"
        }
      ]
    },
    {
      "label": "Numbers",
      "type": "optgroup",
      "min": 2,
      "max": 5
    }
  ],
  "required": true,
  "help": "Select your preferred contact method"
}`
  },
  {
    name: 'Combobox Field',
    description: 'Combined dropdown and text input',
    config: `{
  "name": "combobox-field",
  "label": "Choose a Fruit or Number",
  "placeholder": "Select or type...",
  "type": "combobox",
  "options": [
    {
      "label": "Fruits",
      "type": "optgroup",
      "options": [
        { "label": "Apple", "value": "apple" },
        { "label": "Banana", "value": "banana" },
        { "label": "Cherry", "value": "cherry" }
      ]
    },
    {
      "label": "Numbers",
      "type": "optgroup",
      "min": 1,
      "max": 3
    }
  ],
  "required": false,
  "help": "Select a fruit, a number, or type your own value"
}`
  },
  {
    name: 'Range Field',
    description: 'Slider input for numeric range',
    config: `{
    "name": "range-field",
    "label": "Satisfaction Level",
    "type": "range",
    "min": 1,
    "max": 10,
    "step": 1,
    "value": 5,
    "required": true,
    "help": "Select your satisfaction level from 1 to 10"
  }`
  },
  {
    name: 'Checkbox Field',
    description: 'Boolean checkbox with custom labels',
    config: `{
  "name": "checkbox-field",
  "label": "Accept Terms",
  "type": "checkbox",
  "value": false,
  "showColumn": true,
  "options": [
    { "label": "I do not accept" },
    { "label": "I accept the terms and conditions" }
  ],
  "required": true,
  "help": "Please accept the terms to continue"
}`
  },
  {
    name: 'Switch Field',
    description: 'Toggle switch for boolean values',
    config: `{
  "name": "switch-field",
  "label": "Enable Notifications",
  "type": "switch",
  "value": true,
  "required": false,
  "help": "Toggle to enable or disable notifications"
}`
  },
  {
    name: 'Fieldset Field',
    description: 'Container for grouping related fields',
    config: `{
  "name": "personal-info",
  "label": "Personal Information",
  "type": "fieldset",
  "required": false,
  "help": "Group related fields together",
  "fields": [
    {
      "name": "first-name",
      "label": "First Name",
      "type": "text",
      "required": true
    },
    {
      "name": "last-name", 
      "label": "Last Name",
      "type": "text",
      "required": true
    },
    {
      "name": "email",
      "label": "Email Address",
      "type": "email",
      "required": true
    }
  ]
}`
  }
];

// Group examples by category
const basicFields = computed(() => {
  return exampleConfigs.filter(example => {
    const config = JSON.parse(example.config);
    return ['text', 'textarea', 'email', 'url', 'tel', 'password'].includes(config.type);
  });
});

const specializedFields = computed(() => {
  return exampleConfigs.filter(example => {
    const config = JSON.parse(example.config);
    return ['date', 'number', 'currency', 'color', 'hidden'].includes(config.type);
  });
});

const selectionFields = computed(() => {
  return exampleConfigs.filter(example => {
    const config = JSON.parse(example.config);
    return ['select', 'radio', 'combobox', 'range', 'checkbox', 'switch'].includes(config.type);
  });
});

const containerFields = computed(() => {
  return exampleConfigs.filter(example => {
    const config = JSON.parse(example.config);
    return ['fieldset'].includes(config.type);
  });
});

// Methods
const handleJsonChange = () => {
  validationStatus.value = null;
  try {
    const parsed = JSON.parse(jsonConfig.value);
    if (typeof parsed.value === 'undefined') {
      fieldValue.value = '';
      console.log('fieldValue reset to empty string');
    }
    // else: do not reset, let the watcher handle it
  } catch {
    fieldValue.value = '';
  }
};

let updatingFromJson = false;

// Watch for JSON changes and always sync fieldValue to the value in the config
watch(jsonConfig, async () => {
  try {
    const parsed = JSON.parse(jsonConfig.value);
    updatingFromJson = true;
    fieldValue.value = typeof parsed.value !== 'undefined' ? parsed.value : '';
    await nextTick();
    updatingFromJson = false;

  } catch {
    fieldValue.value = '';
    updatingFromJson = false;
  }
  validationStatus.value = null;
}, { immediate: true });

const handleFieldChange = (value) => {
  if (!updatingFromJson) {
    try {
      const parsed = JSON.parse(jsonConfig.value);
      parsed.value = value;
      jsonConfig.value = JSON.stringify(parsed, null, 2);
    } catch (e) {
      // If JSON is invalid, do nothing
    }
  }
  fieldValue.value = value;
};

const handleValidationError = (validationData) => {
  validationStatus.value = {
    isValid: false,
    message: validationData.errors[0],
    errors: validationData.errors
  };
  console.log('Validation error:', validationData);
};

const handleValidationSuccess = (validationData) => {
  validationStatus.value = {
    isValid: true,
    message: 'Field is valid'
  };
  console.log('Validation success:', validationData);
};

const loadExample = (config) => {
  jsonConfig.value = config;
  fieldValue.value = '';
  validationStatus.value = null;
};

const resetToDefault = () => {
  jsonConfig.value = `{
  "name": "custom-field",
  "label": "Custom Field",
  "placeholder": "Enter some text...",
  "type": "text",
  "required": false,
  "help": "This is a custom field with JSON configuration",
  "info": "Hover for more information",
  "limit": 100
}`;
  fieldValue.value = '';
  validationStatus.value = null;
};

function omit(obj, keys) {
  const result = { ...obj };
  for (const key of keys) {
    delete result[key];
  }
  return result;
}
</script>

<style scoped>
/* Custom styles for the JSON editor */
textarea {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  line-height: 1.5;
}

/* Field component container to handle dynamic heights */
.field-component-container {
  width: 100%;
  min-height: 0;
  overflow: visible;
}

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

.dark .border-gray-300 {
  border-color: rgb(75 85 99) !important; /* gray-600 */
}
</style> 