<script setup lang="ts">
/**
 * @component ExplorerItem
 * @description One item of an explorer tree.
 * The `#item` slot of the tree is the row, and `#item-overlay` adds content over it, such as a save button.
 * Dragging, renaming and the context menu apply only when the tree turns them on.
 */
import { AppContextMenu, type AppContextMenuItem } from '@/components/base/context-menu';
import { AppInputPopover } from '@/components/base/popover';
import { REQUEST_DRAG_TYPE } from '@/config/workspace';
import type { ExplorerNode } from '@/interfaces/explorer';
import { computed, shallowRef } from 'vue';
import DropIndicator from './DropIndicator.vue';
import { ExplorerSlot, useExplorerContext } from './explorerContext';

/*
 * Types & Interfaces.
 */

export interface ExplorerItemProps {
    item: unknown;
    folder: ExplorerNode<unknown>;
}

/*
 * Component Setup.
 */

const props = defineProps<ExplorerItemProps>();

const { options, drag, emit } = useExplorerContext();

const isDragging = shallowRef(false);
const isRenaming = shallowRef(false);
const renameDraft = shallowRef('');

const testId = (name: string) => options.testId && `${options.testId}-${name}`;

/*
 * Computed & Methods.
 */

const key = computed(() => options.itemKey(props.item));

const reorderEdge = computed(() =>
    drag.itemTarget.value?.id === key.value ? drag.itemTarget.value.position : undefined,
);

const setRenaming = (open: boolean) => {
    isRenaming.value = open;
    renameDraft.value = options.itemLabel?.(props.item) ?? '';
};

const submitRename = () => {
    const name = renameDraft.value.trim();

    if (name) {
        emit.renameItem(props.item, name);
        isRenaming.value = false;
    }
};

const menuItems = computed<AppContextMenuItem[][]>(() => {
    const groups = options.itemMenu?.(props.item) ?? [];

    if (!options.renamable) {
        return groups;
    }

    const rename: AppContextMenuItem = {
        label: 'Rename',
        icon: 'i-hugeicons-label',
        afterClose: () => setRenaming(true),
    };

    return groups.length ? [[rename, ...groups[0]], ...groups.slice(1)] : [[rename]];
});

const onDragStart = (event: DragEvent) => {
    event.dataTransfer?.setData(REQUEST_DRAG_TYPE, key.value);

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }

    isDragging.value = true;
    drag.item.value = key.value;
};
</script>

<template>
    <li
        class="relative"
        :class="{ 'opacity-40': isDragging }"
        :draggable="options.draggable"
        data-explorer-item
        :data-id="key"
        :data-folder="folder.path"
        :data-reorder="reorderEdge"
        :data-testid="testId('item')"
        @dragstart="options.draggable ? onDragStart($event) : undefined"
        @dragend="isDragging = false"
    >
        <DropIndicator
            v-if="reorderEdge"
            :edge="reorderEdge"
            :test-id="testId('drop-indicator')"
        />

        <AppContextMenu :items="menuItems" :disabled="!menuItems.length">
            <div
                class="group hover:bg-elevated focus-within:bg-elevated relative rounded-sm"
                @dblclick="options.renamable ? setRenaming(true) : undefined"
            >
                <ExplorerSlot name="item" :scope="{ item, folder }" />
                <ExplorerSlot name="item-overlay" :scope="{ item, folder }" />

                <AppInputPopover
                    v-if="options.renamable"
                    v-model="renameDraft"
                    :open="isRenaming"
                    header="Label"
                    :test-id="testId('rename')"
                    @update:open="setRenaming"
                    @submit="submitRename"
                >
                    <template #anchor>
                        <span class="pointer-events-none absolute inset-0" />
                    </template>
                </AppInputPopover>
            </div>
        </AppContextMenu>
    </li>
</template>
