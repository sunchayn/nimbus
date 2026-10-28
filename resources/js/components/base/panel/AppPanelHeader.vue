<script setup lang="ts">
/**
 * @component AppPanelHeader
 * @description Header container for AppPanel.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelHeaderProps>();
</script>

<template>
    <div :class="cn('px-panel gap-base py-base flex items-center', props.class)">
        <slot />
    </div>
</template>
