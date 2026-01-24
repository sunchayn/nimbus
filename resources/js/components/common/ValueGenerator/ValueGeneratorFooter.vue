<script setup lang="ts">
/**
 * @component ValueGeneratorFooter
 * @description The footer for the value generator command, showing shortcuts and hints.
 */
import { AppBadge } from '@/components/base/badge';
import { useValueGeneratorStore } from '@/stores';
import { ArrowBigUpIcon } from 'lucide-vue-next';

/*
 * Types & Interfaces.
 */

export interface AppValueGeneratorFooterProps {}

/*
 * Component Setup.
 */

const props = defineProps<AppValueGeneratorFooterProps>();

const store = useValueGeneratorStore();
</script>

<template>
    <div
        class="border-t border-border bg-subtle-background p-2 text-center text-xs text-muted-foreground"
    >
        <!-- Shift-Shift Shortcut Hint -->
        <div
            v-if="!store.wasOpenedViaShiftShift"
            class="mb-2 flex items-center justify-center gap-1.5 text-xs"
        >
            <span>Press</span>

            <AppBadge variant="outline" size="xs" class="flex items-center gap-1">
                <ArrowBigUpIcon class="size-3" />
                Shift
            </AppBadge>

            <span>twice to open this command</span>
        </div>

        <small>Use ↑↓ to navigate, Enter to select, Esc to close</small>
    </div>
</template>
