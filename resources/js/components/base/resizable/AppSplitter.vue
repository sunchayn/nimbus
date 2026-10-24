<script setup lang="ts">
/**
 * @component AppSplitter
 * @description Resizable panel group. Pass `items` and fill the `panel-N` slots.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { SplitterProps, SplitterSlots } from '@nuxt/ui/components/Splitter.vue';
import USplitter from '@nuxt/ui/components/Splitter.vue';
import { useTemplateRef } from 'vue';

/*
 * Component Setup.
 */

interface SplitterPanelHandle {
    collapse: () => void;
    expand: () => void;
}

const props = defineProps<SplitterProps>();

const emit = defineEmits<{
    layout: [sizes: number[]];
    collapse: [index: number];
    expand: [index: number];
    resize: [index: number, size: number, prevSize?: number];
    dragging: [index: number, dragging: boolean];
}>();

defineSlots<SplitterSlots>();

const forwarded = useForwardedProps(props);

const splitter = useTemplateRef<{ panelsRef: SplitterPanelHandle[] }>('splitter');

defineExpose({
    collapse: (index: number) => splitter.value?.panelsRef[index]?.collapse(),
    expand: (index: number) => splitter.value?.panelsRef[index]?.expand(),
});
</script>

<template>
    <USplitter
        ref="splitter"
        v-bind="forwarded"
        @layout="sizes => emit('layout', sizes)"
        @collapse="index => emit('collapse', index)"
        @expand="index => emit('expand', index)"
        @resize="(index, size, prevSize) => emit('resize', index, size, prevSize)"
        @dragging="(index, dragging) => emit('dragging', index, dragging)"
    >
        <template v-for="(_, name) in $slots" #[name]="slotProps" :key="name">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </USplitter>
</template>
