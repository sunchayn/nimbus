<script setup lang="ts" generic="T">
/**
 * @component ExplorerTree
 * @description The folder tree shared by the routes explorer and the workspace explorer.
 * The `#item` slot renders the row of an item and `#item-overlay` adds content over it.
 * Dragging, renaming and the context menus are off until the props turn them on.
 */
import type { AppContextMenuItem } from '@/components/base/context-menu';
import { createExplorerDrag } from '@/composables/explorer/useExplorerDrag';
import { useExplorerDropZone } from '@/composables/explorer/useExplorerDropZone';
import type { ExplorerDropPosition, ExplorerNode } from '@/interfaces/explorer';
import { provide, reactive, toRefs, useSlots } from 'vue';
import ExplorerFolder from './ExplorerFolder.vue';
import { EXPLORER_CONTEXT, type ExplorerOptions } from './explorerContext';

/*
 * Types & Interfaces.
 */

export interface ExplorerTreeProps<Item> {
    /** The tree to show, where the root folder has an empty path. */
    node: ExplorerNode<Item>;
    /** Gives the stable key of an item. */
    itemKey: (item: Item) => string;
    /** Gives the text of an item, which is the starting value of its rename input. */
    itemLabel?: (item: Item) => string;
    isOpen: (path: string) => boolean;
    /** Lets items and folders be dragged to reorder them or to move them into a folder. */
    draggable?: boolean;
    /** Lets items and folders be renamed from the context menu, and items on double click. */
    renamable?: boolean;
    /** The context menu groups of an item. */
    itemMenu?: (item: Item) => AppContextMenuItem[][];
    /** The context menu groups of a folder. */
    folderMenu?: (node: ExplorerNode<Item>) => AppContextMenuItem[][];
    /** Prefix of the test ids of the tree. */
    testId?: string;
}

/*
 * Component Setup.
 */

const props = defineProps<ExplorerTreeProps<T>>();

const emit = defineEmits<{
    toggle: [path: string];
    renameItem: [item: T, name: string];
    renameFolder: [folder: ExplorerNode<T>, name: string];
    moveItem: [id: string, folder: string];
    reorderItem: [id: string, target: string, position: ExplorerDropPosition];
    reorderFolder: [path: string, target: string, position: ExplorerDropPosition];
}>();

defineSlots<{
    item(props: { item: T; folder: ExplorerNode<T> }): unknown;
    'item-overlay'?(props: { item: T; folder: ExplorerNode<T> }): unknown;
}>();

const slots = useSlots();

const drag = createExplorerDrag();

/*
 * The parts of the tree read these through the injected context.
 * The cast is safe, because the tree gives every part items of its own item type.
 */

const options = reactive({
    ...toRefs(props),
    toggle: (path: string) => emit('toggle', path),
}) as unknown as ExplorerOptions;

provide(EXPLORER_CONTEXT, {
    options,
    slots,
    drag,
    emit: {
        renameItem: (item, name) => emit('renameItem', item as T, name),
        renameFolder: (folder, name) =>
            emit('renameFolder', folder as ExplorerNode<T>, name),
        moveItem: (id, folder) => emit('moveItem', id, folder),
        reorderItem: (id, target, position) => emit('reorderItem', id, target, position),
        reorderFolder: (path, target, position) =>
            emit('reorderFolder', path, target, position),
    },
});

const dropZone = useExplorerDropZone(drag, {
    reorderFolder: (path, target, position) =>
        emit('reorderFolder', path, target, position),
    reorderItem: (id, target, position) => emit('reorderItem', id, target, position),
    moveItem: (id, folder) => emit('moveItem', id, folder),
});
</script>

<template>
    <div
        class="min-w-0"
        @dragover="draggable ? dropZone.onDragOver($event) : undefined"
        @dragleave="draggable ? dropZone.onDragLeave($event) : undefined"
        @drop="draggable ? dropZone.onDrop($event) : undefined"
        @dragend="draggable ? dropZone.onDragEnd() : undefined"
    >
        <ExplorerFolder :node="node as ExplorerNode<unknown>" is-root />
    </div>
</template>
