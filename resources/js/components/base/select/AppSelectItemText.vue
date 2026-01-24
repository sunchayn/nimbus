<script setup lang="ts">
/**
 * @component AppSelectItemText
 * @description The text content within a select item.
 */
import { SelectItemText, type SelectItemTextProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppSelectItemTextProps extends SelectItemTextProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectItemTextProps>();
</script>

<template>
    <SelectItemText v-bind="props">
        <slot />
    </SelectItemText>
</template>
