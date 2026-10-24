<script setup lang="ts" generic="M extends 'click' | 'hover' = 'click' | 'hover'">
/**
 * @component AppPopover
 * @description A popover built on Nuxt UI. The default slot is the trigger and `#anchor` anchors it without a trigger.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { PopoverProps, PopoverSlots } from '@nuxt/ui/components/Popover.vue';
import UPopover from '@nuxt/ui/components/Popover.vue';

/*
 * Component Setup.
 */

const props = defineProps<PopoverProps<M>>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'close:prevent': [];
}>();

defineSlots<PopoverSlots<M>>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UPopover
        v-bind="forwarded"
        @update:open="value => emit('update:open', value)"
        @close:prevent="emit('close:prevent')"
    >
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UPopover>
</template>
