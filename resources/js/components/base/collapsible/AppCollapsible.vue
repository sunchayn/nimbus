<script setup lang="ts">
/**
 * @component AppCollapsible
 * @description A collapsible built on Nuxt UI. The default slot is the trigger and `#content` is the body.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type {
    CollapsibleProps,
    CollapsibleSlots,
} from '@nuxt/ui/components/Collapsible.vue';
import UCollapsible from '@nuxt/ui/components/Collapsible.vue';

/*
 * Types & Interfaces.
 */

export interface AppCollapsibleProps extends CollapsibleProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppCollapsibleProps>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

defineSlots<CollapsibleSlots>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UCollapsible v-bind="forwarded" @update:open="value => emit('update:open', value)">
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UCollapsible>
</template>
