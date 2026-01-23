<script setup lang="ts">import { type BreadcrumbItem } from '@/types'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import { ref, shallowRef, watch, toRaw, onMounted,  onBeforeUnmount } from 'vue'
import { Button } from '@/components/ui/button'
import { getStoredAppearance } from '@/composables/useAppearance'
import { validateCode as validateCodeLogic } from '@/lib/editorValidator'

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

const language = ref(props.language)

// Helper function to add <?php prefix for PHP files (for display only)
const addPhpPrefix = (content: string): string => {
  if (props.language === 'php' && !content.trim().startsWith('<?php')) {
    return '<?php\n' + content
  }
  return content
}

// Initialize code with PHP prefix if needed
const code = ref(addPhpPrefix(props.code))
// const hasUnsavedChanges = ref(false)

// Helper function to strip <?php prefix (for saving/emitting)
const stripPhpPrefix = (content: string): string => {
  if (props.language === 'php') {
    const trimmed = content.trimStart()
    if (trimmed.startsWith('<?php')) {
      // Remove <?php and any following whitespace/newlines
      return trimmed.replace(/^<\?php\s*\n?/, '').trimStart()
    }
  }
  return content
}

watch(() => props.code, (val) => { 
  // For PHP, ensure <?php prefix is added for display
  code.value = addPhpPrefix(val)
  // props.hasUnsavedChanges.value = false
})
watch(() => props.language, (val) => { 
  if (val) {
    const wasPhp = language.value === 'php'
    language.value = val
    // When language changes to PHP, ensure prefix is added
    if (val === 'php') {
      code.value = addPhpPrefix(code.value)
    } else if (wasPhp) {
      // When switching away from PHP, strip the prefix
      code.value = stripPhpPrefix(code.value)
    }
  }
})

// Track changes to show unsaved status
watch(code, (newCode) => {
  // hasUnsavedChanges.value = newCode !== props.code
  // For PHP, ensure prefix is maintained in editor
  if (props.language === 'php' && !newCode.trim().startsWith('<?php')) {
    // Re-add prefix if it was removed
    const prefixedCode = '<?php\n' + newCode
    code.value = prefixedCode
    // Update editor if mounted
    if (editor.value) {
      const position = editor.value.getPosition()
      editor.value.setValue(prefixedCode)
      if (position) {
        editor.value.setPosition({
          lineNumber: position.lineNumber + 1,
          column: position.column
        })
      }
    }
    // Emit without prefix
    emit('update:code', stripPhpPrefix(prefixedCode))
    return
  }
  // Strip <?php prefix before emitting
  emit('update:code', stripPhpPrefix(newCode))
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
  
  // Ensure PHP files have <?php prefix in the editor
  if (props.language === 'php') {
    const currentValue = editorInstance.getValue()
    if (!currentValue.trim().startsWith('<?php')) {
      editorInstance.setValue('<?php\n' + currentValue)
      code.value = editorInstance.getValue()
    }
  }
  
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
  const model = editorInstance.getModel()
  if (!model) return

  const code = model.getValue()
  const language = props.language || 'python'

  // Use the validator from separate file
  const result = validateCodeLogic(code, language as 'python' | 'php', model)

  // Apply markers to Monaco editor
  monaco.editor.setModelMarkers(model, 'advanced-validation', result.markers)

  // Update validation counters
  validationErrors.value = result.errors
  validationWarnings.value = result.warnings

  // Emit validation event
  emit('validate', result.markers)
}

const handleValidate = (markers: any[]) => {
  // Update validation counters
  validationErrors.value = markers.filter(m => m.severity >= 8).length // Monaco.MarkerSeverity.Error = 8
  validationWarnings.value = markers.filter(m => m.severity === 4).length // Monaco.MarkerSeverity.Warning = 4
  // Emit validation markers to parent
  emit('validate', markers)
}

const handleSave = () => {
  if (props.isSaving) return
  // Strip <?php prefix before saving
  emit('save', stripPhpPrefix(code.value))
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
          {{ language?.toUpperCase() }}
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