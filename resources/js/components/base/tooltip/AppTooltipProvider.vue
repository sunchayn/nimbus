<script setup lang="ts">
/**
 * @component AppTooltipProvider
 * @description Global context provider for managing tooltips, primarily used to set global delays.
 */
import { TooltipProvider, type TooltipProviderProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppTooltipProviderProps extends TooltipProviderProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppTooltipProviderProps>();
</script>

<template>
    <TooltipProvider v-bind="props">
        <slot />
    </TooltipProvider>
</template>
