<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
    defineProps<{ additions: number; deletions: number; showBar?: boolean }>(),
    { showBar: true },
)

// GitHub-style 5-block bar: green share, red share, grey remainder
const blocks = computed(() => {
    const total = props.additions + props.deletions
    if (!total) return Array(5).fill('neutral')
    const green = Math.round((props.additions / total) * 5)
    const red = Math.min(5 - green, Math.round((props.deletions / total) * 5))
    return [
        ...Array(green).fill('add'),
        ...Array(red).fill('del'),
        ...Array(5 - green - red).fill('neutral'),
    ]
})
</script>

<template>
    <span class="inline-flex items-center gap-2 text-xs font-medium tabular-nums">
        <span class="text-green-600 dark:text-green-400">+{{ additions }}</span>
        <span class="text-red-600 dark:text-red-400">−{{ deletions }}</span>
        <span v-if="showBar" class="inline-flex gap-px" aria-hidden="true">
            <span
                v-for="(b, i) in blocks"
                :key="i"
                class="h-2 w-2 rounded-[1px]"
                :class="{
                    'bg-green-500': b === 'add',
                    'bg-red-500': b === 'del',
                    'bg-gray-300 dark:bg-gray-600': b === 'neutral',
                }"
            />
        </span>
    </span>
</template>