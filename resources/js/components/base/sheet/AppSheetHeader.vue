<script setup lang="ts">
/**
 * @component AppSheetHeader
 * @description Header container for a sheet, typically containing title and description.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSheetHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetHeaderProps>();
</script>

<template>
    <div :class="cn('flex flex-col gap-y-2 text-center sm:text-left', props.class)">
        <slot />
    </div>
</template>
