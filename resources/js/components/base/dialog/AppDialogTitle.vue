<script setup lang="ts">
/**
 * @component AppDialogTitle
 * @description Main heading for a dialog.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { DialogTitleProps } from 'reka-ui';
import { DialogTitle, useForwardProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppDialogTitleProps extends DialogTitleProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogTitleProps>();

const delegatedProps = reactiveOmit(props, 'class');

const forwardedProps = useForwardProps(delegatedProps);
</script>

<template>
    <DialogTitle
        data-slot="dialog-title"
        v-bind="forwardedProps"
        :class="cn('text-lg leading-none font-semibold', props.class)"
    >
        <slot />
    </DialogTitle>
</template>
