<script setup lang="ts">
/**
 * @component AppDropdownMenuSub
 * @description Root container for a sub-dropdown menu.
 */
import {
    DropdownMenuSub,
    type DropdownMenuSubEmits,
    type DropdownMenuSubProps,
    useForwardPropsEmits,
} from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuSubProps extends DropdownMenuSubProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuSubProps>();
const emits = defineEmits<DropdownMenuSubEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DropdownMenuSub v-bind="forwarded">
        <slot />
    </DropdownMenuSub>
</template>
