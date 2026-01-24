<script setup lang="ts">
/**
 * @component AppTableHeader
 * @description The container for the table's header rows.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppTableHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppTableHeaderProps>();
</script>

<template>
    <thead :class="cn('[&_tr]:border-b', props.class)">
        <slot />
    </thead>
</template>
