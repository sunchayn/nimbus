<script setup lang="ts">
/**
 * @component CopyButton
 * @description A small button for copying text to the clipboard with status feedback.
 */
import { AppButton } from '@/components/base/button';
import AppSwapIcon from '@/components/base/icons/AppSwapIcon.vue';

/*
 * Types & Interfaces.
 */

export interface CopyButtonProps {
    onClick: () => void;
    copied: boolean;
}

/*
 * Component Setup.
 */

defineProps<CopyButtonProps>();
</script>

<template>
    <AppButton
        size="xs"
        variant="ghost"
        square
        :aria-label="copied ? 'Copied' : 'Copy'"
        :color="copied ? 'success' : undefined"
        @click="onClick"
    >
        <template #leading>
            <AppSwapIcon
                :name="copied ? 'i-hugeicons-tick-02' : 'i-hugeicons-copy-01'"
                class="size-icon-sm"
            />
        </template>
    </AppButton>
</template>
