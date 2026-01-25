<script setup lang="ts">
/**
 * @component AppDialogDescription
 * @description Supporting text for a dialog title.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { DialogDescriptionProps } from 'reka-ui';
import { DialogDescription, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDialogDescriptionProps extends DialogDescriptionProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogDescriptionProps>();

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <DialogDescription
        data-slot="dialog-description"
        v-bind="forwardedProps"
        :class="cn('text-subtle-foreground text-sm', props.class)"
    >
        <slot />
    </DialogDescription>
</template>
