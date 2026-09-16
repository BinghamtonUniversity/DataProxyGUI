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

const lightSrc = computed(() =>
    props.variant === 'full' ? '/images/bits-logo-full.png' : '/images/bits-logo-mark.png',
);

const darkSrc = computed(() =>
    props.variant === 'full' ? '/images/bits-logo-full.png' : '/images/bits-logo-mark-dark.png',
);

const useDarkMark = computed(() => props.variant === 'mark');
</script>

<template>
    <template v-if="useDarkMark">
        <img
            :src="lightSrc"
            :alt="alt"
            :class="[className, 'dark:hidden']"
            v-bind="$attrs"
        />
        <img
            :src="darkSrc"
            :alt="alt"
            :class="[className, 'hidden dark:block']"
            v-bind="$attrs"
        />
    </template>
    <img
        v-else
        :src="lightSrc"
        :alt="alt"
        :class="className"
        v-bind="$attrs"
    />
</template>
