<script setup lang="ts">
/**
 * @component AppScrollBar
 * @description The scrollbar element for the scroll area.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { ScrollAreaScrollbarProps } from 'reka-ui';
import { ScrollAreaScrollbar, ScrollAreaThumb } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppScrollBarProps extends ScrollAreaScrollbarProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppScrollBarProps>(), {
    orientation: 'vertical',
    class: undefined,
});

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <ScrollAreaScrollbar
        data-slot="scroll-area-scrollbar"
        v-bind="delegatedProps"
        :class="
            cn(
                'flex touch-none p-px transition-colors select-none',
                orientation === 'vertical' &&
                    'h-full w-2.5 border-l border-l-transparent',
                orientation === 'horizontal' &&
                    'h-2.5 flex-col border-t border-t-transparent',
                props.class,
            )
        "
    >
        <ScrollAreaThumb
            data-slot="scroll-area-thumb"
            class="relative flex-1 rounded-full bg-gray-400 dark:bg-gray-600"
        />
    </ScrollAreaScrollbar>
</template>
