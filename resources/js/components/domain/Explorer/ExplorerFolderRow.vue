<script setup lang="ts">
/**
 * @component ExplorerFolderRow
 * @description The row of one folder in an explorer tree, with its chevron, its icon and its name.
 */
import { AppIcon } from '@/components/base/icons';
import ExplorerRow from './ExplorerRow.vue';

/*
 * Types & Interfaces.
 */

export interface ExplorerFolderRowProps {
    expanded?: boolean;
}

/*
 * Component Setup.
 */

defineProps<ExplorerFolderRowProps>();
</script>

<template>
    <ExplorerRow :aria-expanded="expanded">
        <AppIcon
            name="i-hugeicons-arrow-right-01"
            class="-mx-tight shrink-0 transition-transform"
            :class="{ 'rotate-90': expanded }"
        />
        <AppIcon name="i-hugeicons-folder-01" class="shrink-0" />
        <span class="flex-1 truncate">
            <slot />
        </span>
    </ExplorerRow>
</template>
