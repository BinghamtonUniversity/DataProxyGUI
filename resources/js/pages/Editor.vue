<script setup lang="ts">import { type BreadcrumbItem } from '@/types'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import { ref, shallowRef, watch, toRaw } from 'vue'
import { Button } from '@/components/ui/button'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Editor', href: '/editor' },
]

const props = defineProps<{
  code: string
  language?: 'python' | 'php'
  isSaving?: boolean
}>()

const emit = defineEmits<{
  save: [code: string]
}>()

const language = ref(props.language ?? 'python')
const code = ref(props.code)
const hasUnsavedChanges = ref(false)

watch(() => props.code, (val) => { 
  code.value = val
  hasUnsavedChanges.value = false
})
watch(() => props.language, (val) => { 
  if (val) language.value = val 
})

// Track changes to show unsaved status
watch(code, (newCode) => {
  hasUnsavedChanges.value = newCode !== props.code
})

declare global {
  interface Window {
    monaco: typeof import('monaco-editor');
  }
}

const editor = shallowRef<any>(null);

const editorOptions = {
  automaticLayout: true,
  formatOnType: true,
  formatOnPaste: true,
}

function handleMount(editorInstance: any, monaco: any) {
  editor.value = editorInstance
  
  // ??Add keyboard shortcut for save (Ctrl+S / Cmd+S)
  editorInstance.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => {
    handleSave()
  })
}

const handleSave = () => {
  if (props.isSaving) return
  emit('save', code.value)
}

// const formatCode = () => {
//   editor.value?.getAction('editor.action.formatDocument').run()
// }
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
          :disabled="props.isSaving || !hasUnsavedChanges"
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
        theme="vs-dark"
        :options="editorOptions"
        @mount="handleMount"
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