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

export interface PanelSubHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<PanelSubHeaderProps>();
</script>

<template>
    <div
        :class="
            cn(
                'px-panel min-h-sub-toolbar md:h-sub-toolbar flex items-center justify-between',
                props.class,
            )
        "
    >
        <span class="text-muted text-xs">
            <slot></slot>
        </span>
        <slot name="toolbox"></slot>
    </div>
</template>
