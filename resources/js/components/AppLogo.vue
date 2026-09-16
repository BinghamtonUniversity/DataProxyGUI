<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-12 shrink-0 items-center justify-center overflow-visible group-data-[collapsible=icon]:size-8"
    >
        <AppLogoIcon
            variant="mark"
            class="size-12 object-contain group-data-[collapsible=icon]:size-8"
        />
    </div>
    <div class="ml-2 grid min-w-0 flex-1 text-left group-data-[collapsible=icon]:hidden">
        <span class="truncate text-xl font-extrabold leading-none tracking-[0.18em] text-[#0B3D36] dark:text-emerald-100">
            BITS
        </span>
        <span class="mt-1 truncate text-[0.55rem] font-medium leading-none tracking-[0.12em] uppercase">
            <span class="text-neutral-800 dark:text-neutral-200">Binghamton ITS </span>
            <span class="text-[#1DB985]">Proxy</span>
        </span>
    </div>
</template>
