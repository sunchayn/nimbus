<script setup lang="ts">
/**
 * @component AppResizablePanel
 * @description An individual panel within a resizable group.
 */
import type { SplitterPanelEmits, SplitterPanelProps } from 'reka-ui';
import { SplitterPanel, useForwardPropsEmits } from 'reka-ui';

/*
 * Types & Interfaces.
 */

export interface AppResizablePanelProps extends SplitterPanelProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppResizablePanelProps>();
const emits = defineEmits<SplitterPanelEmits>();

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <SplitterPanel data-slot="resizable-panel" v-bind="forwarded">
        <slot />
    </SplitterPanel>
</template>
