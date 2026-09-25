<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'
import { VueMonacoDiffEditor } from '@guolao/vue-monaco-editor'

const props = withDefaults(
    defineProps<{
        original: string
        modified: string
        language?: string
        sideBySide?: boolean
        height?: string
    }>(),
    { language: 'plaintext', sideBySide: true, height: '70vh' },
)


const isDark = ref(document.documentElement.classList.contains('dark'))
let observer: MutationObserver | null = null
onMounted(() => {
    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark')
    })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})


// Detach the models first (this hook runs before the child's onUnmounted)
const diffEditor = shallowRef<any>(null)
const handleMount = (editor: any) => {
    diffEditor.value = editor
}

onBeforeUnmount(() => {
    observer?.disconnect()
    const editor = diffEditor.value
    if (!editor) return
    const model = editor.getModel()
    editor.setModel(null)
    model?.original?.dispose()
    model?.modified?.dispose()
    diffEditor.value = null
})

const options = computed(() => ({
    readOnly: true,
    originalEditable: false,
    renderSideBySide: props.sideBySide,
    useInlineViewWhenSpaceIsLimited: true,
    automaticLayout: true,
    scrollBeyondLastLine: false,
    minimap: { enabled: false },
    // Collapse unchanged blocks like GitHub (monaco-editor >= 0.41)
    hideUnchangedRegions: { enabled: true, contextLineCount: 3, minimumLineCount: 5 },
    ignoreTrimWhitespace: false,
}))
</script>

<template>
    <VueMonacoDiffEditor
        :original="original"
        :modified="modified"
        :language="language"
        :theme="isDark ? 'vs-dark' : 'vs'"
        :options="options"
        :height="height"
        @mount="handleMount"
    />
</template>