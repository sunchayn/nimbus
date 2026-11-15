<script setup lang="ts">
/**
 * @component AppSwitchButton
 * @description A compact switch with its label, drawn as one bordered button.
 * Clicking anywhere on the button toggles the switch.
 */
import { AppSwitch } from '@/components/base/switch';

/*
 * Types & Interfaces.
 */

export interface AppSwitchButtonProps {
    label: string;
}

/*
 * Component Setup.
 */

defineProps<AppSwitchButtonProps>();

const modelValue = defineModel<boolean | undefined>({ default: undefined });
</script>

<template>
    <label
        class="border-default h-control-sm gap-tight px-tight hover:bg-elevated inline-flex cursor-pointer items-center rounded-sm border text-sm"
    >
        <AppSwitch v-model="modelValue" size="sm" :aria-label="label" />
        <span>{{ label }}</span>
    </label>
</template>
