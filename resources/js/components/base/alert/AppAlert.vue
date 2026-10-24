<script setup lang="ts">
/**
 * @component AppAlert
 * @description A callout built on Nuxt UI for hints, warnings and errors inside content.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { AlertProps, AlertSlots } from '@nuxt/ui/components/Alert.vue';
import UAlert from '@nuxt/ui/components/Alert.vue';

/*
 * Types & Interfaces.
 */

export interface AppAlertProps extends AlertProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppAlertProps>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

defineSlots<AlertSlots>();

const forwarded = useForwardedProps(props);
</script>

<template>
    <UAlert v-bind="forwarded" @update:open="value => emit('update:open', value)">
        <template v-for="(_, name) in $slots" :key="name" #[name]="slotProps">
            <slot :name="name as never" v-bind="slotProps ?? {}" />
        </template>
    </UAlert>
</template>
