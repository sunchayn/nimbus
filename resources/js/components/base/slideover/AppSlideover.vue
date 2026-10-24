<script setup lang="ts">
/**
 * @component AppSlideover
 * @description A panel that slides in from a screen edge, built on Nuxt UI. The default slot is the trigger.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { SlideoverProps, SlideoverSlots } from '@nuxt/ui/components/Slideover.vue';
import USlideover from '@nuxt/ui/components/Slideover.vue';

/*
 * Types & Interfaces.
 */

export interface AppSlideoverProps extends SlideoverProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppSlideoverProps>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'after:enter': [];
    'after:leave': [];
    'close:prevent': [];
}>();

defineSlots<SlideoverSlots>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <USlideover
        v-bind="forwarded"
        @update:open="value => emit('update:open', value)"
        @after:enter="emit('after:enter')"
        @after:leave="emit('after:leave')"
        @close:prevent="emit('close:prevent')"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </USlideover>
</template>
