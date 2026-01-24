<script setup lang="ts">
/**
 * @component CopyButton
 * @description A small button for copying text to the clipboard with status feedback.
 */
import { AppButton } from '@/components/base/button';
import { CheckIcon, CopyIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppCopyButtonProps {
    onClick: () => void;
    copied: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<AppCopyButtonProps>();
</script>

<template>
    <AppButton size="xs" variant="ghost" class="size-6" @click="onClick">
        <CopyIcon v-if="!copied" class="size-3" />
        <CheckIcon v-else class="size-3 text-green-800 dark:text-green-500" />
    </AppButton>
</template>
