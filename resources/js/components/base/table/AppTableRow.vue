<script setup lang="ts">
/**
 * @component AppTableRow
 * @description A row container within a table.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTableRowProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTableRowProps>();
</script>

<template>
    <tr
        :class="
            cn(
                'border-b transition-colors hover:bg-zinc-100/50 data-[state=selected]:bg-zinc-100 dark:hover:bg-zinc-800/50 dark:data-[state=selected]:bg-zinc-800',
                props.class,
            )
        "
    >
        <slot />
    </tr>
</template>
