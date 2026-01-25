<script setup lang="ts">
/**
 * @component AppPanelContent
 * @description Main content area for AppPanel.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelContentProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelContentProps>();
</script>

<template>
    <div :class="cn('px-panel py-2', props.class)">
        <slot />
    </div>
</template>
