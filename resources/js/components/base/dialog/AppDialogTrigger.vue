<script setup lang="ts">
/**
 * @component AppDialogTrigger
 * @description The interactive element that opens the dialog.
 */
import type { DialogTriggerProps } from 'reka-ui';
import { DialogTrigger } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDialogTriggerProps extends DialogTriggerProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogTriggerProps>();
</script>

<template>
    <DialogTrigger data-slot="dialog-trigger" v-bind="props">
        <slot />
    </DialogTrigger>
</template>
