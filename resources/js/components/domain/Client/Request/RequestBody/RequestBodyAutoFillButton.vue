<script setup lang="ts">
/**
 * @component RequestBodyAutoFillButton
 * @description A glowing button that triggers the autofill functionality for request bodies.
 */
import AppGlowingButton from '@/components/base/button/AppGlowingButton.vue';

/*
 * Types & Interfaces.
 */

export interface RequestBodyAutoFillButtonProps {}

/*
 * Component Setup.
 */

defineProps<RequestBodyAutoFillButtonProps>();
</script>

<template>
    <AppGlowingButton
        size="sm"
        icon="i-hugeicons-magic-wand-01"
        label="Auto Fill"
        :ui="{ leadingIcon: 'transition-transform group-hover:rotate-12' }"
    />
</template>
