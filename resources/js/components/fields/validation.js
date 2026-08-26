// validation.js
// Dynamic, extensible validation module for form fields
// Usage:
//   import { validateField } from './validation.js';
//   const errors = validateField(value, config, matchValues);
//   - value: the field value
//   - config: field config (should include validate array if custom rules)
//   - matchValues: (optional) object of other field values for 'matches' rule

// Helper function to determine input type from format
function getInputTypeFromFormat(format) {
  if (!format || !format.input) return 'date';
  
  const formatStr = format.input;
  
  // Map specific accepted formats to input types
  switch (formatStr) {
    case 'h:mm A':
      return 'time';
    case 'MM':
    case 'YYYY':
      return 'number';
    case 'MM/YYYY':
      return 'month';
    case 'MM/DD':
    case 'MM/DD/YYYY':
    case 'YYYY-MM-DD':
    case 'MM/DD/YYYY h:mm A':
    default:
      return 'date';
  }
}

// Helper function to extract valid values from options (including optgroups)
function getValidValuesFromOptions(options) {
  if (!options || !Array.isArray(options)) return [];
  
  const validValues = [];
  
  for (const option of options) {
    if (option.type === 'optgroup' && option.options) {
      // Handle optgroup format with existing options
      for (const subOption of option.options) {
        if (typeof subOption === 'string' || typeof subOption === 'boolean' || typeof subOption === 'number') {
          validValues.push(subOption);
        } else if (subOption && subOption.value !== undefined) {
          validValues.push(subOption.value);
        }
      }
    } else if (option.type === 'optgroup' && (option.min !== undefined || option.max !== undefined)) {
      // Handle optgroup with min/max - generate numeric options
      const min = option.min || 0;
      const max = option.max || 10;
      for (let i = min; i <= max; i++) {
        validValues.push(i.toString());
      }
    } else if (typeof option === 'string' || typeof option === 'boolean' || typeof option === 'number') {
      // Handle string options
      validValues.push(option);
    } else if (option && option.value !== undefined) {
      // Handle object options
      validValues.push(option.value);
    }
  }
  
  return validValues;
}

