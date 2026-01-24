<script setup lang="ts">
/**
 * @component AppCollapsible
 * @description An interactive component that expands/collapses content.
 */
import type { CollapsibleRootEmits, CollapsibleRootProps } from 'reka-ui';
import { CollapsibleRoot, useForwardPropsEmits } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppCollapsibleProps extends CollapsibleRootProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppCollapsibleProps>();
const emits = defineEmits<CollapsibleRootEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <!-- eslint-disable-next-line vue/no-template-shadow -->
    <CollapsibleRoot v-slot="{ open }" v-bind="forwarded">
        <slot :open="open" />
    </CollapsibleRoot>
</template>
