<script setup lang="ts">
/**
 * @component ValueGeneratorFooter
 * @description The footer for the value generator command, showing shortcuts and hints.
 */
import { AppBadge } from '@/components/base/badge';
import { useValueGeneratorStore } from '@/stores';

/*
 * Types & Interfaces.
 */

export interface ValueGeneratorFooterProps {}

/*
 * Component Setup.
 */

defineProps<ValueGeneratorFooterProps>();

const store = useValueGeneratorStore();
</script>

<template>
    <div
        class="border-default bg-muted text-muted gap-base flex flex-col border-t p-2 text-center text-xs"
    >
        <!-- Shift-Shift Shortcut Hint -->
        <div
            v-if="!store.wasOpenedViaShiftShift"
            class="flex items-center justify-center gap-2 text-xs"
        >
            <span>Press</span>

            <AppBadge
                variant="outline"
                color="neutral"
                size="xs"
                icon="i-hugeicons-arrow-up-big"
                label="Shift"
            />

            <span>twice to open this command</span>
        </div>

        <small>Use ↑↓ to navigate, Enter to select, Esc to close</small>
    </div>
</template>