// Built-in validation rules
const rules = {
  required: (value, config) => {

    if (config &&  config.required && (value === undefined || value === null || value === '')) {
      return 'This field is required.';
    }
    return null;
  },
  minLength: (value, config) => {
    if (config && config.minLength && value && value.length < config.minLength) {
      return `Minimum length is ${config.minLength}.`;
    }
    return null;
  },
  maxLength: (value, config) => {
    if (config && config.maxLength && value && value.length > config.maxLength) {
      return `Maximum length is ${config.maxLength}.`;
    }
    return null;
  },
  pattern: (value, config) => {
    if (config && config.pattern && value && !(new RegExp(config.pattern).test(value))) {
      return 'Invalid format.';
    }
    return null;
  },
  email: (value, config) => {
    if (config && config.type === 'email') {
      if (config.required && (!value || value.trim() === '')) {
        return 'Email address is required.';
      }
      if (value && value.trim() !== '' && !/^\S+@\S+\.\S+$/.test(value)) {
        return 'Invalid email address.';
      }
    }
    return null;
  },
  phone: (value, config) => {
    if (config && config.type === 'tel') {
      if (config.required && (!value || value.trim() === '')) {
        return 'Phone number is required.';
      }
      if (value && value.trim() !== '' && !/^[\+]?[1-9][\d]{0,15}$/.test(value.replace(/[\s\-\(\)]/g, ''))) {
        return 'Invalid phone number.';
      }
    }
    return null;
  },
  url: (value, config) => {
    if (config && config.type === 'url') {
      if (config.required && (!value || value.trim() === '')) {
        return 'URL is required.';
      }
      if (value && value.trim() !== '') {
        try {
          new URL(value);
        } catch {
          return 'Invalid URL.';
        }
      }
    }
    return null;
  },
  number: (value, config) => {
    if (config && config.type === 'number') {
      if (config.required && (value === undefined || value === null || value === '')) {
        return 'Number is required.';
      }
      if (value !== undefined && value !== null && value !== '') {
        const numValue = Number(value);
        if (isNaN(numValue)) {
          return 'Invalid number.';
        }
        
        // Check min value
        if (config.min !== null && config.min !== undefined && numValue < config.min) {
          return `Number must be at least ${config.min}.`;
        }
        
        // Check max value
        if (config.max !== null && config.max !== undefined && numValue > config.max) {
          return `Number must be at most ${config.max}.`;
        }
      }
    }
    return null;
  },
  currency: (value, config) => {
    if (config && config.type === 'currency') {
      if (config.required && (value === undefined || value === null || value === '')) {
        return 'Amount is required.';
      }
      if (value !== undefined && value !== null && value !== '') {
        const numValue = Number(value);
        if (isNaN(numValue)) {
          return 'Invalid amount.';
        }
        
        // Check if negative (unless allowed)
        if (numValue < 0 && config.min !== null && config.min !== undefined && config.min >= 0) {
          return 'Amount cannot be negative.';
        }
        
        // Check min value
        if (config.min !== null && config.min !== undefined && numValue < config.min) {
          return `Amount must be at least ${config.min}.`;
        }
        
        // Check max value
        if (config.max !== null && config.max !== undefined && numValue > config.max) {
          return `Amount must be at most ${config.max}.`;
        }
        
        // Check decimal places (currency should have max 2 decimal places)
        const decimalPlaces = (numValue.toString().split('.')[1] || '').length;
        if (decimalPlaces > 2) {
          return 'Amount cannot have more than 2 decimal places.';
        }
      }
    }
    return null;
  },
  color: (value, config) => {
    if (config && config.type === 'color') {
      if (config.required && (value === undefined || value === null || value === '')) {
        return 'Color is required.';
      }
      if (value !== undefined && value !== null && value !== '') {
        // Validate hex color format (#RRGGBB)
        if (!/^#[0-9A-Fa-f]{6}$/.test(value)) {
          return 'Invalid color format. Use #RRGGBB format (e.g., #ff0000).';
        }
      }
    }
    return null;
  },
  hidden: (value, config) => {
    if (config && config.type === 'hidden') {
      if (config.required && (value === undefined || value === null || value === '')) {
        return 'Hidden field is required.';
      }
      // Hidden fields can contain any value type, so no format validation needed
      // Custom validation rules can be applied through the validate array
    }
    return null;
  },
  select: (value, config) => {
    if (config && config.type === 'select') {
      const isMultiple = config.multiple === true || config.multiple === 'true';

      if (config.required) {
        if (isMultiple) {
          // For multiple selection, check if array is empty
          if (!Array.isArray(value) || value.length === 0) {
            return 'Please select at least one option.';
          }
        } else {
          // For single selection, check if value is empty
          if (value === undefined || value === null || value === '') {
            return 'Please select an option.';
          }
        }
      }
      
      // Validate that selected values exist in options (including optgroups)
      if (value !== undefined && value !== null && value !== '' && config.options && config.options.length > 0) {
        // Extract all valid values from options and optgroups
        const validValues = [];
        
        for (const option of config.options) {
          if (option.type === 'optgroup' && option.options) {
            // Handle optgroup format with existing options
            for (const subOption of option.options) {
              if (typeof subOption === 'string' || typeof subOption === 'boolean' || typeof subOption === 'number') {
                validValues.push(subOption.toString());
              } else if (subOption && subOption.value !== undefined) {
                validValues.push(subOption.value.toString());
              }
            }
          } else if (option.type === 'optgroup' && (option.min !== undefined || option.max !== undefined)) {
            // Handle optgroup with min/max - generate numeric options
            const min = option.min || 0;
            const max = option.max || 10;
            for (let i = min; i <= max; i++) {
              validValues.push(i.toString());
            }
          } else if (typeof option === 'string' || typeof option === 'boolean'  || typeof option === 'number') {
            // Handle string options
            validValues.push(option.toString());
          } else if (option && option.value !== undefined) {
            // Handle object options
            validValues.push(option.value.toString());
          }
        }
        // Validate selected values exist in options
        if (isMultiple && Array.isArray(value)) {
          for (const selectedValue of value) {
            if (!validValues.includes(selectedValue.toString())) {
              return 'Invalid option value selected.';
            }
          }
        }
        else if (!isMultiple && !validValues.includes(value.toString())) {
          return 'Invalid option selected.';
        }
      }
    }
    return null;
  },
  date: (value, config) => {
    if (config && config.type === 'date') {
      if (config.required && (!value || value.trim() === '')) {
        return 'Date is required.';
      }
      if (value && value.trim() !== '') {
        // Handle different input types based on format
        const inputType = getInputTypeFromFormat(config.format);
        
        if (inputType === 'time') {
          // Validate time format for h:mm A
          if (!/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value)) {
            return 'Invalid time format. Use HH:MM format.';
          }
        } else if (inputType === 'month') {
          // Validate month format for MM/YYYY (YYYY-MM in HTML)
          if (!/^\d{4}-\d{2}$/.test(value)) {
            return 'Invalid month format. Use YYYY-MM format.';
          }
        } else if (inputType === 'number') {
          // Validate number format for MM or YYYY
          const num = parseInt(value);
          if (isNaN(num)) {
            return 'Invalid number format.';
          }
          
          if (config.format?.input === 'MM') {
            // Month validation (1-12)
            if (num < 1 || num > 12) {
              return 'Month must be between 1 and 12.';
            }
          } else if (config.format?.input === 'YYYY') {
            // Year validation (1900-2100)
            if (num < 1900 || num > 2100) {
              return 'Year must be between 1900 and 2100.';
            }
          }
        } else {
          // Standard date validation
          const date = new Date(value);
          if (isNaN(date.getTime())) {
            return 'Invalid date.';
          }
          // Check min date if specified
          if (config.minDate && date < new Date(config.minDate)) {
            return `Date must be on or after ${new Date(config.minDate).toLocaleDateString()}.`;
          }
          // Check max date if specified
          if (config.maxDate && date > new Date(config.maxDate)) {
            return `Date must be on or before ${new Date(config.maxDate).toLocaleDateString()}.`;
          }
        }
      }
    }
    return null;
  },
  combobox: (value, config) => {
    if (config && config.type === 'combobox') {
      const isMultiple = config.multiple === true || config.multiple === 'true';

      if (config.required) {
        if (isMultiple) {
          if (!Array.isArray(value) || value.length === 0) {
            return 'Please select at least one option.';
          }
        } else if (value === undefined || value === null || value === '') {
          return 'This field is required.';
        }
      }
      
      // If custom values are not allowed, check if value is in options
      if (!config.allowCustom && value !== undefined && value !== null && value !== '') {
        const validValues = getValidValuesFromOptions(config.options);
        if (validValues.length > 0) {
          if (isMultiple && Array.isArray(value)) {
            for (const selectedValue of value) {
              if (!validValues.includes(selectedValue) && !validValues.includes(String(selectedValue))) {
                return 'Please select a valid option from the list.';
              }
            }
          } else if (!isMultiple && !validValues.includes(value) && !validValues.includes(String(value))) {
            return 'Please select a valid option from the list.';
          }
        }
      }
    }
    return null;
  },
  custom: (value, config) => {
    if (config && typeof config.customValidation === 'function') {
      return config.customValidation(value, config);
    }
    return null;
  },
  range: (value, config) => {
    if (config && config.type === 'range') {
      if (config.required && (value === undefined || value === null || value === '')) {
        return 'This field is required.';
      }
      if (value !== undefined && value !== null && value !== '') {
        const numValue = Number(value);
        if (isNaN(numValue)) {
          return 'Invalid number.';
        }
        if (config.min !== null && config.min !== undefined && numValue < config.min) {
          return `Value must be at least ${config.min}.`;
        }
        if (config.max !== null && config.max !== undefined && numValue > config.max) {
          return `Value must be at most ${config.max}.`;
        }
        if (config.step !== null && config.step !== undefined && ((numValue - (config.min || 0)) % config.step !== 0)) {
          return `Value must be a multiple of ${config.step} from ${config.min || 0}.`;
        }
      }
    }
    return null;
  },
  checkbox: (value, config) => {
    if (config && config.type === 'checkbox') {

      if (config.required && (value === undefined || value === null || value  == false ) ) {
        return 'This checkbox is required.';
      }
    }
    return null;
  },
  switch: (value, config) => {
    if (config && config.type === 'switch') {
      if (config.required && (value === undefined || value === null ) && value !== config.options?.[1]?.value) {
        return 'This switch is required.';
      }
    }
    return null;
  }
};

