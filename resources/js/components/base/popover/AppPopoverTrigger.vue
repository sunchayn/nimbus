<script setup lang="ts">
/**
 * @component AppPopoverTrigger
 * @description The interactive element that opens the popover.
 */
import type { PopoverTriggerProps } from 'reka-ui';
import { PopoverTrigger } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppPopoverTriggerProps extends PopoverTriggerProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppPopoverTriggerProps>();
</script>

<template>
    <PopoverTrigger data-slot="popover-trigger" v-bind="props">
        <slot />
    </PopoverTrigger>
</template>
