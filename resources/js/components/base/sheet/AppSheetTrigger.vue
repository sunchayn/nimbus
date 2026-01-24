<script setup lang="ts">
/**
 * @component AppSheetTrigger
 * @description The interactive element that opens the sheet.
 */
import { DialogTrigger, type DialogTriggerProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppSheetTriggerProps extends DialogTriggerProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSheetTriggerProps>();
</script>

<template>
    <DialogTrigger v-bind="props">
        <slot />
    </DialogTrigger>
</template>
