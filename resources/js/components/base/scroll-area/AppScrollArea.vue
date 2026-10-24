<script setup lang="ts">
/**
 * @component AppScrollArea
 * @description Native scroll container built on Nuxt UI.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { ScrollAreaProps } from '@nuxt/ui/components/ScrollArea.vue';
import UScrollArea from '@nuxt/ui/components/ScrollArea.vue';
import { useEventListener } from '@vueuse/core';
import { computed, useTemplateRef } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppScrollAreaProps extends ScrollAreaProps {}

/*
 * State.
 */

const props = defineProps<AppScrollAreaProps>();

const forwarded = useForwardedProps(props);

const scrollArea = useTemplateRef<{ $el: HTMLElement }>('scrollArea');

const emit = defineEmits<{
    scroll: [event: Event];
}>();

/*
 * Exposes.
 */

const viewport = computed(
    () => (scrollArea.value?.$el as HTMLElement | undefined) ?? null,
);

// UScrollArea only emits its own scroll state, so the native event is forwarded here.
useEventListener(viewport, 'scroll', event => emit('scroll', event));

defineExpose({ viewport });
</script>

<template>
    <UScrollArea ref="scrollArea" v-bind="forwarded">
        <slot />
    </UScrollArea>
</template>
