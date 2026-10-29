<script setup lang="ts">
/**
 * @component WorkspaceSkeleton
 * @description Placeholder for the request tabs, the request builder and the response viewer.
 * It draws the same shapes as the static shell in resources/views/partials/loading-shell.blade.php.
 */
import { AppSkeleton } from '@/components/base/skeleton';
</script>

<template>
    <section
        class="gap-group @container flex h-full min-w-0 flex-col"
        data-testid="skeleton-workspace"
    >
        <div class="gap-pane flex shrink-0 items-center">
            <AppSkeleton class="h-control-md w-control-md" />
            <AppSkeleton class="h-control-md w-20" />
        </div>

        <!-- The row layout starts at a wide container, close to the breakpoint in ClientWorkspace. -->
        <div class="gap-pane flex min-h-0 flex-1 flex-col @4xl:flex-row">
            <div
                class="gap-pane flex min-h-0 min-w-0 flex-col"
                style="flex: var(--shell-request-size, 1) 1 0%"
                data-testid="skeleton-request"
            >
                <div class="pane-surface p-tight shrink-0">
                    <AppSkeleton class="h-control-md w-full" />
                </div>
                <div class="pane-surface flex min-h-0 flex-1 flex-col">
                    <div
                        class="h-toolbar px-panel gap-base flex shrink-0 items-center border-b"
                    >
                        <AppSkeleton class="h-control-xs w-16" />
                        <AppSkeleton class="h-control-xs w-16" />
                        <AppSkeleton class="h-control-xs w-16" />
                    </div>
                    <div class="p-panel flex-1">
                        <AppSkeleton class="h-full w-full" />
                    </div>
                </div>
            </div>

            <div
                class="gap-pane flex min-h-0 min-w-0 flex-col"
                style="flex: var(--shell-response-size, 1) 1 0%"
                data-testid="skeleton-response"
            >
                <div
                    class="pane-surface h-pane-header px-panel flex shrink-0 items-center"
                >
                    <AppSkeleton class="h-control-xs w-20" />
                </div>
                <div class="pane-surface p-panel flex min-h-0 flex-1 flex-col">
                    <AppSkeleton class="h-full w-full" />
                </div>
            </div>
        </div>
    </section>
</template>
