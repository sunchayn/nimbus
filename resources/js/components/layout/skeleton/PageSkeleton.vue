<script setup lang="ts">
/**
 * @component PageSkeleton
 * @description Placeholder for the Settings and Environments pages while their chunk loads.
 * It draws the same shapes as the static shell in resources/views/partials/loading-shell.blade.php.
 */
import { AppSkeleton } from '@/components/base/skeleton';
</script>

<template>
    <div class="p-base h-full" data-testid="skeleton-page">
        <div
            class="bg-default border-default rounded-pane flex h-full flex-col overflow-hidden border"
        >
            <div class="h-toolbar px-panel flex items-center border-b">
                <AppSkeleton class="h-control-xs w-20" />
            </div>
            <div class="h-sub-toolbar bg-muted border-b" />
            <div class="p-panel gap-base flex flex-col">
                <AppSkeleton class="h-control-md w-1/3" />
                <AppSkeleton class="h-control-md w-2/3" />
                <AppSkeleton class="h-control-md w-1/2" />
                <AppSkeleton class="h-control-md w-3/5" />
            </div>
        </div>
    </div>
</template>
