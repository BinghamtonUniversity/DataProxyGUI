<script setup lang="ts">import { type BreadcrumbItem } from '@/types'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import { ref, shallowRef, watch, toRaw, onMounted,  onBeforeUnmount } from 'vue'
import { Button } from '@/components/ui/button'
import { getStoredAppearance } from '@/composables/useAppearance'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Editor', href: '/editor' },
]

const props = defineProps<{
  code: string
  language?: 'python' | 'php'
  isSaving?: boolean
  saveError?: string,
  saveSuccess?: Boolean,
  hasUnsavedChanges?: Boolean
}>()

const emit = defineEmits<{
  save: [code: string]
  'update:code': [code: string]
  validate: [markers: any[]]
}>()

const language = ref(props.language ?? 'python')
const code = ref(props.code)
// const hasUnsavedChanges = ref(false)

watch(() => props.code, (val) => { 
  code.value = val
  // props.hasUnsavedChanges.value = false
})
watch(() => props.language, (val) => { 
  if (val) language.value = val 
})

// Track changes to show unsaved status
watch(code, (newCode) => {
  // hasUnsavedChanges.value = newCode !== props.code
  emit('update:code', newCode)
})

declare global {
  interface Window {
    monaco: typeof import('monaco-editor');
  }
}

const editor = shallowRef<any>(null);
const editorTheme = ref<"vs" | "vs-dark">("vs-dark")
let mediaQueryList: MediaQueryList | null = null
const validationErrors = ref<number>(0)
const validationWarnings = ref<number>(0)

const editorOptions = {
  automaticLayout: true,
  formatOnType: true,
  formatOnPaste: true,
  validate: true,
  ...(props.language === 'python' ? {
    tabSize: 4,
    insertSpaces: true,
    autoIndent: 'full' as const,
    detectIndentation: false,
  } : {})
}


function handleEditorTheme(){
  let theme = getStoredAppearance() ?? "dark"
  if(theme === "system"){//resolve system preference
    mediaQueryList = window.matchMedia("(prefers-color-scheme: dark)")
    theme = mediaQueryList.matches ? "dark" : "light"
  }

  editorTheme.value = theme === "light" ? "vs" : "vs-dark" //map appearance to Monaco themes
}

function handleMount(editorInstance: any, monaco: any) {
  editor.value = editorInstance
  

  
  // Add keyboard shortcut for save (Ctrl+S / Cmd+S)
  editorInstance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => {
    handleSave()
  })
  
  // Since Python/PHP validation is not available, set up custom validation
  const model = editorInstance.getModel()
  if (model) {

    // Listen for content changes and validate
    editorInstance.onDidChangeModelContent(() => {
      validateCode(editorInstance, monaco)
    })
    
    // Initial validation
    setTimeout(() => validateCode(editorInstance, monaco), 100)
  }
}

