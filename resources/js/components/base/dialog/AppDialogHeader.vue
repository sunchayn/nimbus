<script setup lang="ts">
/**
 * @component AppDialogHeader
 * @description Header container for dialog title and description.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDialogHeaderProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogHeaderProps>();
</script>

<template>
    <div
        data-slot="dialog-header"
        :class="cn('flex flex-col gap-2 text-center sm:text-left', props.class)"
    >
        <slot />
    </div>
</template>
