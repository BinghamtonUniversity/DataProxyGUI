# Field Components - Shared Functions

This directory contains Vue components for form fields and shared utility functions.

## Shared Functions (`functions.js`)

The `functions.js` file contains common utility functions that can be used across all field components to reduce code duplication and maintain consistency.

### Available Functions

#### `getFieldLayoutClasses(field)`
Generates CSS classes for grid layout positioning based on field configuration.

```javascript
import { getFieldLayoutClasses } from './functions.js';

// Usage
const classes = getFieldLayoutClasses({
  columns: 4,
  offset: 2,
  forceRow: false
});
// Returns: "col-span-4 col-start-3"
```

#### `generateFieldId(prefix)`
Generates a unique field ID with optional prefix.

```javascript
import { generateFieldId } from './functions.js';

// Usage
const fieldId = generateFieldId('text'); // Returns: "text_abc123def"
```

#### `createEventHandlers(props, emit, state, fieldType)`
Creates standardized event handlers for form fields with built-in validation.

```javascript
import { createEventHandlers } from './functions.js';

// Usage
const { handleChange, handleBlur, handleFocus, handleInput } = createEventHandlers(
  props, 
  emit, 
  { internalValue, localError }, 
  'text'
);
```

#### `createValidationHandler(props, emit, value, fieldType)`
Creates a standardized validation handler with error emission.

```javascript
import { createValidationHandler } from './functions.js';

// Usage
const validation = createValidationHandler(props, emit, value, 'text');
if (!validation.isValid) {
  // Handle validation errors
}
```

#### `getFieldContainerClass(props, inFieldset)`
Generates CSS classes for field containers.

```javascript
import { getFieldContainerClass } from './functions.js';

// Usage
const containerClass = getFieldContainerClass(props, props.inFieldset);
```

#### `formatCurrency(amount, currency, locale)`
Formats currency values with proper localization.

```javascript
import { formatCurrency } from './functions.js';

// Usage
const formatted = formatCurrency(1234.56, 'USD', 'en-US'); // Returns: "$1,234.56"
```

#### `formatDate(date, format)`
Formats date values with custom format strings.

```javascript
import { formatDate } from './functions.js';

// Usage
const formatted = formatDate(new Date(), 'YYYY-MM-DD'); // Returns: "2024-01-15"
```

#### `getSafeFieldValue(field, formData)`
Safely retrieves field values with proper defaults based on field type.

```javascript
import { getSafeFieldValue } from './functions.js';

// Usage
const value = getSafeFieldValue(field, formData);
```

#### `shouldShowField(field, formData)` and `shouldEditField(field, formData)`
Check if fields should be shown or editable based on conditional logic.

```javascript
import { shouldShowField, shouldEditField } from './functions.js';

// Usage
const shouldShow = shouldShowField(field, formData);
const shouldEdit = shouldEditField(field, formData);
```

#### `getCheckboxLabel(value, options)`
Gets appropriate label for checkbox based on value and options.

```javascript
import { getCheckboxLabel } from './functions.js';

// Usage
const label = getCheckboxLabel(true, [
  { label: 'No' },
  { label: 'Yes' }
]); // Returns: "Yes"
```

#### `debounce(func, wait)` and `throttle(func, limit)`
Performance optimization utilities.

```javascript
import { debounce, throttle } from './functions.js';

// Usage
const debouncedSearch = debounce(searchFunction, 300);
const throttledScroll = throttle(scrollHandler, 100);
```

## Migration Guide

### Before (Old Pattern)
```javascript
// In each field component
const fieldId = `field_${Math.random().toString(36).substr(2, 9)}`;

const handleChange = (event) => {
  const value = event.target.value;
  internalValue.value = value;
  emit('update:value', value);
  emit('change', event);
  
  // Manual validation
  const errors = validateField(value, props, props.matchValues);
  if (errors.length > 0) {
    localError.value = errors[0];
    emit('validation-error', { field: props.name, errors, value });
  } else {
    localError.value = '';
    emit('validation-success', { field: props.name, value });
  }
};
```

### After (New Pattern)
```javascript
// Import shared functions
import { 
  generateFieldId, 
  createEventHandlers 
} from './functions.js';

// Use shared functions
const fieldId = generateFieldId('text');

const { handleChange, handleBlur, handleFocus, handleInput } = createEventHandlers(
  props, 
  emit, 
  { internalValue, localError }, 
  'text'
);
```

## Benefits

1. **Reduced Code Duplication**: Common patterns are centralized
2. **Consistency**: All fields behave the same way
3. **Maintainability**: Changes to common logic only need to be made in one place
4. **Type Safety**: Better TypeScript support with shared interfaces
5. **Performance**: Optimized functions with debouncing/throttling
6. **Testing**: Easier to test shared functions independently

## Field Components

Each field component should:
1. Import needed functions from `functions.js`
2. Use `createEventHandlers` for consistent event handling
3. Use `generateFieldId` for unique IDs
4. Use `getFieldContainerClass` for consistent styling
5. Use appropriate formatting functions for their data type

This approach ensures all field components follow the same patterns and are easier to maintain. 