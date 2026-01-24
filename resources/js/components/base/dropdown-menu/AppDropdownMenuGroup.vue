<script setup lang="ts">
/**
 * @component AppDropdownMenuGroup
 * @description A logical grouping for dropdown menu items.
 */
import { DropdownMenuGroup, type DropdownMenuGroupProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuGroupProps extends DropdownMenuGroupProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuGroupProps>();
</script>

<template>
    <DropdownMenuGroup v-bind="props">
        <slot />
    </DropdownMenuGroup>
</template>
