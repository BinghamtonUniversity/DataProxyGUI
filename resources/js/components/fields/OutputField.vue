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
import Mustache from 'mustache';

const props = defineProps({
  field: {
    type: Object,
    required: true
  },
  value: {
    type: [String, Number, Boolean, Object],
    default: ''
  },
  /**
   * Optional context object for template rendering.
   * Typically this is the full form data passed from FormViewer so that
   * templates like {{last_exec_start}} can access other fields.
   */
  context: {
    type: Object,
    default: () => ({})
  },
  disabled: {
    type: Boolean,
    default: false
  },
  edit:  { type: [Boolean,String,Array], default: true },
  show:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  required:  { type: [Boolean,String,Array], default: false },

});

const emit = defineEmits(['update:value', 'validation-error', 'validation-success']);

// Normalize value for display when it's an object (text/textarea/output)
function normalizeValueForDisplay(val, field) {
  if (val === undefined || val === null) return '';
  if (typeof val === 'object' && val !== null && !Array.isArray(val)) {
    if (field?.valueKey && typeof field.valueKey === 'string') {
      const v = val[field.valueKey];
      return v !== undefined && v !== null ? String(v) : '';
    }
    return JSON.stringify(val, null, 2);
  }
  return String(val);
}

// ---- Template helpers (aligned with DataGrid) ----
function formatRelativeTime(isoString) {
  if (isoString === undefined || isoString === null) return '—';
  const str = String(isoString).trim();
  if (!str) return '—';
  try {
    const date = new Date(str);
    if (Number.isNaN(date.getTime())) return str;
    const now = new Date();
    const sec = Math.floor((now - date) / 1000);
    if (sec < 0) return 'in the future';
    if (sec < 60) return 'just now';
    if (sec < 3600) return `${Math.floor(sec / 60)} minutes ago`;
    if (sec < 86400) return `${Math.floor(sec / 3600)} hours ago`;
    if (sec < 86400 * 7) return `${Math.floor(sec / 86400)} days ago`;
    if (sec < 86400 * 30) return `${Math.floor(sec / 86400 / 7)} weeks ago`;
    if (sec < 86400 * 365) return `${Math.floor(sec / 86400 / 30)} months ago`;
    return `${Math.floor(sec / 86400 / 365)} years ago`;
  } catch {
    return str;
  }
}

function formatDurationFromText(text) {
  if (text === undefined || text === null) return '—';
  const str = String(text).trim();
  if (!str) return '—';
  const parts = str.split('|').map((s) => s.trim());
  if (parts.length < 2) return '—';
  try {
    const start = new Date(parts[0]);
    const end = new Date(parts[1]);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime())) return '—';
    const ms = Math.max(0, end.getTime() - start.getTime());
    const totalSec = Math.floor(ms / 1000);
    const minutes = Math.floor(totalSec / 60);
    const seconds = totalSec % 60;
    const out = [];
    if (minutes > 0) out.push(`${minutes} minute${minutes !== 1 ? 's' : ''}`);
    if (seconds > 0 || out.length === 0) out.push(`${seconds} second${seconds !== 1 ? 's' : ''}`);
    return out.join(' and ');
  } catch {
    return '—';
  }
}

const defaultTemplateHelpers = {
  formatRelative() {
    return (text, render) => formatRelativeTime(render(text));
  },
  formatDuration() {
    return (text, render) => formatDurationFromText(render(text));
  },
};

function renderTemplate(templateStr, baseContext = {}, value = '') {
  if (!templateStr || typeof templateStr !== 'string') return '';
  try {
    const plain =
      baseContext && typeof baseContext === 'object'
        ? JSON.parse(JSON.stringify(baseContext))
        : {};

    const view = { ...plain, value };

    // If context.attributes exists, merge it like in DataGrid so templates
    // can reference both top-level and nested fields.
    if (plain.attributes && typeof plain.attributes === 'object' && !Array.isArray(plain.attributes)) {
      Object.assign(view, plain.attributes);
    }

    Object.assign(view, defaultTemplateHelpers);

    if (view.attributes && typeof view.attributes === 'object') {
      Object.assign(view.attributes, plain);
      Object.assign(view.attributes, defaultTemplateHelpers);
    }

    return Mustache.render(templateStr.trim(), view);
  } catch {
    return '';
  }
}

// Get the formatted content based on field configuration
const formattedContent = computed(() => {
  if (!props.field) {
    return props.value || '';
  }

  // 1) New: Mustache-style template support via field.template
  if (props.field.template) {
    return renderTemplate(props.field.template, props.context, props.value);
  }

  // 2) No template: show value first, then help as fallback
  const format = props.field.format;
  const displayValue = normalizeValueForDisplay(props.value, props.field);

  if (!format) {
    const hasValue = props.value !== undefined && props.value !== null && displayValue !== '';
    if (hasValue) {
      return displayValue;
    }
    const help = props.field.help;
    if (help) {
      return help;
    }
    return displayValue || '';
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
    return format.replace(/\{value\}/g, displayValue || '');
  }

  return displayValue || '';
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
  white-space: pre-wrap; /* preserve \n in templates like "…\nRan for …" */
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