<script setup lang="ts">
/**
 * @component AppScrollArea
 * @description A custom-styled scrollable container using radix-ui primitives.
 */
import { cn } from '@/utils/ui';
import { reactiveOmit } from '@vueuse/core';
import type { ScrollAreaRootProps } from 'reka-ui';
import { ScrollAreaCorner, ScrollAreaRoot, ScrollAreaViewport } from 'reka-ui';
import { type HTMLAttributes, ref } from 'vue';
import AppScrollBar from './AppScrollBar.vue';

/*
 * Types & Interfaces.
 */

export interface AppScrollAreaProps extends ScrollAreaRootProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppScrollAreaProps>();
const delegatedProps = reactiveOmit(props, 'class', 'type');

const emit = defineEmits<{
    scroll: [event: Event];
}>();

const viewportComponent = ref<InstanceType<typeof ScrollAreaViewport> | null>(null);

defineExpose({
    get viewport() {
        return viewportComponent.value?.viewportElement ?? null;
    },
});
</script>

<template>
    <ScrollAreaRoot
        type="hover"
        data-slot="scroll-area"
        v-bind="delegatedProps"
        :class="cn('relative', props.class)"
    >
        <ScrollAreaViewport
            ref="viewportComponent"
            :as-child="true"
            data-slot="scroll-area-viewport"
            class="focus-visible:ring-ring/50 size-full rounded-[inherit] transition-[color,box-shadow] outline-none focus-visible:ring-[3px] focus-visible:outline-1 [&>div]:h-full"
            @scroll="event => emit('scroll', event)"
        >
            <slot />
        </ScrollAreaViewport>
        <AppScrollBar class="z-[50]" />
        <ScrollAreaCorner />
    </ScrollAreaRoot>
</template>
