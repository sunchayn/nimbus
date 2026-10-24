<script setup lang="ts">
/**
 * @component AppTooltip
 * @description A tooltip built on Nuxt UI. The default slot is the trigger.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { TooltipProps, TooltipSlots } from '@nuxt/ui/components/Tooltip.vue';
import UTooltip from '@nuxt/ui/components/Tooltip.vue';

/*
 * Types & Interfaces.
 */

export interface AppTooltipProps extends TooltipProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppTooltipProps>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

defineSlots<TooltipSlots>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UTooltip v-bind="forwarded" @update:open="value => emit('update:open', value)">
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UTooltip>
</template>
