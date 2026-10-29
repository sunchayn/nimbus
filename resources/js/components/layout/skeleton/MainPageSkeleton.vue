<script setup lang="ts">
/**
 * @component MainPageSkeleton
 * @description Placeholder for the main page while its chunk loads, with the explorer and the workspace.
 * The inline script in app.blade.php sets the sidebar width from the saved layout.
 */
import { AppSkeleton } from '@/components/base/skeleton';
import RoutesListSkeleton from '@/components/layout/skeleton/RoutesListSkeleton.vue';
import WorkspaceSkeleton from '@/components/layout/skeleton/WorkspaceSkeleton.vue';
</script>

<template>
    <div class="p-base flex h-full overflow-hidden" data-testid="skeleton-main">
        <aside
            class="gap-tight flex shrink-0 flex-col overflow-hidden max-md:hidden"
            style="width: var(--shell-explorer-width, 20%)"
            data-testid="skeleton-sidebar"
        >
            <AppSkeleton class="h-control-md w-full" />
            <ul class="flex flex-col">
                <RoutesListSkeleton />
            </ul>
        </aside>
        <div
            class="shrink-0 max-md:hidden"
            style="width: var(--shell-handle-width, var(--spacing-group))"
        />
        <div class="min-w-0 flex-1">
            <WorkspaceSkeleton />
        </div>
    </div>
</template>
