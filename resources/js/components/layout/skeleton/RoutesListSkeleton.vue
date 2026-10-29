<script setup lang="ts">
/**
 * @component RoutesListSkeleton
 * @description Placeholder rows for the routes tree, with a polite message while the routes load.
 */
import { AppSkeleton } from '@/components/base/skeleton';
import { useTimeout } from '@vueuse/core';

/*
 * Timers.
 */

// A fast load never shows the message, and a slow one gets an extra hint.
const showMessage = useTimeout(150);
const showSlowHint = useTimeout(3000);

// Widths and indents vary so the rows look like a folder tree.
const rows = [
    'w-2/3',
    'ms-base w-1/2',
    'ms-base w-3/5',
    'w-1/2',
    'w-3/4',
    'ms-base w-2/5',
    'w-3/5',
];
</script>

<template>
    <li class="gap-tight flex flex-col" data-testid="routes-skeleton">
        <p
            role="status"
            aria-live="polite"
            class="px-base min-h-8"
            data-testid="routes-skeleton-message"
        >
            <template v-if="showMessage">
                <span class="text-toned block text-sm font-medium">Loading routes</span>
                <span v-if="showSlowHint" class="text-muted block text-xs">
                    The first load can take longer. Later loads are faster.
                </span>
            </template>
        </p>
        <AppSkeleton
            v-for="(row, index) in rows"
            :key="index"
            :class="`h-control-sm ${row}`"
        />
    </li>
</template>
