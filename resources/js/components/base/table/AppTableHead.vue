<script setup lang="ts">
/**
 * @component AppTableHead
 * @description A header cell for defining column titles.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTableHeadProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTableHeadProps>();
</script>

<template>
    <th
        :class="
            cn(
                'text-subtle h-10 px-2 text-left align-middle font-medium [&:has([role=checkbox])]:pr-0 [&>[role=checkbox]]:translate-y-0.5',
                props.class,
            )
        "
    >
        <slot />
    </th>
</template>
