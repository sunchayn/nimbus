<script setup lang="ts">
/**
 * @component AppDialogClose
 * @description An interactive element that closes the dialog.
 */
import type { DialogCloseProps } from 'reka-ui';
import { DialogClose } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDialogCloseProps extends DialogCloseProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDialogCloseProps>();
</script>

<template>
    <DialogClose data-slot="dialog-close" v-bind="props">
        <slot />
    </DialogClose>
</template>
