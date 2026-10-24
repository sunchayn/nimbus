<script setup lang="ts">
/**
 * @component AppPanel
 * @description A dense container component for panel-based layouts.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelProps>();
</script>

<template>
    <div :class="cn('bg-default text-default flex flex-col', props.class)">
        <slot />
    </div>
</template>
