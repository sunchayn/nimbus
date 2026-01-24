<script setup lang="ts">
/**
 * @component AppCollapsibleTrigger
 * @description The interactive element that toggles the collapsible state.
 */
import { CollapsibleTrigger, type CollapsibleTriggerProps } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppCollapsibleTriggerProps extends CollapsibleTriggerProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppCollapsibleTriggerProps>();
</script>

<template>
    <CollapsibleTrigger v-bind="props">
        <slot />
    </CollapsibleTrigger>
</template>
