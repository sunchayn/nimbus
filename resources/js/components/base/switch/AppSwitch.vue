<script setup lang="ts">
/**
 * @component AppSwitch
 * @description Toggle switch built on Nuxt UI.
 */
import { useForwardedProps } from '@/components/base/useForwardedProps';
import type { SwitchProps } from '@nuxt/ui/components/Switch.vue';
import USwitch from '@nuxt/ui/components/Switch.vue';

/*
 * Types & Interfaces.
 */

export interface AppSwitchProps extends Omit<SwitchProps, 'modelValue'> {}

/*
 * Component Setup.
 */

const props = defineProps<AppSwitchProps>();

const modelValue = defineModel<boolean | undefined>({ default: undefined });

const forwarded = useForwardedProps(props);
</script>

<template>
    <USwitch v-bind="forwarded" v-model="modelValue" />
</template>
