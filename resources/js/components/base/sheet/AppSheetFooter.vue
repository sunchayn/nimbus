<script setup lang="ts">
/**
 * @component AppSheetFooter
 * @description Footer container for a sheet, typically containing action buttons.
 */
import { cn } from '@/utils/ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppSheetFooterProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetFooterProps>();
</script>

<template>
    <div
        :class="
            cn('flex flex-col-reverse sm:flex-row sm:justify-end sm:gap-x-2', props.class)
        "
    >
        <slot />
    </div>
</template>
