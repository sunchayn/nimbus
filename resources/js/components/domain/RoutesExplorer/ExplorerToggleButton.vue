<script setup lang="ts">
/**
 * @component ExplorerToggleButton
 * @description A button that shows or hides the routes explorer.
 */
import { AppButton } from '@/components/base/button';
import { AppTooltip } from '@/components/base/tooltip';
import { useExplorerVisibility } from '@/composables/ui/useExplorerVisibility';

/*
 * Component Setup.
 */

const { isExplorerOpen, isExplorerDrawerOpen, isCompact, toggleExplorer } =
    useExplorerVisibility();
</script>

<template>
    <AppTooltip text="Toggle routes" :kbds="['meta', 'B']">
        <AppButton
            aria-label="Toggle routes"
            :aria-pressed="isCompact ? isExplorerDrawerOpen : isExplorerOpen"
            icon="i-hugeicons-sidebar-left"
            color="neutral"
            variant="outline"
            size="md"
            @click="toggleExplorer"
        />
    </AppTooltip>
</template>
