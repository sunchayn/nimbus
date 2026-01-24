<script setup lang="ts">
/**
 * @component AppTableBody
 * @description The container for the table's main content rows.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTableBodyProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTableBodyProps>();
</script>

<template>
    <tbody :class="cn('[&_tr:last-child]:border-0', props.class)">
        <slot />
    </tbody>
</template>
