<script setup lang="ts">
/**
 * @component ExplorerFolderContents
 * @description The sub folders and the items of one folder, with a note when a folder has neither.
 */
import type { ExplorerNode } from '@/interfaces/explorer';
import ExplorerFolder from './ExplorerFolder.vue';
import ExplorerItem from './ExplorerItem.vue';
import { useExplorerContext } from './explorerContext';

/*
 * Types & Interfaces.
 */

export interface ExplorerFolderContentsProps {
    node: ExplorerNode<unknown>;
    isRoot?: boolean;
}

/*
 * Component Setup.
 */

defineProps<ExplorerFolderContentsProps>();

const { options } = useExplorerContext();
</script>

<template>
    <li v-for="child in node.folders" :key="child.path">
        <ExplorerFolder :node="child" />
    </li>

    <ExplorerItem
        v-for="item in node.items"
        :key="options.itemKey(item)"
        :item="item"
        :folder="node"
    />

    <li
        v-if="!isRoot && !node.folders.length && !node.items.length"
        class="h-item-md px-base text-muted flex items-center text-xs"
        :data-testid="options.testId && `${options.testId}-folder-empty`"
    >
        Empty folder
    </li>
</template>
