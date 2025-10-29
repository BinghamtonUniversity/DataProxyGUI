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
  
  console.log('Editor mounted - Monaco languages available:', Object.keys(monaco.languages))
  console.log('Editor mounted - Monaco languages.python:', monaco.languages.python)
  console.log('Editor mounted - Monaco languages.php:', monaco.languages.php)
  
  // Add keyboard shortcut for save (Ctrl+S / Cmd+S)
  editorInstance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => {
    handleSave()
  })
  
  // Since Python/PHP validation is not available, set up custom validation
  const model = editorInstance.getModel()
  if (model) {
    console.log('Setting up custom validation for', props.language)
    
    // Listen for content changes and validate
    editorInstance.onDidChangeModelContent(() => {
      validateCode(editorInstance, monaco)
    })
    
    // Initial validation
    setTimeout(() => validateCode(editorInstance, monaco), 100)
  }
}

const validateCode = (editorInstance: any, monaco: any) => {
  const model = editorInstance.getModel()
  if (!model) return
  
  const code = model.getValue()
  const language = props.language || 'python'
  const markers: any[] = []
  
  console.log('Validating code for language:', language)
  
  // Basic syntax validation
  if (language === 'python') {
    const lines = code.split('\n')
    for (let i = 0; i < lines.length; i++) {
      const line = lines[i].trim()
      
      // Skip empty lines and comments
      if (!line || line.startsWith('#')) continue
      
      // Check for invalid variable assignments (like "a = 3a")
      if (line.includes('=')) {
        const parts = line.split('=')
        if (parts.length === 2) {
          const rightSide = parts[1].trim()
          // Check if right side contains invalid characters for Python
          if (rightSide.match(/^\d+[a-zA-Z]/)) {
            markers.push({
              startLineNumber: i + 1,
              startColumn: line.indexOf(rightSide) + 1,
              endLineNumber: i + 1,
              endColumn: line.length + 1,
              message: 'Invalid syntax: number followed by letter',
              severity: monaco.MarkerSeverity.Error
            })
          }
        }
      }
      
      // Check for standalone invalid identifiers (like "ad34asre3")
      if (line.match(/^[a-zA-Z0-9_]+$/) && !line.match(/^(def|class|if|else|elif|for|while|try|except|finally|with|import|from|return|pass|break|continue|lambda|yield|global|nonlocal|assert|del|raise)$/)) {
        // This is a standalone identifier that's not a keyword
        // Check if it looks like invalid syntax
        if (line.match(/^[a-zA-Z]+\d+[a-zA-Z]+/) || line.match(/^\d+[a-zA-Z]+/)) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: 1,
            endLineNumber: i + 1,
            endColumn: line.length + 1,
            message: 'Invalid syntax: malformed identifier',
            severity: monaco.MarkerSeverity.Error
          })
        }
      }
    }
  } else if (language === 'php') {
    // Basic PHP validation
    const openBraces = (code.match(/\{/g) || []).length
    const closeBraces = (code.match(/\}/g) || []).length
    if (openBraces !== closeBraces) {
      markers.push({
        startLineNumber: 1,
        startColumn: 1,
        endLineNumber: model.getLineCount(),
        endColumn: model.getLineMaxColumn(model.getLineCount()),
        message: 'Unmatched braces: Check that all opening { have corresponding closing }',
        severity: monaco.MarkerSeverity.Error
      })
    }
  }
  
  console.log('Custom validation found markers:', markers)
  
  // Set markers in the editor
  monaco.editor.setModelMarkers(model, 'custom-validation', markers)
  
  // Update validation counters
  validationErrors.value = markers.filter(m => m.severity >= monaco.MarkerSeverity.Error).length
  validationWarnings.value = markers.filter(m => m.severity === monaco.MarkerSeverity.Warning).length
  
  // Emit validation markers to parent
  emit('validate', markers)
}

const handleValidate = (markers: any[]) => {
  console.log('Editor.vue - handleValidate called with markers:', markers)
  // Update validation counters
  validationErrors.value = markers.filter(m => m.severity >= 8).length // Monaco.MarkerSeverity.Error = 8
  validationWarnings.value = markers.filter(m => m.severity === 4).length // Monaco.MarkerSeverity.Warning = 4
  
  console.log('Editor.vue - validationErrors:', validationErrors.value, 'validationWarnings:', validationWarnings.value)
  
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
        
        <Transition
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
        </Transition>
        <!-- <Button
          variant="outline"
          size="sm"
          @click="formatCode"
          :disabled="props.isSaving"
        >
          Format
        </Button> -->
        <Button
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
        </Button>
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
        @onValidate="handleValidate"
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