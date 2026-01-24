<script setup lang="ts">
/**
 * @component AppDropdownMenuRadioGroup
 * @description A group for managing mutually exclusive dropdown menu items.
 */
import {
    DropdownMenuRadioGroup,
    type DropdownMenuRadioGroupEmits,
    type DropdownMenuRadioGroupProps,
    useForwardPropsEmits,
} from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuRadioGroupProps extends DropdownMenuRadioGroupProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuRadioGroupProps>();
const emits = defineEmits<DropdownMenuRadioGroupEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DropdownMenuRadioGroup v-bind="forwarded">
        <slot />
    </DropdownMenuRadioGroup>
</template>
