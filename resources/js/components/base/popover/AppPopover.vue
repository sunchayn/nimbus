<script setup lang="ts">
/**
 * @component AppPopover
 * @description Root container for a popover menu.
 */
import type { PopoverRootEmits, PopoverRootProps } from 'reka-ui';
import { PopoverRoot, useForwardPropsEmits } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppPopoverProps extends PopoverRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppPopoverProps>();
const emits = defineEmits<PopoverRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <PopoverRoot data-slot="popover" v-bind="forwarded">
        <slot />
    </PopoverRoot>
</template>
