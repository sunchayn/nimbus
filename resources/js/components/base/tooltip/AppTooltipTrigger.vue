<script setup lang="ts">
/**
 * @component AppTooltipTrigger
 * @description The interactive element that receives focus or hover to display a tooltip.
 */
import { TooltipTrigger, type TooltipTriggerProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppTooltipTriggerProps extends TooltipTriggerProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppTooltipTriggerProps>();
</script>

<template>
    <TooltipTrigger v-bind="props">
        <slot />
    </TooltipTrigger>
</template>
