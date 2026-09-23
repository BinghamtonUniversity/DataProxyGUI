<template>
  <div v-if="show" :class="containerClass">
    <!-- Label -->
    <label v-if="label" :for="fieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2" :class="{ 'text-red-500': localError }">
      {{ label }}
      <span v-if="required === true || required === 'true'" class="text-red-500 ml-1">*</span>
      <span
        v-if="info"
        class="relative cursor-pointer ml-1"
        @mouseenter="showInfo = true || showInfo === 'true'"
        @mouseleave="showInfo = false || showInfo === 'false'"
      >
        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 inline" fill="currentColor" viewBox="0 0 20 20">
          <circle cx="10" cy="10" r="9" fill="currentColor"/>
          <text x="10" y="15" text-anchor="middle" font-size="12" fill="white">?</text>
        </svg>
        <span
          v-if="showInfo"
          class="absolute left-1/2 z-10 -translate-x-1/2 mt-2 w-48 p-2 rounded-lg bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white shadow-lg border border-gray-200 dark:border-gray-700"
          style="pointer-events: none;"
        >
          {{ info }}
        </span>
      </span>
    </label>

    <!-- Monaco Editor Container -->
    <div class="relative">
      <!-- Editor Options Bar -->
      <div v-if="showOptions" class="flex items-center justify-between mb-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-t-md border border-b-0 border-gray-300 dark:border-gray-600">
        <div class="flex items-center space-x-4">
          <!-- Language Selector -->
          <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Language:</label>
            <select 
              v-model="selectedLanguage"
              :disabled="!edit"
              class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option v-for="lang in supportedLanguages" :key="lang.value" :value="lang.value">
                {{ lang.label }}
              </option>
            </select>
          </div>

          <!-- Theme Selector -->
          <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Theme:</label>
            <select 
              v-model="selectedTheme"
              :disabled="!edit"
              class="px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="vs">Light</option>
              <option value="vs-dark">Dark</option>
              <option value="hc-black">High Contrast</option>
            </select>
          </div>

          <!-- Font Size -->
          <div class="flex items-center space-x-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Font Size:</label>
            <input 
              v-model.number="fontSize"
              type="number"
              min="8"
              max="24"
              :disabled="!edit"
              class="w-16 px-2 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <div class="flex items-center space-x-2">
          <!-- Word Wrap Toggle -->
          <label class="flex items-center space-x-1 text-sm text-gray-700 dark:text-gray-300">
            <input 
              v-model="wordWrap"
              type="checkbox"
              :disabled="!edit"
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            />
            <span>Word Wrap</span>
          </label>

          <!-- Minimap Toggle -->
          <label class="flex items-center space-x-1 text-sm text-gray-700 dark:text-gray-300">
            <input 
              v-model="showMinimap"
              type="checkbox"
              :disabled="!edit"
              class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500"
            />
            <span>Minimap</span>
          </label>
        </div>
      </div>

       <!-- Monaco Editor -->
       <div :class="editorClass" :style="{ height: editorHeight + 'px' }">
         <VueMonacoEditor
           v-model:value="internalValue"
           :language="selectedLanguage"
           :theme="selectedTheme"
           :options="editorOptions"
           @mount="handleMount"
           style="height: 100%; width: 100%;"
         />
       </div>

      <!-- Editor Status Bar -->
      <div v-if="showStatusBar" class="flex items-center justify-between px-3 py-1 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 border border-t-0 border-gray-300 dark:border-gray-600 rounded-b-md">
        <div class="flex items-center space-x-4">
          <span>Lines: {{ lineCount }}</span>
          <span>Characters: {{ characterCount }}</span>
          <span v-if="selectedLanguage">Language: {{ getLanguageLabel(selectedLanguage) }}</span>
        </div>
        <div class="flex items-center space-x-2">
          <span v-if="isModified" class="text-orange-500">Modified</span>
          <span v-if="isReadOnly" class="text-gray-500">Read Only</span>
        </div>
      </div>
    </div>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (errors && errors.length > 0)" class="mt-2 text-xs text-red-500">
      {{ localError || errors[0] }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick, shallowRef } from 'vue';
import { VueMonacoEditor } from '@guolao/vue-monaco-editor';
import { 
  generateFieldId, 
  createEventHandlers, 
  getFieldContainerClass,
  getSafeFieldValue 
} from './functions.js';
import { validateField } from './validation.js';

