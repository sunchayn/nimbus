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
                'pl-panel pr-tight py-tight min-h-sub-toolbar md:h-sub-toolbar flex items-center justify-between',
                props.class,
            )
        "
    >
        <span class="text-muted text-xs">
            <slot></slot>
        </span>
        <div v-if="$slots.toolbox" class="sub-toolbox flex items-center self-stretch">
            <slot name="toolbox"></slot>
        </div>
    </div>
</template>