const validateCode = (editorInstance: any, monaco: any) => {
  const model = editorInstance.getModel();
  if (!model) return;

  const code = model.getValue();
  const language = props.language || 'python';
  const markers: any[] = [];

  const lines = code.split('\n');


  // --------------------------------------------------------------------
  // Python Validation
  // --------------------------------------------------------------------
  if (language === 'python') {
    const indentStack: number[] = [];
    const openParens: number[] = [];
    const openBrackets: number[] = [];
    const openBraces: number[] = [];
    const keywordsWithColon = /^(if|elif|else|for|while|try|except|finally|with|def|class)\b/;
    
    // Track multi-line string/comment state
    let inTripleQuote = false;
    let tripleQuoteChar = '';
    let tripleQuoteStartLine = -1;

    // Helper function to validate Python identifiers
    const isValidIdentifier = (name: string): boolean => {
      // Must start with letter or underscore, then letters, digits, or underscores
      return /^[a-zA-Z_][a-zA-Z0-9_]*$/.test(name);
    };

    const getInvalidIdentifierReason = (name: string): string => {
      if (/^\d/.test(name)) return 'cannot start with a number';
      if (/-/.test(name)) return 'cannot contain hyphens';
      if (/\s/.test(name)) return 'cannot contain spaces';
      if (/^\$/.test(name)) return 'cannot start with $';
      if (/[^\w]/.test(name)) return 'contains invalid characters';
      return 'invalid identifier';
    };

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i];
      const trimmed = line.trim();

      // Check for triple quotes (''' or """)
      const tripleQuoteMatches = line.match(/'''|"""/g);
      if (tripleQuoteMatches) {
        for (const match of tripleQuoteMatches) {
          if (!inTripleQuote) {
            inTripleQuote = true;
            tripleQuoteChar = match;
            tripleQuoteStartLine = i;
          } else if (match === tripleQuoteChar) {
            inTripleQuote = false;
            tripleQuoteChar = '';
            tripleQuoteStartLine = -1;
          }
        }
      }

      // Skip validation for lines inside multi-line strings/comments
      if (inTripleQuote && i !== tripleQuoteStartLine) {
        continue;
      }

      if (!trimmed || trimmed.startsWith('#')) continue;

      // Skip if line starts with triple quote
      if (trimmed.startsWith('"""') || trimmed.startsWith("'''")) continue;

      // Remove inline comments for analysis
      const lineWithoutComment = trimmed.split('#')[0].trim();

      // Track parentheses/braces
      for (const ch of line) {
        if (ch === '(') openParens.push(i);
        else if (ch === ')') openParens.pop();
        if (ch === '[') openBrackets.push(i);
        else if (ch === ']') openBrackets.pop();
        if (ch === '{') openBraces.push(i);
        else if (ch === '}') openBraces.pop();
      }

      // Missing colon after block headers
      if (keywordsWithColon.test(trimmed) && !lineWithoutComment.endsWith(':')) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: `Missing colon (:) after '${trimmed.split(' ')[0]}' statement`,
          severity: monaco.MarkerSeverity.Error,
        });
      }

      // Check indentation consistency (multiples of 4 spaces)
      const leadingSpaces = line.match(/^(\s*)/)?.[1].length ?? 0;
      if (leadingSpaces % 4 !== 0) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: leadingSpaces + 1,
          message: 'Indentation should be a multiple of 4 spaces',
          severity: monaco.MarkerSeverity.Warning,
        });
      }

      // Validate function definitions
      const funcMatch = lineWithoutComment.match(/^def\s+([a-zA-Z0-9_$-]+)\s*\(/);
      if (funcMatch) {
        const funcName = funcMatch[1];
        if (!isValidIdentifier(funcName)) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: line.indexOf(funcName) + 1,
            endLineNumber: i + 1,
            endColumn: line.indexOf(funcName) + funcName.length + 1,
            message: `Invalid function name '${funcName}' (${getInvalidIdentifierReason(funcName)})`,
            severity: monaco.MarkerSeverity.Error,
          });
        }
      }

      // Validate class definitions
      const classMatch = lineWithoutComment.match(/^class\s+([a-zA-Z0-9_$-]+)\s*[\(:]?/);
      if (classMatch) {
        const className = classMatch[1];
        if (!isValidIdentifier(className)) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: line.indexOf(className) + 1,
            endLineNumber: i + 1,
            endColumn: line.indexOf(className) + className.length + 1,
            message: `Invalid class name '${className}' (${getInvalidIdentifierReason(className)})`,
            severity: monaco.MarkerSeverity.Error,
          });
        }
      }

      // 🔸 Validate import statements
      const importMatch = lineWithoutComment.match(/^import\s+([a-zA-Z0-9_$-]+)/);
      if (importMatch) {
        const moduleName = importMatch[1];
        if (!isValidIdentifier(moduleName)) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: line.indexOf(moduleName) + 1,
            endLineNumber: i + 1,
            endColumn: line.indexOf(moduleName) + moduleName.length + 1,
            message: `Invalid module name '${moduleName}' (${getInvalidIdentifierReason(moduleName)})`,
            severity: monaco.MarkerSeverity.Error,
          });
        }
      }

      // Validate 'from ... import' statements
      const fromImportMatch = lineWithoutComment.match(/^from\s+([a-zA-Z0-9_$-]+)\s+import/);
      if (fromImportMatch) {
        const moduleName = fromImportMatch[1];
        if (!isValidIdentifier(moduleName)) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: line.indexOf(moduleName) + 1,
            endLineNumber: i + 1,
            endColumn: line.indexOf(moduleName) + moduleName.length + 1,
            message: `Invalid module name '${moduleName}' (${getInvalidIdentifierReason(moduleName)})`,
            severity: monaco.MarkerSeverity.Error,
          });
        }
      }

      // Validate variable assignments
      if (trimmed.includes('=') && !trimmed.startsWith('def') && !trimmed.startsWith('class')) {
        const assignMatch = lineWithoutComment.match(/^([a-zA-Z0-9_$\s-]+)\s*=\s*[^=]/);
        if (assignMatch) {
          const varName = assignMatch[1].trim();
          
          // Check for spaces in variable name
          if (varName.includes(' ')) {
            const parts = varName.split(' ');
            markers.push({
              startLineNumber: i + 1,
              startColumn: 1,
              endLineNumber: i + 1,
              endColumn: line.indexOf('=') + 1,
              message: `Invalid variable name '${varName}' (cannot contain spaces)`,
              severity: monaco.MarkerSeverity.Error,
            });
          } else if (!isValidIdentifier(varName)) {
            markers.push({
              startLineNumber: i + 1,
              startColumn: 1,
              endLineNumber: i + 1,
              endColumn: line.indexOf('=') + 1,
              message: `Invalid variable name '${varName}' (${getInvalidIdentifierReason(varName)})`,
              severity: monaco.MarkerSeverity.Error,
            });
          }
        }

        // Check right side for invalid expressions
        const [left, right] = trimmed.split('=').map((s: string) => s.trim());
        if (right?.match(/^\d+[a-zA-Z]/)) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: line.indexOf(right) + 1,
            endLineNumber: i + 1,
            endColumn: line.length + 1,
            message: 'Invalid expression: number directly followed by letters',
            severity: monaco.MarkerSeverity.Error,
          });
        }
      }

      // Detect unterminated single/double quotes (excluding triple quotes)
      const lineWithoutTripleQuotes = line.replace(/'''|"""/g, '');
      // Remove properly closed strings first to avoid counting quotes inside them
      const lineWithoutStrings = lineWithoutTripleQuotes
        .replace(/"(?:[^"\\]|\\.)*"/g, '') // Remove double-quoted strings
        .replace(/'(?:[^'\\]|\\.)*'/g, ''); // Remove single-quoted strings

      // Now count remaining unmatched quotes
      const singleQuotes = (lineWithoutStrings.match(/'/g) || []).length;
      const doubleQuotes = (lineWithoutStrings.match(/"/g) || []).length;

      if (singleQuotes % 2 !== 0 || doubleQuotes % 2 !== 0) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: 'Unterminated string literal',
          severity: monaco.MarkerSeverity.Error,
        });
      }
    }
    
    // Check for unterminated multi-line string/comment
    if (inTripleQuote) {
      markers.push({
        startLineNumber: tripleQuoteStartLine + 1,
        startColumn: 1,
        endLineNumber: tripleQuoteStartLine + 1,
        endColumn: 2,
        message: `Unterminated multi-line string (${tripleQuoteChar})`,
        severity: monaco.MarkerSeverity.Error,
      });
    }

    // Check unmatched parentheses/brackets/braces
    if (openParens.length > 0)
      markers.push({
        startLineNumber: openParens[0] + 1,
        startColumn: 1,
        endLineNumber: openParens[0] + 1,
        endColumn: 2,
        message: 'Unmatched parenthesis "("',
        severity: monaco.MarkerSeverity.Error,
      });

    if (openBrackets.length > 0)
      markers.push({
        startLineNumber: openBrackets[0] + 1,
        startColumn: 1,
        endLineNumber: openBrackets[0] + 1,
        endColumn: 2,
        message: 'Unmatched square bracket "["',
        severity: monaco.MarkerSeverity.Error,
      });

    if (openBraces.length > 0)
      markers.push({
        startLineNumber: openBraces[0] + 1,
        startColumn: 1,
        endLineNumber: openBraces[0] + 1,
        endColumn: 2,
        message: 'Unmatched curly brace "{"',
        severity: monaco.MarkerSeverity.Error,
      });
  }

  // --------------------------------------------------------------------
  // PHP Validation
  // --------------------------------------------------------------------
  // else if (language === 'php') {
  //   const openTags = (code.match(/<\?php/g) || []).length;
  //   const closeTags = (code.match(/\?>/g) || []).length;
  //   const openBraces = (code.match(/\{/g) || []).length;
  //   const closeBraces = (code.match(/\}/g) || []).length;
  //   const semicolonLines = [];

  //   for (let i = 0; i < lines.length; i++) {
  //     const trimmed = lines[i].trim();
  //     if (!trimmed || trimmed.startsWith('//') || trimmed.startsWith('#')) continue;

  //     // Missing semicolon after statements
  //     if (
  //       !trimmed.endsWith(';') &&
  //       !trimmed.endsWith('{') &&
  //       !trimmed.endsWith('}') &&
  //       !trimmed.startsWith('<?php') &&
  //       !trimmed.startsWith('?>') &&
  //       !trimmed.match(/(if|else|while|for|foreach|function|class|switch|case|default)\b/)
  //     ) {
  //       semicolonLines.push(i + 1);
  //     }

  //     // Unterminated quotes
  //     const quoteMatches = trimmed.match(/['"]/g);
  //     if (quoteMatches && quoteMatches.length % 2 !== 0) {
  //       markers.push({
  //         startLineNumber: i + 1,
  //         startColumn: 1,
  //         endLineNumber: i + 1,
  //         endColumn: lines[i].length + 1,
  //         message: 'Unterminated string literal',
  //         severity: monaco.MarkerSeverity.Error,
  //       });
  //     }
  //   }

  //   if (openTags !== closeTags) {
  //     markers.push({
  //       startLineNumber: 1,
  //       startColumn: 1,
  //       endLineNumber: 1,
  //       endColumn: 10,
  //       message: 'PHP open/close tags mismatch',
  //       severity: monaco.MarkerSeverity.Error,
  //     });
  //   }

  //   if (openBraces !== closeBraces) {
  //     markers.push({
  //       startLineNumber: 1,
  //       startColumn: 1,
  //       endLineNumber: model.getLineCount(),
  //       endColumn: model.getLineMaxColumn(model.getLineCount()),
  //       message: 'Unmatched braces: ensure all { have matching }',
  //       severity: monaco.MarkerSeverity.Error,
  //     });
  //   }

  //   semicolonLines.forEach((lineNum) => {
  //     markers.push({
  //       startLineNumber: lineNum,
  //       startColumn: 1,
  //       endLineNumber: lineNum,
  //       endColumn: lines[lineNum - 1].length + 1,
  //       message: 'Missing semicolon (;) at end of statement',
  //       severity: monaco.MarkerSeverity.Warning,
  //     });
  //   });
  // }

  // --------------------------------------------------------------------
  // Apply Markers + Emit
  // --------------------------------------------------------------------
  monaco.editor.setModelMarkers(model, 'advanced-validation', markers);

  validationErrors.value = markers.filter(
    (m) => m.severity === monaco.MarkerSeverity.Error
  ).length;
  validationWarnings.value = markers.filter(
    (m) => m.severity === monaco.MarkerSeverity.Warning
  ).length;

  emit('validate', markers);


};


const handleValidate = (markers: any[]) => {
  // Update validation counters
  validationErrors.value = markers.filter(m => m.severity >= 8).length // Monaco.MarkerSeverity.Error = 8
  validationWarnings.value = markers.filter(m => m.severity === 4).length // Monaco.MarkerSeverity.Warning = 4
  // Emit validation markers to parent
  emit('validate', markers)
}

const handleSave = () => {
  if (props.isSaving) return
  emit('save', code.value)
}

// const formatCode = () => {
//   editor.value?.getAction('editor.action.formatDocument').run()
// }

onMounted(() => {
  handleEditorTheme()
  if(getStoredAppearance() === "system"){//if system is theme watch live browser changes
    mediaQueryList = window.matchMedia("(prefers-color-scheme: dark)")
    mediaQueryList.addEventListener("change", handleEditorTheme)
  }
})
onBeforeUnmount(() => {
  if(mediaQueryList){
    mediaQueryList.removeEventListener("change", handleEditorTheme)
  }
})
</script>

<template>
  <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
    <!-- Toolbar -->
    <div class="flex items-center justify-between gap-2 pb-2 border-b">
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium">
          {{ language.toUpperCase() }}
        </span>
        <span v-if="hasUnsavedChanges" class="text-xs text-amber-600 dark:text-amber-400">
          • Unsaved changes
        </span>
      </div>
      
      <div class="flex items-center gap-2">
        <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="props.saveError" class="px-3 py-1.5 bg-red-50 border border-red-200 text-red-700 rounded-md text-xs">
            {{ props.saveError }}
          </div>
        </Transition>
        
        <!-- <Transition
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="props.saveSuccess" class="px-3 py-1.5 bg-green-50 border border-green-200 text-green-700 rounded-md text-xs flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Saved!
          </div>
        </Transition> -->
        <!-- <Button
          variant="outline"
          size="sm"
          @click="formatCode"
          :disabled="props.isSaving"
        >
          Format
        </Button> -->
        <!-- <Button
          size="sm"
          @click="handleSave"
          :disabled="props.isSaving || !hasUnsavedChanges || validationErrors > 0"
        >
          <span v-if="props.isSaving" class="flex items-center gap-2">
            <div class="w-4 h-4 border-2 border-current border-t-transparent rounded-full animate-spin"></div>
            Saving...
          </span>
          <span v-else>
            Save
          </span>
        </Button> -->
      </div>
    </div>

    <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border" style="min-height: 70vh;">
      <vue-monaco-editor
        v-model:value="code"
        :language="props.language"
        :theme="editorTheme"
        :options="editorOptions"
        @mount="handleMount"
        @validate="handleValidate"    
        style="height:100%; width:100%;"
      />
    </div>
    
    <!-- Keyboard shortcut hint -->
    <div class="text-xs text-muted-foreground">
      Press <kbd class="px-1 py-0.5 bg-muted rounded">Ctrl+S</kbd> (or <kbd class="px-1 py-0.5 bg-muted rounded">Cmd+S</kbd>) to save
    </div>
  </div>
</template>

<style scoped>
kbd {
  font-family: ui-monospace, SFMono-Regular, "SF Mono", monospace;
}
</style>