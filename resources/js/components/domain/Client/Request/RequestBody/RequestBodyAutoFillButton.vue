<script setup lang="ts">
/**
 * @component RequestBodyAutoFillButton
 * @description A glowing button that triggers the autofill functionality for request bodies.
 */
import AppGlowingButton from '@/components/base/button/AppGlowingButton.vue';
import { WandSparklesIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppRequestBodyAutoFillButtonProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestBodyAutoFillButtonProps>();
</script>

<template>
    <AppGlowingButton>
        <WandSparklesIcon
            class="transition-all will-change-transform group-hover:rotate-12"
        />
        Auto Fill
    </AppGlowingButton>
</template>