// Custom validation rules from config.validate
function runCustomValidations(value, config, matchValues = {}) {
  const errors = [];
  if (config && Array.isArray(config.validate)) {
    for (const rule of config.validate) {
      if (!rule.conditions) continue;
      switch (rule.type) {
        case 'date':
          if (value && isNaN(Date.parse(value))) {
            errors.push(rule.message || 'Invalid date');
          }
          break;
        case 'valid_url':
          if (value && value.trim() !== '') {
            try { 
              new URL(value); 
            } catch { 
              errors.push(rule.message || 'Invalid URL'); 
            }
          }
          break;
        case 'valid_email':
          if (value && value.trim() !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors.push(rule.message || 'Invalid email');
          }
          break;
        case 'length':
          if (typeof rule.min === 'number' && value.length < rule.min) {
            errors.push(rule.message || `Minimum length is ${rule.min}`);
          }
          if (typeof rule.max === 'number' && value.length > rule.max) {
            errors.push(rule.message || `Maximum length is ${rule.max}`);
          }
          break;
        case 'numeric':
          if (value && isNaN(Number(value))) {
            errors.push(rule.message || 'Must be numeric');
          }
          break;
        case 'pattern':
          try {
            const regex = new RegExp(rule.regex, rule.flags || '');
            if (value && !regex.test(value)) {
              errors.push(rule.message || 'Invalid format');
            }
          } catch {
            errors.push('Invalid pattern');
          }
          break;
        case 'matches':
          if (matchValues && value !== matchValues[rule.name]) {
            errors.push(rule.message || `Must match ${rule.name}`);
          }
          break;
        default:
          break;
      }
    }
  }
  return errors;
}

