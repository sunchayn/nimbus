<script setup lang="ts">
/**
 * @component AppTooltip
 * @description Root container for a tooltip component.
 */
import {
    TooltipRoot,
    type TooltipRootEmits,
    type TooltipRootProps,
    useForwardPropsEmits,
} from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppTooltipProps extends TooltipRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppTooltipProps>();
const emits = defineEmits<TooltipRootEmits>();

/*
 * Computed & Methods.
 */

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TooltipRoot v-bind="forwarded">
        <slot />
    </TooltipRoot>
</template>
