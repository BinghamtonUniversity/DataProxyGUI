/**
 * Common utility functions for field components
 * This file centralizes shared functionality to reduce code duplication
 */

import { validateField } from './validation.js';

/**
 * Normalize multiple flag from boolean or string config values.
 * Avoids treating "false" as true (Vue Boolean casting / JS truthiness).
 * @param {boolean|string|undefined|null} value
 * @returns {boolean}
 */
export const isMultipleFlag = (value) => value === true || value === 'true';

/**
 * Get the appropriate Vue component for a field type
 * @param {string} fieldType - The type of field
 * @param {Object} field - The field configuration object (optional)
 * @returns {Object|null} - The Vue component or null if not found
 */
export const getFieldComponent = (fieldType, field = null) => {
  // Check if fieldset has array attribute
  if (fieldType === 'fieldset' && field && field.array) {
    // Return null here - the importing component should handle ArrayField directly
    return null;
  }
  
  // Return null - components should import their dependencies directly
  // This function is mainly for reference and documentation
  return null;
};

/**
 * Get field layout classes for grid positioning
 * @param {Object} field - The field configuration object
 * @returns {string} - CSS classes for layout
 */
export const getFieldLayoutClasses = (field) => {
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

/**
 * Generate a unique field ID
 * @param {string} prefix - Prefix for the ID (default: 'field')
 * @returns {string} - Unique field ID
 */
export const generateFieldId = (prefix = 'field') => {
  return `${prefix}_${Math.random().toString(36).substr(2, 9)}`;
};

/**
 * Create a standardized validation handler
 * @param {Object} props - Component props
 * @param {Function} emit - Component emit function
 * @param {any} value - Value to validate
 * @param {string} fieldType - Type of field for validation
 * @returns {Object} - Validation result with errors and success status
 */
export const createValidationHandler = (props, emit, value, fieldType = null) => {
  const validationProps = fieldType ? { ...props, type: fieldType } : props;
  const errors = validateField(value, validationProps, props.matchValues);
  
  if (errors.length > 0) {
    emit('validation-error', { 
      field: props.name, 
      errors, 
      value 
    });
    return { errors, isValid: false };
  } else {
    emit('validation-success', { 
      field: props.name, 
      value 
    });
    return { errors: [], isValid: true };
  }
};

/**
 * Create standardized event handlers for form fields
 * @param {Object} props - Component props
 * @param {Function} emit - Component emit function
 * @param {Object} state - Reactive state object with internalValue and localError
 * @param {string} fieldType - Type of field for validation
 * @returns {Object} - Object containing event handlers
 */
export const createEventHandlers = (props, emit, state, fieldType = null) => {
  const { internalValue, localError } = state;
  
  const handleChange = (event) => {
    const value = event?.target?.value ?? event;
    internalValue.value = value;
    emit('update:value', value);
    emit('change', event);
    
    // Validate on change
    const validation = createValidationHandler(props, emit, value, fieldType);
    localError.value = validation.errors[0] || '';
  };
  
  const handleBlur = (event) => {
    const value = event?.target?.value ?? internalValue.value;
    emit('blur', event);
    
    // Validate on blur
    const validation = createValidationHandler(props, emit, value, fieldType);
    localError.value = validation.errors[0] || '';
  };
  
  const handleFocus = (event) => {
    emit('focus', event);
  };
  
  const handleInput = (event) => {
    const value = event?.target?.value ?? event;
    internalValue.value = value;
    emit('update:value', value);
    emit('input', event);
  };
  
  return {
    handleChange,
    handleBlur,
    handleFocus,
    handleInput
  };
};

/**
 * Create a standardized field container class
 * @param {Object} props - Component props
 * @param {boolean} inFieldset - Whether the field is inside a fieldset
 * @returns {string} - CSS classes for the field container
 */
export const getFieldContainerClass = (props, inFieldset = false) => {
  const baseClass = `${props.type || 'field'}-field-container`;
  return inFieldset ? baseClass : `${baseClass} w-full`;
};

/**
 * Format currency value
 * @param {number} amount - Amount to format
 * @param {string} currency - Currency code (default: 'USD')
 * @param {string} locale - Locale for formatting (default: 'en-US')
 * @returns {string} - Formatted currency string
 */
export const formatCurrency = (amount, currency = 'USD', locale = 'en-US') => {
  if (amount === null || amount === undefined || amount === '') {
    return '';
  }
  
  try {
    return new Intl.NumberFormat(locale, {
      style: 'currency',
      currency: currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount);
  } catch (error) {
    // Fallback formatting
    return `$${Number(amount).toFixed(2)}`;
  }
};

/**
 * Format date value
 * @param {string|Date} date - Date to format
 * @param {string} format - Format string (default: 'YYYY-MM-DD')
 * @returns {string} - Formatted date string
 */
export const formatDate = (date, format = 'YYYY-MM-DD') => {
  if (!date) return '';
  
  const d = new Date(date);
  if (isNaN(d.getTime())) return '';
  
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  
  return format
    .replace('YYYY', year)
    .replace('MM', month)
    .replace('DD', day);
};

/**
 * Parse date string to Date object
 * @param {string} dateString - Date string to parse
 * @returns {Date|null} - Parsed Date object or null if invalid
 */
export const parseDate = (dateString) => {
  if (!dateString) return null;
  
  const date = new Date(dateString);
  return isNaN(date.getTime()) ? null : date;
};

/**
 * Get safe field value with proper defaults
 * @param {Object} field - Field configuration
 * @param {Object} formData - Form data object
 * @returns {any} - Safe field value
 */
export const getSafeFieldValue = (field, formData) => {
  if (!field || !field.name) return '';
  
  const fieldValue = formData[field.name];
  
  // Handle fieldsets: must be an object
  if (field.type === 'fieldset') {
    const value = formData[field.name] || field.defaultValue || {};
    return (typeof value === 'object' && value !== null) ? value : {};
  }
  
  // Handle booleans
  if (field.type === 'checkbox' || field.type === 'switch') {
    return typeof fieldValue === 'boolean' ? fieldValue : false;
  }
  
  // Handle arrays/multiple-selection
  if (field.array || isMultipleFlag(field.multiple)) {
    return Array.isArray(fieldValue) ? fieldValue : [];
  }
  
  // Handle all other types (text, number, etc.)
  // Use nullish coalescing operator (??) to allow "0" or "false" as valid values
  return fieldValue ?? field.defaultValue ?? '';
};

/**
 * Check if a field should be shown based on conditional logic
 * @param {Object} field - Field configuration
 * @param {Object} formData - Form data object
 * @returns {boolean} - Whether the field should be shown
 */
export const shouldShowField = (field, formData) => {
  if (!field.show) return true;
  
  if (typeof field.show === 'boolean') {
    return field.show;
  }
  
  if (typeof field.show === 'string') {
    // Reference to another field's show property
    const referencedField = Object.values(formData).find(f => f.name === field.show);
    return referencedField ? shouldShowField(referencedField, formData) : true;
  }
  
  // Complex condition object - would need conditionalLogic.js
  return true;
};

/**
 * Check if a field should be editable based on conditional logic
 * @param {Object} field - Field configuration
 * @param {Object} formData - Form data object
 * @returns {boolean} - Whether the field should be editable
 */
export const shouldEditField = (field, formData) => {
  if (!field.edit) return true;
  
  if (typeof field.edit === 'boolean') {
    return field.edit;
  }
  
  if (typeof field.edit === 'string') {
    // Reference to another field's edit property
    const referencedField = Object.values(formData).find(f => f.name === field.edit);
    return referencedField ? shouldEditField(referencedField, formData) : true;
  }
  
  // Complex condition object - would need conditionalLogic.js
  return true;
};

/**
 * Get checkbox label based on options
 * @param {boolean} value - Current checkbox value
 * @param {Array} options - Checkbox options array
 * @returns {string} - Label text
 */
export const getCheckboxLabel = (value, options = []) => {
  if (options && options.length >= 2) {
    // Use custom labels from options
    return value ? options[1].label : options[0].label;
  }
  // Default labels
  return value ? 'Yes' : 'No';
};

/**
 * Debounce function for performance optimization
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} - Debounced function
 */
export const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

/**
 * Throttle function for performance optimization
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} - Throttled function
 */
export const throttle = (func, limit) => {
  let inThrottle;
  return function() {
    const args = arguments;
    const context = this;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => inThrottle = false, limit);
    }
  };
}; 