<script setup lang="ts">
/**
 * @component AppSonner
 * @description A toast notification container using vue-sonner.
 */
import type { ToasterProps } from 'vue-sonner';
import { Toaster as Sonner } from 'vue-sonner';

defineOptions({
    name: 'AppSonner',
});

/*
 * Types & Interfaces.
 */

export interface AppSonnerProps extends ToasterProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSonnerProps>();
</script>

<template>
    <Sonner
        class="toaster group"
        v-bind="props"
        :style="{
            '--normal-bg': 'var(--color-popover)',
            '--normal-text': 'var(--color-popover-foreground)',
            '--normal-border': 'var(--color-border)',
        }"
    />
</template>
