<script setup lang="ts">import { type BreadcrumbItem } from '@/types'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import { ref, shallowRef, watch, computed, onMounted, onBeforeUnmount } from 'vue'
import { Button } from '@/components/ui/button'
import { getStoredAppearance } from '@/composables/useAppearance'
import { validateCode as validateCodeLogic } from '@/lib/editorValidator'
import { createPhpWorker } from '@/lib/createPhpWorker'
import { createPythonWorker } from '@/lib/createPythonWorker'
import { checkPythonForbiddenUsage } from '@/lib/pythonPolicyCheck'
import { monacoLanguageFor, stripPhpTag, minimalEdit } from '@/lib/monacoPhpSnippet'


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
  // Files tab keep their `<?php` tag in the editor and when saving.
  // PHP functions (default) are shown and saved without it.
  keepPhpTag?: boolean
}>()

const emit = defineEmits<{
  save: [code: string]
  'update:code': [code: string]
  validate: [markers: any[]]
}>()


let phpWorker: Worker | null = null
let phpWorkerPromise: Promise<Worker> | null = null
let requestCounter = 0
let latestRequestId = 0
// The editor shows code without `<?php`, but the PHP parser needs it, so validation
// prepends the tag and shifts marker line numbers back by this many lines.
let latestPhpLineOffset = 0

let pythonWorker: Worker | null = null
let pyRequestCounter = 0
let latestPyRequestId = 0

async function getPhpWorker(): Promise<Worker> {
  if (phpWorker) return phpWorker
  if (!phpWorkerPromise) {
    phpWorkerPromise = createPhpWorker().then((worker) => {
      phpWorker = worker
      worker.onmessage = (e: MessageEvent) => {
        const { requestId, markers: rawMarkers } = e.data
        if (requestId !== latestRequestId) return
        const markers = shiftMarkers(rawMarkers, latestPhpLineOffset)
        if (!editor.value) return
        const model = editor.value.getModel()
        if (!model) return
        window.monaco.editor.setModelMarkers(model, 'php-validation', markers)
        validationErrors.value = markers.filter((m: any) => m.severity === 8).length
        validationWarnings.value = markers.filter((m: any) => m.severity === 4).length
        emit('validate', markers)
      }
      worker.onerror = (err) => {
        console.error('PHP validation worker error:', err)
      }
      return worker
    })
  }
  return phpWorkerPromise
}

function shiftMarkers(markers: any[], offset: number) {
  if (!offset) return markers
  return markers.map((m) => ({
    ...m,
    startLineNumber: Math.max(1, m.startLineNumber - offset),
    endLineNumber: Math.max(1, m.endLineNumber - offset),
  }))
}

async function validatePhpRemote(code: string) {
  const worker = await getPhpWorker()
  const hasTag = code.trimStart().startsWith('<?php')
  latestPhpLineOffset = hasTag ? 0 : 1
  latestRequestId = ++requestCounter
  worker.postMessage({ code: hasTag ? code : '<?php\n' + code, requestId: latestRequestId })
}

function getPythonWorker(): Worker {
  if (!pythonWorker) {
    pythonWorker = createPythonWorker()
    pythonWorker.onmessage = (e: MessageEvent) => {
      const { requestId, markers: syntaxMarkers } = e.data
      if (requestId !== latestPyRequestId) return
      if (!editor.value) return
      const model = editor.value.getModel()
      if (!model) return

      const code = model.getValue()
      const policyMarkers = checkPythonForbiddenUsage(code)
      const allMarkers = [...syntaxMarkers, ...policyMarkers]

      window.monaco.editor.setModelMarkers(model, 'python-validation', allMarkers)
      validationErrors.value = allMarkers.filter((m: any) => m.severity === 8).length
      validationWarnings.value = allMarkers.filter((m: any) => m.severity === 4).length
      emit('validate', allMarkers)
    }
    pythonWorker.onerror = (err) => {
      console.error('Python validation worker error:', err)
    }
  }
  return pythonWorker
}

function validatePythonRemote(code: string) {
  const worker = getPythonWorker()
  latestPyRequestId = ++pyRequestCounter
  worker.postMessage({ code, requestId: latestPyRequestId })
}

const language = ref(props.language)
// Tagged PHP files use Monaco's built-in `php` (which needs the tag);
// untagged function bodies use `php-snippet`.
const monacoLanguage = computed(() =>
  props.language === 'php' && props.keepPhpTag ? 'php' : monacoLanguageFor(props.language)
)

// What leaves the editor (update:code / save): strip the tag unless it should be kept
const outgoing = (content: string) =>
  props.language === 'php' && !props.keepPhpTag ? stripPhpTag(content) : content

// The editor shows the code exactly as stored: functions without `<?php`
// (highlighted by `php-snippet`), files with it (keepPhpTag).
const code = ref(props.code)

watch(() => props.language, (val) => {
  if (val) language.value = val
})

watch(() => props.code, (val) => {
  // Skip when it's just our own emit coming back
  if (val === outgoing(code.value)) return
 
  // A real outside change (e.g. the saved version coming back from the server).
  // Apply only the part that differs as a normal edit. Letting the editor component
  // call setValue() instead would move the cursor to the start and clear undo history.
  const ed = editor.value
  const model = ed?.getModel()
  if (ed && model && window.monaco && model.getValue() !== val) {
    ed.pushUndoStop()
    model.pushEditOperations(ed.getSelections(), [minimalEdit(window.monaco, model, model.getValue(), val)], () => null)
    ed.pushUndoStop()
  }
  code.value = val
})

watch(code, (newCode) => {
  const out = outgoing(newCode)
  // Only report real edits; not the parent's own value coming back in
  // (e.g. switching versions), which would otherwise look like a change.
  if (out !== props.code) emit('update:code', out)
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
  const model = editorInstance.getModel()
  if (!model) return

  const code = model.getValue()

  if (props.language === 'php') {
    validatePhpRemote(code) // async, worker posts markers back
    return
  }

  if (props.language === 'python') {
    validatePythonRemote(code)
    return
  }

  // Python backup - local regex logic 
  // const result = validateCodeLogic(code, 'python', model)
  // monaco.editor.setModelMarkers(model, 'advanced-validation', result.markers)
  // validationErrors.value = result.errors
  // validationWarnings.value = result.warnings
  // emit('validate', result.markers)
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
  emit('save', outgoing(code.value))
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
  phpWorker?.terminate()
  pythonWorker?.terminate()

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

      </div>
    </div>

    <div class="relative flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border" style="min-height: 70vh;">
      <vue-monaco-editor
        v-model:value="code"
        :language="monacoLanguage"
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