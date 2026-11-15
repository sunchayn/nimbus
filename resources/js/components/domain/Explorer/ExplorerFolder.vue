<script setup lang="ts">
/**
 * @component ExplorerFolder
 * @description One folder of an explorer tree, with its sub folders and its items.
 * The root folder has no row and is always open.
 */
import { AppCollapsible } from '@/components/base/collapsible';
import { AppContextMenu, type AppContextMenuItem } from '@/components/base/context-menu';
import { AppInputPopover } from '@/components/base/popover';
import { FOLDER_DRAG_TYPE } from '@/config/workspace';
import type { ExplorerNode } from '@/interfaces/explorer';
import { computed, shallowRef } from 'vue';
import DropIndicator from './DropIndicator.vue';
import ExplorerFolderContents from './ExplorerFolderContents.vue';
import ExplorerFolderRow from './ExplorerFolderRow.vue';
import { useExplorerContext } from './explorerContext';

/*
 * Types & Interfaces.
 */

export interface ExplorerFolderProps {
    node: ExplorerNode<unknown>;
    isRoot?: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<ExplorerFolderProps>();

const { options, drag, emit } = useExplorerContext();

const isRenaming = shallowRef(false);
const renameDraft = shallowRef('');

const testId = (name: string) => options.testId && `${options.testId}-${name}`;

/*
 * Computed & Methods.
 */

const isOpen = computed(() => props.isRoot || options.isOpen(props.node.path));

const reorderEdge = computed(() =>
    drag.target.value?.path === props.node.path ? drag.target.value.position : undefined,
);

const setRenaming = (open: boolean) => {
    isRenaming.value = open;
    renameDraft.value = props.node.name;
};

const submitRename = () => {
    const name = renameDraft.value.trim();

    if (name) {
        emit.renameFolder(props.node, name);
        isRenaming.value = false;
    }
};

const menuItems = computed<AppContextMenuItem[][]>(() => {
    const groups = options.folderMenu?.(props.node) ?? [];

    if (!options.renamable) {
        return groups;
    }

    const rename: AppContextMenuItem = {
        label: 'Rename folder',
        icon: 'i-hugeicons-label',
        afterClose: () => setRenaming(true),
    };

    return groups.length ? [[rename, ...groups[0]], ...groups.slice(1)] : [[rename]];
});

const onDragStart = (event: DragEvent) => {
    event.dataTransfer?.setData(FOLDER_DRAG_TYPE, props.node.path);

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }

    drag.folder.value = props.node.path;
};
</script>

<template>
    <div
        class="data-[drop=true]:bg-elevated relative min-w-0 rounded-sm"
        :class="{ 'opacity-40': drag.folder.value === node.path }"
        :data-drop="drag.dropFolder.value === node.path"
        :data-reorder="reorderEdge"
        data-explorer-folder
        :data-testid="testId('folder')"
        :data-path="node.path"
    >
        <DropIndicator
            v-if="reorderEdge"
            :edge="reorderEdge"
            :test-id="testId('drop-indicator')"
        />

        <template v-if="!isRoot">
            <AppContextMenu :items="menuItems" :disabled="!menuItems.length">
                <ExplorerFolderRow
                    :expanded="isOpen"
                    :draggable="options.draggable"
                    data-explorer-folder-header
                    :data-testid="testId('folder-toggle')"
                    @click="options.toggle(node.path)"
                    @dragstart="options.draggable ? onDragStart($event) : undefined"
                >
                    {{ node.name }}
                </ExplorerFolderRow>
            </AppContextMenu>

            <AppInputPopover
                v-if="options.renamable"
                v-model="renameDraft"
                :open="isRenaming"
                header="Folder name"
                :test-id="testId('folder-rename')"
                @update:open="setRenaming"
                @submit="submitRename"
            >
                <template #anchor>
                    <span
                        class="h-item-md pointer-events-none absolute inset-x-0 top-0"
                    />
                </template>
            </AppInputPopover>
        </template>

        <ul v-if="isRoot" class="gap-tight min-h-item-md flex w-full min-w-0 flex-col">
            <ExplorerFolderContents :node="node" is-root />
        </ul>

        <AppCollapsible v-else :open="isOpen">
            <template #content>
                <ul
                    class="border-default ms-loose gap-tight py-tight ps-tight flex min-w-0 flex-col border-s"
                >
                    <ExplorerFolderContents :node="node" />
                </ul>
            </template>
        </AppCollapsible>
    </div>
</template>
