<script setup lang="ts">
/**
 * @component AppPanelTitle
 * @description Heading for an AppPanel.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelTitleProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelTitleProps>();
</script>

<template>
    <h3 :class="cn('text-sm leading-none font-semibold tracking-tight', props.class)">
        <slot />
    </h3>
</template>
