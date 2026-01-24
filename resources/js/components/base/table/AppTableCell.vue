<script setup lang="ts">
/**
 * @component AppTableCell
 * @description An individual data cell within a table row.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTableCellProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTableCellProps>();
</script>

<template>
    <td
        :class="
            cn(
                'p-2 align-middle [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-0.5',
                props.class,
            )
        "
    >
        <slot />
    </td>
</template>
