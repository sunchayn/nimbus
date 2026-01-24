<script setup lang="ts">
/**
 * @component AppDialogOverlay
 * @description The semi-transparent backdrop for a modal dialog.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { DialogOverlayProps } from 'reka-ui';
import { DialogOverlay } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDialogOverlayProps extends DialogOverlayProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogOverlayProps>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
    <DialogOverlay
        data-slot="dialog-overlay"
        v-bind="delegatedProps"
        :class="
            cn(
                'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/80',
                props.class,
            )
        "
    >
        <slot />
    </DialogOverlay>
</template>