// Check if a fieldset item is empty (all fields are empty/undefined)
function isFieldsetEmpty(value, fields) {
  if (!value || typeof value !== 'object') return true;
  if (!fields || !Array.isArray(fields)) return false;
  
  // Check if all fields are empty
  return fields.every(field => {
    if (!field || !field.name) return true;
    const fieldValue = value[field.name];
    // Consider field empty if undefined, null, empty string, or empty array
    return fieldValue === undefined || 
           fieldValue === null || 
           fieldValue === '' || 
           (Array.isArray(fieldValue) && fieldValue.length === 0);
  });
}

// Recursively validate nested fields in a fieldset
function validateFieldset(value, config, matchValues = {}) {
  const errors = [];
  
  // If fieldset is required but value is empty
  if (config.required) {
    if (!value || (typeof value === 'object' && Object.keys(value).length === 0)) {
      errors.push('This section is required.');
      return errors; // Return early if required fieldset is empty
    }
  }
  
  // Validate nested fields if fieldset has fields array
  if (config.fields && Array.isArray(config.fields) && value && typeof value === 'object') {
    config.fields.forEach(field => {
      if (field && field.name) {
        const fieldValue = value[field.name];
        const fieldConfig = {
          type: field.type || 'text',
          minLength: field.minLength,
          maxLength: field.maxLength,
          pattern: field.pattern,
          min: field.min,
          max: field.max,
          ...field,
          // Explicitly set required after spread to ensure correct value
          // Convert string "false" to boolean false, undefined to false
          required: (() => {
            if (field.required === undefined) return false;
            if (typeof field.required === 'boolean') return field.required;
            if (field.required === 'true' || field.required === true) return true;
            if (field.required === 'false' || field.required === false) return false;
            // For conditional logic (string 'conditional' or arrays), pass through as-is
            return field.required;
          })()
        };
        
        // Validate the nested field
        const fieldErrors = validateField(fieldValue, fieldConfig, value);
        if (fieldErrors.length > 0) {
          // Prefix error with field name for clarity
          fieldErrors.forEach(error => {
            errors.push(`${field.label || field.name}: ${error}`);
          });
        }
      }
    });
  }
  
  return errors;
}

