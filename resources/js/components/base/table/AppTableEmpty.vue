<script setup lang="ts">
/**
 * @component AppTableEmpty
 * @description A specialized table row for displaying empty states across all columns.
 */
import { cn } from '@/utils/ui';
import { computed, type HTMLAttributes } from 'vue';
import TableCell from './AppTableCell.vue';
import TableRow from './AppTableRow.vue';

/*
 * Types & Interfaces.
 */

export interface AppTableEmptyProps {
    class?: HTMLAttributes['class'];
    colspan?: number;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<AppTableEmptyProps>(), {
    colspan: 1,
    class: '',
});

/*
 * Computed & Methods.
 */

const delegatedProps = computed(() => {
    const { class: _, ...delegated } = props;

    return delegated;
});
</script>

<template>
    <TableRow>
        <TableCell
            :class="
                cn(
                    'p-4 align-middle text-sm whitespace-nowrap text-zinc-950 dark:text-zinc-50',
                    props.class,
                )
            "
            v-bind="delegatedProps"
        >
            <div class="flex items-center justify-center py-10">
                <slot />
            </div>
        </TableCell>
    </TableRow>
</template>
