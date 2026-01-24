<script setup lang="ts">
/**
 * @component AppSelectValue
 * @description Displays the currently selected value in the select trigger.
 */
import { SelectValue, type SelectValueProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppSelectValueProps extends SelectValueProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSelectValueProps>();
</script>

<template>
    <SelectValue v-bind="props">
        <slot />
    </SelectValue>
</template>