// Validate array fields (arrays of simple values or arrays of fieldsets)
function validateArray(value, config, matchValues = {}) {

  
  const errors = [];
  
  // If value is not an array, check if it's required
  if (!Array.isArray(value)) {
    if (config.required && (value === undefined || value === null || value === '')) {
      errors.push('This field is required.');
    }
    // If not required or value is not empty, return no errors (it's optional)
    return errors;
  }
  
  // Check min/max array length
  const arrayConfig = config.array || {};
  const minItems = arrayConfig.min;
  const maxItems = arrayConfig.max;
  
  // Check array length constraints
  if (maxItems !== undefined && value.length > maxItems) {
    errors.push(`Maximum ${maxItems} item(s) allowed.`);
  }
  
  // If array is required and empty
  if (config.required && value.length === 0) {
    errors.push('At least one item is required.');
    return errors; // Return early if required array is empty
  }
  
  // Validate each item in the array
  // Track if we have at least one non-empty item (for minItems requirement)
  let hasNonEmptyItem = false;
  let nonEmptyItemCount = 0;
  
  value.forEach((item, index) => {
    if (config.fields && Array.isArray(config.fields)) {
      // Array of fieldsets - check if item is empty first
      const isEmpty = isFieldsetEmpty(item, config.fields);
      
      if (!isEmpty) {
        hasNonEmptyItem = true;
        nonEmptyItemCount++;
        // Only validate non-empty items
        // Don't pass the array's required flag to individual fieldset items
        const fieldsetConfig = { 
          ...config, 
          fields: config.fields,
          required: false, // Individual items in an array are not required (the array itself is)
          type: 'fieldset'
        };
        const fieldsetErrors = validateFieldset(item, fieldsetConfig, matchValues);
        if (fieldsetErrors.length > 0) {
          fieldsetErrors.forEach(error => {
            errors.push(`Item ${index + 1}: ${error}`);
          });
        }
      }
      // Skip validation for empty items - they're allowed as placeholders
    } else {
      // Array of simple values - check if item has data first
      const isEmpty = item === undefined || item === null || item === '' || 
                      (Array.isArray(item) && item.length === 0);
      
      if (!isEmpty) {
        hasNonEmptyItem = true;
        nonEmptyItemCount++;
      }
      
      // Validate each item (even empty ones for format validation)
      // Don't pass the array's required flag to individual items
      const itemConfig = {
        type: config.type || 'text',
        required: false, // Individual items in an array are not required (the array itself is)
        minLength: config.minLength,
        maxLength: config.maxLength,
        pattern: config.pattern,
        min: config.min,
        max: config.max,
        ...config
      };
      // Remove the array-specific config from item validation
      delete itemConfig.array;
      delete itemConfig.required; // Already set to false above
      const itemErrors = validateField(item, itemConfig, matchValues);
      if (itemErrors.length > 0) {
        itemErrors.forEach(error => {
          errors.push(`Item ${index + 1}: ${error}`);
        });
      }
    }
  });
  
  // Check minItems requirement - need at least minItems non-empty items
  if (minItems !== undefined && minItems > 0) {
    if (nonEmptyItemCount < minItems) {
      if (nonEmptyItemCount === 0 && value.length > 0) {
        // We have items but they're all empty
        errors.push(`At least ${minItems} item(s) with data are required.`);
      } else {
        // We don't have enough non-empty items
        errors.push(`At least ${minItems} item(s) with data are required.`);
      }
    }
  }
  return errors;
}

// Main validation function
export function validateField(value, config, matchValues = {}) {
  const errors = [];
  
  // Handle array validation FIRST (for fieldsets with array attribute or regular arrays)
  // A fieldset with array attribute should be treated as an array, not a fieldset
  if (config.array || (config.type === 'fieldset' && config.array)) {
    const arrayErrors = validateArray(value, config, matchValues);
    errors.push(...arrayErrors);
    // Return early for arrays - don't run other rules
    return errors;
  }
  
  // Handle regular fieldset validation (fieldset without array attribute)
  if (config.type === 'fieldset') {
    const fieldsetErrors = validateFieldset(value, config, matchValues);
    errors.push(...fieldsetErrors);
    // Don't run other rules for fieldsets, as we've handled it above
    return errors;
  }
  
  // Handle array validation for non-fieldset types
  if (Array.isArray(value) && config.type && config.type !== 'fieldset') {
    const arrayErrors = validateArray(value, config, matchValues);
    errors.push(...arrayErrors);
    // Continue with other validations for array items if needed
  }
  
  // Built-in rules
  for (const ruleName in rules) {
    const error = rules[ruleName](value, config);
    if (error) errors.push(error);
  }
  
  // Custom rules
  errors.push(...runCustomValidations(value, config, matchValues));

  return errors;
} 