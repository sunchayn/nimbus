<script setup lang="ts">
/**
 * @component AppDropdownMenuTrigger
 * @description The interactive element that opens the dropdown menu.
 */
import {
    DropdownMenuTrigger,
    type DropdownMenuTriggerProps,
    useForwardProps,
} from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuTriggerProps extends DropdownMenuTriggerProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuTriggerProps>();

const forwardedProps = useForwardProps(props);
</script>

<template>
    <DropdownMenuTrigger class="outline-none" v-bind="forwardedProps">
        <slot />
    </DropdownMenuTrigger>
</template>
