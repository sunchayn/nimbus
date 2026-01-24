<script setup lang="ts">
/**
 * @component AppDialogFooter
 * @description Footer container for dialog actions.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDialogFooterProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogFooterProps>();
</script>

<template>
    <div
        data-slot="dialog-footer"
        :class="cn('flex flex-col-reverse gap-2 sm:flex-row sm:justify-end', props.class)"
    >
        <slot />
    </div>
</template>
