<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { type BreadcrumbItem } from '@/types'
import { Head } from '@inertiajs/vue3'
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'
import { ref, shallowRef, watch, toRaw, onMounted,  onBeforeUnmount } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Editor', href: '/editor' },
]

const language = ref<'python' | 'php'>('python')
const code = ref<string>(
  language.value === 'python'
    ? `print("Hello, Python!")`
    : `<?php echo "Hello, PHP!"; ?>`
)

declare global {
  interface Window {
    monaco: typeof import('monaco-editor');
  }
}

const editor = shallowRef<any>(null);
const editorTheme = ref<"vs" | "vs-dark">("vs-dark")
let mediaQueryList: MediaQueryList | null = null

const editorOptions = {
  automaticLayout: true,
  formatOnType: true,
  formatOnPaste: true,
}

function getCookie(name: String){
  const nameEQ = name + "="
  const cookie_arr = document.cookie.split(';')//array of each application cookie (format: <name>=<value>) ignore <>

  for(let i = 0; i < cookie_arr.length; i++){
    let cookie = cookie_arr[i];
    while(cookie.charAt(0) === ' '){//remove any qhite space before name (splitting into array causes white space for every cookie thats not cookie_arr[0])
      cookie = cookie.substring(1, cookie.length)
    }
    if(cookie.indexOf(nameEQ) === 0){ return cookie.substring(nameEQ.length, cookie.length) }
  }
  return null;
}
function handleEditorTheme(){
  let theme = getCookie("appearance") ?? "dark"//default to dark if cannot get cookie

  if(theme === "system"){//resolve system preference
    mediaQueryList = window.matchMedia("(prefers-color-scheme: dark)")
    theme = mediaQueryList.matches ? "dark" : "light"
  }

  editorTheme.value = theme === "light" ? "vs" : "vs-dark" //map appearance to Monaco themes
}

function handleMount(editorInstance: any, monaco: any) {
  editor.value = editorInstance
}

// Switch languages and update model
watch(language, (lang) => {
  const inst = editor.value
  if (!inst) return
  const model = toRaw(inst).getModel()
  inst?.getModel() &&
    (window.monaco.editor.setModelLanguage(model!, lang))
  code.value = lang === 'python'
    ? `print("Hello, Python!")`
    : `<?php echo "Hello, PHP!"; ?>`
})
//listener for system theme
onMounted(() => {
  handleEditorTheme()
  if(getCookie("appearance") === "system"){//if system is theme watch live browser changes
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
  <Head title="Editor" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
      <div class="flex items-center gap-2">
        <label for="lang-select">Language:</label>
        <select id="lang-select" v-model="language" class="border rounded p-1">
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
          :language="language"
          :theme="editorTheme"
          :options="editorOptions"
          @mount="handleMount"
          style="height:100%; width:100%;"
        />
      </div>
    </div>
  </AppLayout>
</template>
