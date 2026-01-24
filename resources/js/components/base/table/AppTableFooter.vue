<script setup lang="ts">
/**
 * @component AppTableFooter
 * @description The footer container for table summary rows.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTableFooterProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTableFooterProps>();
</script>

<template>
    <tfoot
        :class="
            cn(
                'border-t bg-zinc-100/50 font-medium dark:bg-zinc-800/50 [&>tr]:last:border-b-0',
                props.class,
            )
        "
    >
        <slot />
    </tfoot>
</template>
