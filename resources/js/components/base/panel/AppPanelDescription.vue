<script setup lang="ts">
/**
 * @component AppPanelDescription
 * @description Description text for an AppPanel.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelDescriptionProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelDescriptionProps>();
</script>

<template>
    <p :class="cn('text-subtle-foreground text-sm', props.class)">
        <slot />
    </p>
</template>
