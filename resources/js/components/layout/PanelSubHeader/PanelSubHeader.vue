<script setup lang="ts">
/**
 * @component PanelSubHeader
 * @description A consistent header for internal panels, typically containing a title and a toolbox.
 */
import { cn } from '@/utils/ui';
import { type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppPanelSubHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppPanelSubHeaderProps>();
</script>

<template>
    <div
        :class="
            cn('px-panel h-sub-toolbar flex items-center justify-between', props.class)
        "
    >
        <span class="text-muted-foreground text-xs">
            <slot></slot>
        </span>
        <slot name="toolbox"></slot>
    </div>
</template>
