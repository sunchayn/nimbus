<script setup lang="ts">
/**
 * @component AppTableCaption
 * @description A caption or summary for the table content.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTableCaptionProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTableCaptionProps>();
</script>

<template>
    <caption :class="cn('text-subtle-foreground mt-4 text-sm', props.class)">
        <slot />
    </caption>
</template>
