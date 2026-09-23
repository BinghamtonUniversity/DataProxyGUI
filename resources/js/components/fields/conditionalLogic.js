/**
 * Conditional Logic Utility for Form Fields
 * Handles show, edit, and parse properties that can be:
 * - Boolean values (true/false)
 * - String references (e.g., "show": "edit")
 * - Condition objects with complex logic
 */

/**
 * Evaluate a single condition
 * @param {Object} condition - The condition object
 * @param {Object} formData - Current form data
 * @returns {boolean} - Result of the condition
 */
function evaluateCondition(condition, formData) {
  const { type, name, value } = condition;
  
  if (!name) {
    warning('No field name provided');
    return false;
  }
  
  // Handle case where formData is undefined or null
  if (!formData || typeof formData !== 'object') {
    warning('formData is not a valid object:', formData);
    return false;
  }
  
  const fieldValue = formData[name];
  if (fieldValue === undefined) {
    warning('Field not found in formData:', name, 'Available fields:', Object.keys(formData), 'formData:', formData);
    return false;
  }
  
  
  switch (type) {
    case 'equals':
      return fieldValue === value;
      
    case 'not_equals':
      return fieldValue !== value;
      
    case 'matches':
      if (Array.isArray(value)) {
        const result = value.includes(fieldValue);
        return result;
      }
      const result = fieldValue === value;
      return result;
      
    case 'not_matches':
      if (Array.isArray(value)) {
        const arrayResult = !value.includes(fieldValue);
        return arrayResult;
      }
      const stringResult = fieldValue !== value;
      return stringResult;
      
    case 'contains':
      if (typeof fieldValue === 'string' && typeof value === 'string') {
        return fieldValue.includes(value);
      }
      return false;
      
    case 'not_contains':
      if (typeof fieldValue === 'string' && typeof value === 'string') {
        return !fieldValue.includes(value);
      }
      return true;
      
    case 'greater_than':
      return Number(fieldValue) > Number(value);
      
    case 'less_than':
      return Number(fieldValue) < Number(value);
      
    case 'greater_than_or_equal':
      return Number(fieldValue) >= Number(value);
      
    case 'less_than_or_equal':
      return Number(fieldValue) <= Number(value);
      
    case 'is_empty':
      return !fieldValue || fieldValue === '' || (Array.isArray(fieldValue) && fieldValue.length === 0);
      
    case 'is_not_empty':
      return fieldValue && fieldValue !== '' && (!Array.isArray(fieldValue) || fieldValue.length > 0);
      
    case 'requires':
      return fieldValue !== null && fieldValue !== undefined && fieldValue !== '' && (!Array.isArray(fieldValue) || fieldValue.length > 0);
      
    default:
      return false;
  }
}

/**
 * Evaluate a group of conditions with logical operators
 * @param {Object} conditionGroup - The condition group object
 * @param {Object} formData - Current form data
 * @returns {boolean} - Result of the condition group
 */
function evaluateConditionGroup(conditionGroup, formData) {
  const { op, conditions } = conditionGroup;
  
  if (!conditions || !Array.isArray(conditions)) {
    return true;
  }
  
  // Handle case where formData is undefined or null
  if (!formData || typeof formData !== 'object') {
    warning('evaluateConditionGroup: formData is not valid:', formData);
    return false;
  }
  
  const results = conditions.map(condition => {
    if (condition.op) {
      // Nested condition group
      const nestedResult = evaluateConditionGroup(condition, formData);
      return nestedResult;
    } else {
      // Single condition
      const singleResult = evaluateCondition(condition, formData);
      return singleResult;
    }
  });
  
  switch (op) {
    case 'and':
      const andResult = results.every(result => result === true);
      return andResult;
      
    case 'or':
      const orResult = results.some(result => result === true);
      return orResult;
      
    case 'not':
      const notResult = results.length > 0 ? !results[0] : true;
      return notResult;
      
    default:
      const defaultResult = results.every(result => result === true);
      return defaultResult;
  }
}

/**
 * Resolve a property value that might be a reference or condition
 * @param {any} propertyValue - The property value (boolean, string, or condition object)
 * @param {Object} fieldConfig - The field configuration
 * @param {Object} formData - Current form data
 * @param {string} propertyName - Name of the property being resolved
 * @returns {boolean} - Resolved boolean value
 */
export function resolveProperty(propertyValue, fieldConfig, formData, propertyName = 'show') {
  // If property is undefined/null, return true (default behavior)
  if (propertyValue === undefined || propertyValue === null) {
    return true;
  }
  
  // If property is a boolean, return it directly
  if (typeof propertyValue === 'boolean') {
    return propertyValue;
  }
  
  // If property is a string, it's a reference to another property
  if (typeof propertyValue === 'string') {
    return resolveProperty(fieldConfig[propertyValue], fieldConfig, formData, propertyValue);
  }
  
  // If property is an array, it's a condition group
  if (Array.isArray(propertyValue)) {
    if (propertyValue.length === 0) {
      return true;
    }
    // Take the first condition group
    const conditionGroup = propertyValue[0];
    return evaluateConditionGroup(conditionGroup, formData);
  }
  
  // If property is an object, it's a condition group
  if (typeof propertyValue === 'object' && propertyValue !== null) {
    return evaluateConditionGroup(propertyValue, formData);
  }
  
  // Default to true
  return true;
}

/**
 * Resolve show, edit, and parse properties for a field
 * @param {Object} field - The field configuration
 * @param {Object} formData - Current form data
 * @returns {Object} - Object with resolved show, edit, and parse values
 */
export function resolveFieldProperties(field, formData) {
  return {
    show: resolveProperty(field.show, field, formData, 'show'),
    edit: resolveProperty(field.edit, field, formData, 'edit'),
    parse: resolveProperty(field.parse, field, formData, 'parse')
  };
}

/**
 * Check if a field should be visible based on its show property
 * @param {Object} field - The field configuration
 * @param {Object} formData - Current form data
 * @returns {boolean} - Whether the field should be shown
 */
export function shouldShowField(field, formData) {
  return resolveProperty(field.show, field, formData, 'show');
}

/**
 * Check if a field should be editable based on its edit property
 * @param {Object} field - The field configuration
 * @param {Object} formData - Current form data
 * @returns {boolean} - Whether the field should be editable
 */
export function shouldEditField(field, formData) {
  return resolveProperty(field.edit, field, formData, 'edit');
}

/**
 * Check if a field should be parsed based on its parse property
 * @param {Object} field - The field configuration
 * @param {Object} formData - Current form data
 * @returns {boolean} - Whether the field should be parsed
 */
export function shouldParseField(field, formData) {
  return resolveProperty(field.parse, field, formData, 'parse');
}