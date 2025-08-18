# FormViewer Component

The FormViewer component renders dynamic forms based on JSON configuration and supports both default and custom actions.

## Default Actions

By default, FormViewer provides two standard actions:

1. **Submit** - Saves the form data and shows a success message
2. **Cancel** - Clears all form fields and resets to initial state

### Default Action Behavior

- **Submit Action**: 
  - Validates the form
  - Emits `submit` event with form data
  - Shows success toastr message (if available)
  - Handles errors with error toastr message

- **Cancel Action**:
  - Clears all form data
  - Resets validation errors
  - Emits `reset` event
  - Re-initializes form with default values

## Custom Actions

You can override the default actions by providing a custom `actions` array in your form configuration.

### Action Object Structure

```javascript
{
  type: 'save',           // Action type (save, cancel, custom)
  action: 'save',         // Action identifier
  label: 'Save Draft',    // Button text (supports HTML)
  modifiers: 'px-4 py-2 text-sm font-medium text-white bg-green-600...', // CSS classes
  disabled: false         // Optional: disable the button
}
```

### Example Custom Actions

```javascript
{
  name: 'my_form',
  label: 'My Form',
  fields: [...],
  actions: [
    {
      type: 'save',
      action: 'save',
      label: 'Save Draft',
      modifiers: 'px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500/20 transition-colors'
    },
    {
      type: 'submit',
      action: 'submit',
      label: '<i class="fa fa-paper-plane"></i> Send Message',
      modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors'
    },
    {
      type: 'cancel',
      action: 'cancel',
      label: '<i class="fa fa-times"></i> Cancel',
      modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors'
    }
  ]
}
```

## Props

### Action-related Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `actions` | Array | `[]` | Custom actions to override defaults |
| `showDefaultActions` | Boolean | `true` | Whether to show default actions when no custom actions provided |
| `showActions` | Boolean | `true` | Whether to show any actions at all |

### Other Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `formConfig` | Object | Required | Form configuration object |
| `initialData` | Object | `{}` | Initial form data |
| `disabled` | Boolean | `false` | Disable all form fields |
| `edit` | Boolean | `true` | Enable/disable editing |
| `showSubmitButton` | Boolean | `true` | Show submit button (legacy) |
| `showResetButton` | Boolean | `true` | Show reset button (legacy) |
| `submitButtonText` | String | `'Submit'` | Submit button text (legacy) |

## Events

### Action Events

| Event | Payload | Description |
|-------|---------|-------------|
| `action` | `{ type, action, formData }` | Emitted when custom action is clicked |

### Other Events

| Event | Payload | Description |
|-------|---------|-------------|
| `submit` | `formData` | Form submitted successfully |
| `reset` | - | Form reset |
| `update:modelValue` | `formData` | Form data updated |
| `validation-error` | `{ field, errors }` | Field validation failed |
| `validation-success` | `{ field, value }` | Field validation passed |

## Usage Examples

### Basic Usage (Default Actions)

```vue
<template>
  <FormViewer
    :form-config="formConfig"
    :initial-data="initialData"
    @submit="handleSubmit"
    @reset="handleReset"
  />
</template>
```

### Custom Actions

```vue
<template>
  <FormViewer
    :form-config="formConfig"
    :actions="customActions"
    @action="handleCustomAction"
  />
</template>

<script setup>
const customActions = [
  {
    type: 'save',
    action: 'save',
    label: 'Save Draft',
    modifiers: 'btn btn-success'
  },
  {
    type: 'cancel',
    action: 'cancel',
    label: '<i class="fa fa-times"></i> Cancel',
    modifiers: 'btn btn-danger'
  }
];

const handleCustomAction = (action) => {
  console.log('Custom action:', action);
  // Handle custom action logic
};
</script>
```

### Disable Default Actions

```vue
<template>
  <FormViewer
    :form-config="formConfig"
    :show-default-actions="false"
    :show-actions="false"
  />
</template>
```

## Toastr Integration

The FormViewer automatically shows toastr messages for form submission:

- **Success**: "Form submitted successfully!" (when toastr is available)
- **Error**: "Error submitting form: [error message]" (when errors occur)

### Supported Toastr Libraries

- `window.toastr` - Standard toastr library
- `window.showToast` - Custom toast function

## Styling

Actions use Tailwind CSS classes by default. You can customize the appearance by providing your own `modifiers` in the action configuration.

### Default Button Styles

- **Submit/Save**: Blue background with white text
- **Cancel**: Gray border with dark text
- **Custom**: Use your own CSS classes in `modifiers`

## Migration from Legacy Props

If you're using the legacy `showSubmitButton`, `showResetButton`, and `submitButtonText` props, you can continue using them, but the new actions system provides more flexibility.

### Legacy to New Actions

```javascript
// Old way
{
  showSubmitButton: true,
  showResetButton: true,
  submitButtonText: 'Save'
}

// New way
{
  actions: [
    {
      type: 'save',
      action: 'save',
      label: 'Save',
      modifiers: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors'
    },
    {
      type: 'cancel',
      action: 'cancel',
      label: 'Cancel',
      modifiers: 'px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-colors'
    }
  ]
}
``` 