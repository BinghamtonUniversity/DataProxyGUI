<template>
  <div class="output-field-container">
    <!-- Field Label (if provided) -->
    <div v-if="field && field.label" class="mb-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ field.label }}
      </label>
    </div>

    <!-- Output Content -->
    <div 
      class="output-content"
      :class="getOutputClasses()"
      v-html="formattedContent"
    ></div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';

const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  value: {
    type: [String, Number, Boolean],
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  edit:  { type: [Boolean,String,Array], default: true },
  show:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  required:  { type: [Boolean,String,Array], default: true },

});

const emit = defineEmits(['update:value', 'validation-error', 'validation-success']);

// Get the formatted content based on field configuration
const formattedContent = computed(() => {
  if (!props.field) {
    return props.value || '';
  }

  const format = props.field.format;
  
  if (!format) {
    // Check if help property contains HTML content
    const help = props.field.help;
    if (help) {
      return help;
    }
    return props.value || '';
  }

  // If format.value is provided, use it
  if (format.value) {
    return format.value;
  }

  // If format is a function or template, evaluate it
  if (typeof format === 'function') {
    return format(props.value);
  }

  // If format is a template string, replace placeholders
  if (typeof format === 'string') {
    return format.replace(/\{value\}/g, props.value || '');
  }

  return props.value || '';
});

// Get CSS classes based on field configuration
const getOutputClasses = () => {
  const classes = [];
  
  if (!props.field) {
    return classes.join(' ');
  }
  
  // Show column styling
  if (props.field.showColumn) {
    classes.push('col-span-full');
  }

  return classes.join(' ');
};

// Emit validation success when component is mounted
onMounted(() => {
  if (props.field && props.field.name) {
    emit('validation-success', {
      field: props.field.name,
      value: props.value
    });
  }
});
</script>

<style scoped>
.output-field-container {
  width: 100%;
}

.output-content {
  line-height: 1.6;
}

.output-content :deep(h1) {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
  color: #111827;
}

.output-content :deep(h2) {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #111827;
}

.output-content :deep(h3) {
  font-size: 1.125rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #111827;
}

.output-content :deep(p) {
  margin-bottom: 0.75rem;
  color: #374151;
}

.output-content :deep(p:last-child) {
  margin-bottom: 0;
}

.output-content :deep(ul) {
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
  list-style-type: disc;
}

.output-content :deep(ol) {
  margin-bottom: 0.75rem;
  padding-left: 1.5rem;
  list-style-type: decimal;
}

.output-content :deep(li) {
  margin-bottom: 0.25rem;
  color: #374151;
}

.output-content :deep(strong) {
  font-weight: 600;
  color: #111827;
}

.output-content :deep(em) {
  font-style: italic;
}

.output-content :deep(code) {
  background-color: #f3f4f6;
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
  font-size: 0.875em;
}

.output-content :deep(pre) {
  background-color: #f3f4f6;
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin-bottom: 0.75rem;
}

.output-content :deep(blockquote) {
  border-left: 4px solid #d1d5db;
  padding-left: 1rem;
  margin-bottom: 0.75rem;
  font-style: italic;
  color: #6b7280;
}

/* Dark mode styles */
.dark .output-content :deep(h1),
.dark .output-content :deep(h2),
.dark .output-content :deep(h3) {
  color: #f9fafb;
}

.dark .output-content :deep(p),
.dark .output-content :deep(li) {
  color: #d1d5db;
}

.dark .output-content :deep(strong) {
  color: #f9fafb;
}

.dark .output-content :deep(code),
.dark .output-content :deep(pre) {
  background-color: #374151;
  color: #e5e7eb;
}

.dark .output-content :deep(blockquote) {
  border-left-color: #4b5563;
  color: #9ca3af;
}
</style>