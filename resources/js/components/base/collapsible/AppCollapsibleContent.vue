<script setup lang="ts">
/**
 * @component AppCollapsibleContent
 * @description The content area that expands/collapses within a collapsible.
 */
import { CollapsibleContent, type CollapsibleContentProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppCollapsibleContentProps extends CollapsibleContentProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppCollapsibleContentProps>();
</script>

<template>
    <CollapsibleContent
        v-bind="props"
        class="data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down transition-all"
    >
        <slot />
    </CollapsibleContent>
</template>