// Props
const props = defineProps({
  // Field identification
  name: {
    type: String,
    required: true
  },
  fieldId: {
    type: String,
    default: () => generateFieldId('monaco')
  },
  
  // Display properties
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: ''
  },
  help: {
    type: String,
    default: ''
  },
  info: {
    type: String,
    default: ''
  },
  
  // Value and model
  value: {
    type: String,
    default: ''
  },
  
  // Input properties
  required:  { type: [Boolean,String,Array], default: false },
  show:  { type: [Boolean,String,Array], default: true },
  edit:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  
  // Fieldset context
  inFieldset: {
    type: Boolean,
    default: false
  },
  
  // Editor specific props
  language: {
    type: String,
    default: 'python'
  },
  theme: {
    type: String,
    default: 'vs'
  },
  height: {
    type: Number,
    default: 300
  },
  fontSize: {
    type: Number,
    default: 14
  },
  wordWrap: {
    type: Boolean,
    default: false
  },
  showMinimap: {
    type: Boolean,
    default: false
  },
  showOptions: {
    type: Boolean,
    default: false
  },
  showStatusBar: {
    type: Boolean,
    default: true
  },
  readOnly: {
    type: Boolean,
    default: false
  },
  
  // Validation
  errors: {
    type: Array,
    default: () => []
  },
  
  // Accessibility
  autofocus: {
    type: Boolean,
    default: false
  },
  
  // Error handling
  localError: {
    type: String,
    default: ''
  },
  
  // Features
  validate: {
    type: Array,
    default: () => []
  },
  matchValues: {
    type: Object,
    default: undefined
  }
});

// Emits
const emit = defineEmits([
  'update:value',
  'input',
  'change',
  'blur',
  'focus',
  'keydown',
  'validation-error',
  'validation-success'
]);

// Reactive state
const showInfo = ref(false);
const internalValue = ref(props.value);
const localError = ref('');
const isModified = ref(false);
const isReadOnly = ref(props.readOnly);

// Editor state
const editor = shallowRef(null);
const selectedLanguage = ref(props.language);
const isDark = ref(document.documentElement.classList.contains('dark'));
const selectedTheme = ref(isDark.value ? 'vs-dark' : 'vs');
const fontSize = ref(props.fontSize);
const wordWrap = ref(props.wordWrap);
const showMinimap = ref(props.showMinimap);
const lineCount = ref(0);
const characterCount = ref(0);
let themeObserver = null;

// Supported languages
const supportedLanguages = ref([
  { label: 'JavaScript', value: 'javascript' },
  { label: 'TypeScript', value: 'typescript' },
  { label: 'Python', value: 'python' },
  { label: 'Java', value: 'java' },
  { label: 'C#', value: 'csharp' },
  { label: 'C++', value: 'cpp' },
  { label: 'C', value: 'c' },
  { label: 'Go', value: 'go' },
  { label: 'Rust', value: 'rust' },
  { label: 'PHP', value: 'php' },
  { label: 'Ruby', value: 'ruby' },
  { label: 'Swift', value: 'swift' },
  { label: 'Kotlin', value: 'kotlin' },
  { label: 'HTML', value: 'html' },
  { label: 'CSS', value: 'css' },
  { label: 'SCSS', value: 'scss' },
  { label: 'Less', value: 'less' },
  { label: 'JSON', value: 'json' },
  { label: 'XML', value: 'xml' },
  { label: 'YAML', value: 'yaml' },
  { label: 'Markdown', value: 'markdown' },
  { label: 'SQL', value: 'sql' },
  { label: 'Shell', value: 'shell' },
  { label: 'Dockerfile', value: 'dockerfile' },
  { label: 'Plain Text', value: 'plaintext' }
]);

// Create event handlers using shared functions
const { handleChange, handleBlur, handleFocus, handleInput } = createEventHandlers(
  props, 
  emit, 
  { internalValue, localError }, 
  'monaco'
);

// Computed properties
const containerClass = computed(() => getFieldContainerClass(props, props.inFieldset));

const editorClass = computed(() => [
  'monaco-editor-container',
  'border border-gray-300 dark:border-gray-600 rounded-md',
  !props.edit || props.readOnly ? 'opacity-75' : '',
  (localError.value || (props.errors && props.errors.length > 0)) ? 'border-red-500' : ''
]);

const editorHeight = computed(() => props.height);

const getLanguageLabel = (value) => {
  const lang = supportedLanguages.value.find(l => l.value === value);
  return lang ? lang.label : value;
};

// Editor options
const editorOptions = computed(() => ({
  automaticLayout: true,
  formatOnType: false, // Disable for better performance
  formatOnPaste: false, // Disable for better performance
  fontSize: fontSize.value,
  wordWrap: wordWrap.value ? 'on' : 'off',
  minimap: { enabled: false }, // Disable minimap for better performance
  readOnly: !props.edit || props.readOnly,
  scrollBeyondLastLine: false,
  renderWhitespace: 'none', // Reduce rendering load
  renderControlCharacters: false, // Reduce rendering load
  lineNumbers: 'on',
  folding: true,
  selectOnLineNumbers: true,
  roundedSelection: false,
  cursorStyle: 'line',
  cursorBlinking: 'blink',
  cursorWidth: 0,
  mouseWheelZoom: false, // Disable for better scrolling
  contextmenu: true,
  suggestOnTriggerCharacters: false, // Disable for better performance
  acceptSuggestionOnEnter: 'off',
  tabCompletion: 'off',
  wordBasedSuggestions: 'off', // Disable for better performance
  parameterHints: { enabled: false }, // Disable for better performance
  hover: { enabled: false }, // Disable for better performance
  links: false, // Disable for better performance
  colorDecorators: false, // Disable for better performance
  codeLens: false, // Disable for better performance
  foldingStrategy: 'indentation',
  // Add performance optimizations
  smoothScrolling: true,
  scrollbar: {
    vertical: 'auto',
    horizontal: 'auto',
    useShadows: false,
    verticalHasArrows: false,
    horizontalHasArrows: false,
    verticalScrollbarSize: 10,
    horizontalScrollbarSize: 10,
    arrowSize: 11
  },
  showFoldingControls: 'always',
  matchBrackets: 'always',
  renderLineHighlight: 'all',
  renderIndentGuides: true,
  highlightActiveIndentGuide: true,
  bracketPairColorization: { enabled: true },
  guides: {
    bracketPairs: true,
    bracketPairsHorizontal: true,
    highlightActiveBracketPair: true,
    indentation: true
  }
}));

