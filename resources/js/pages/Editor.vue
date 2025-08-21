<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import { ref, shallowRef, watch, toRaw } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Editor', href: '/editor' },
]

const props = defineProps<{
  code: string
  language?: 'python' | 'php'
}>()

const language = ref(props.language ?? 'python')
const code = ref(props.code)

watch(() => props.code, (val) => { code.value = val })
watch(() => props.language, (val) => { if (val) language.value = val })

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
}

// Switch languages and update model
// watch(language, (lang) => {
//   const inst = editor.value
//   if (!inst) return
//   const model = toRaw(inst).getModel()
//   inst?.getModel() &&
//     (window.monaco.editor.setModelLanguage(model!, lang))
//   code.value = lang === 'python'
//     ? `print("Hello, Python!")`
//     : `<?php echo "Hello, PHP!"; ?>`
// })
</script>

<template>
  
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="flex items-center gap-2">
        <label for="lang-select">Language:</label>
        <select id="lang-select" v-model="props.language" class="border rounded p-1" disabled>
          <option value="python">Python</option>
          <option value="php">PHP</option>
        </select>
        <!-- <button
          class="ml-auto btn"
          @click="editor?.getAction('editor.action.formatDocument').run()"
        >
          Format
        </button> -->
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
    </div>
 
</template>
