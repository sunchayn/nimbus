<script setup lang="ts">
/**
 * @component AppLabel
 * @description Plain form label with consistent typography.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes, LabelHTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppLabelProps {
    for?: LabelHTMLAttributes['for'];
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppLabelProps>();
</script>

<template>
    <label
        :for="props.for"
        :class="cn('text-default text-sm leading-none font-medium', props.class)"
    >
        <slot />
    </label>
</template>