// Monaco Editor methods
const handleMount = (editorInstance, monaco) => {
  editor.value = editorInstance;
  
  // Set up event listeners
  editorInstance.onDidChangeModelContent(() => {
    const value = editorInstance.getValue();
    internalValue.value = value;
    isModified.value = true;
    updateStats();
    emit('update:value', value);
    emit('input', value);
  });

  editorInstance.onDidFocusEditorWidget(() => {
    emit('focus');
  });

  editorInstance.onDidBlurEditorWidget(() => {
    emit('blur');
    handleBlur();
  });

  editorInstance.onKeyDown((e) => {
    emit('keydown', e);
  });

  // Initial stats
  updateStats();
};

const updateStats = () => {
  if (editor.value) {
    const model = editor.value.getModel();
    if (model) {
      lineCount.value = model.getLineCount();
      characterCount.value = model.getValueLength();
    }
  }
};

// No need for updateEditorOptions or disposeEditor with VueMonacoEditor

// Watchers
watch(() => props.value, (newValue) => {
  if (newValue !== internalValue.value) {
    internalValue.value = newValue;
    updateStats();
  }
}, { immediate: true });

watch(() => props.edit, (newEdit) => {
  isReadOnly.value = !newEdit || props.readOnly;
});

watch(() => props.readOnly, (newReadOnly) => {
  isReadOnly.value = newReadOnly;
});

watch(() => props.validate, () => {
  // Validation will happen on blur instead
});

// Lifecycle
onMounted(() => {
  if (props.autofocus && editor.value) {
    editor.value.focus();
  }

  themeObserver = new MutationObserver(() => {
    const dark = document.documentElement.classList.contains('dark');
    if (dark !== isDark.value) {
      isDark.value = dark;
      selectedTheme.value = dark ? 'vs-dark' : 'vs';
    }
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

onUnmounted(() => {
  themeObserver?.disconnect();
});
</script>

<style scoped>
.monaco-editor-container {
  width: 100%;
  position: relative;
}

/* Monaco Editor custom styles */
:deep(.monaco-editor) {
  border-radius: 0.375rem; /* rounded-md */
}

:deep(.monaco-editor .margin) {
  background-color: transparent;
}

/* Dark mode adjustments */
.dark :deep(.monaco-editor) {
  background-color: rgb(31 41 55) !important; /* gray-800 */
}

.dark :deep(.monaco-editor .monaco-editor-background) {
  background-color: rgb(31 41 55) !important; /* gray-800 */
}

/* Error state */
.monaco-editor-container.border-red-500 :deep(.monaco-editor) {
  border-color: rgb(239 68 68) !important; /* red-500 */
}

/* Read-only state */
.monaco-editor-container.opacity-75 :deep(.monaco-editor) {
  opacity: 0.75;
}

/* Focus ring */
.monaco-editor-container:focus-within {
  box-shadow: 0 0 0 3px rgb(59 130 246 / 0.2); /* blue-500, 20% opacity */
}

/* Status bar styling */
.monaco-editor-container + div {
  font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
}

/* Custom scrollbar for editor container */
.monaco-editor-container :deep(.monaco-scrollable-element) {
  scrollbar-width: thin;
}

.monaco-editor-container :deep(.monaco-scrollable-element)::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.monaco-editor-container :deep(.monaco-scrollable-element)::-webkit-scrollbar-track {
  background: rgb(243 244 246); /* gray-100 */
}

.dark .monaco-editor-container :deep(.monaco-scrollable-element)::-webkit-scrollbar-track {
  background: rgb(55 65 81); /* gray-700 */
}

.monaco-editor-container :deep(.monaco-scrollable-element)::-webkit-scrollbar-thumb {
  background: rgb(156 163 175); /* gray-400 */
  border-radius: 4px;
}

.monaco-editor-container :deep(.monaco-scrollable-element)::-webkit-scrollbar-thumb:hover {
  background: rgb(107 114 128); /* gray-500 */
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .monaco-editor-container {
    font-size: 12px;
  }
}
</style>
