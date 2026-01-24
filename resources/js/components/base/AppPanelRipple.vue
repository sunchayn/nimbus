<script setup lang="ts">
/**
 * @component AppPanelRipple
 * @description A decorative ripple effect container used in panel backgrounds.
 */
import AppRipple from '@/components/base/ripple/AppRipple.vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelRippleProps {}
</script>

<template>
    <AppRipple
        class="bottom-0 translate-x-2/5 translate-y-1/2 scale-150 bg-white/5 opacity-25 dark:bg-zinc-950/5"
        circle-class="border-[hsl(var(--primary))] bg-primary/25 blobed"
    />
</template>

<style scoped>
:deep(.blobed) {
    border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
}
</style>
