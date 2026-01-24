<script setup lang="ts">
/**
 * @component AppDropdownMenu
 * @description Root container for a dropdown menu.
 */
import {
    DropdownMenuRoot,
    type DropdownMenuRootEmits,
    type DropdownMenuRootProps,
    useForwardPropsEmits,
} from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppDropdownMenuProps extends DropdownMenuRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppDropdownMenuProps>();
const emits = defineEmits<DropdownMenuRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <DropdownMenuRoot v-bind="forwarded">
        <slot />
    </DropdownMenuRoot>
</template>
