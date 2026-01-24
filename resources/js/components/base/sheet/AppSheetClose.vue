<script setup lang="ts">
/**
 * @component AppSheetClose
 * @description The element that closes the sheet.
 */
import { DialogClose, type DialogCloseProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppSheetCloseProps extends DialogCloseProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetCloseProps>();
</script>

<template>
    <DialogClose v-bind="props">
        <slot />
    </DialogClose>
</template>
