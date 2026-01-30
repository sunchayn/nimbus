<script setup lang="ts">
/**
 * @component AppResizablePanel
 * @description An individual panel within a resizable group.
 */
import type { SplitterPanelEmits, SplitterPanelProps } from 'reka-ui';
import { SplitterPanel, useForwardPropsEmits } from 'reka-ui';
import { ref } from 'vue';

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

const splitterPanelRef = ref<InstanceType<typeof SplitterPanel> | null>(null);

defineExpose({
    collapse: () => splitterPanelRef.value?.collapse(),
    expand: () => splitterPanelRef.value?.expand(),
});
</script>

<template>
    <SplitterPanel ref="splitterPanelRef" data-slot="resizable-panel" v-bind="forwarded">
        <slot />
    </SplitterPanel>
</template>
