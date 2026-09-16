<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
    variant?: 'mark' | 'full';
    alt?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'mark',
    alt: 'BITS Proxy',
});

const src = computed(() =>
    props.variant === 'full' ? '/images/bits-logo-full.png' : '/images/bits-logo-mark.png',
);
</script>

<template>
    <img
        :src="src"
        :alt="alt"
        :class="className"
        v-bind="$attrs"
    />
</template>
