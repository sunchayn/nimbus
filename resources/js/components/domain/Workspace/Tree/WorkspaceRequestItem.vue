<script setup lang="ts">
/**
 * @component WorkspaceRequestItem
 * @description One saved request in the workspace tree.
 */
import { AppButton } from '@/components/base/button';
import { AppContextMenu, type AppContextMenuItem } from '@/components/base/context-menu';
import { AppInputPopover } from '@/components/base/popover';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import ExplorerRow from '@/components/domain/RoutesExplorer/ExplorerRow.vue';
import { REQUEST_DRAG_TYPE } from '@/config/workspace';
import type { WorkspaceSummary } from '@/interfaces/workspace';
import { shallowRef } from 'vue';
import DropIndicator from './DropIndicator.vue';

/*
 * Types & Interfaces.
 */

export interface WorkspaceRequestItemProps {
    request: WorkspaceSummary;
    isActive?: boolean;
    isDirty?: boolean;
    /** The side of the row where a dragged request would land. */
    reorderEdge?: 'before' | 'after';
}

/*
 * Component Setup.
 */

const props = defineProps<WorkspaceRequestItemProps>();

const emit = defineEmits<{
    open: [id: string];
    rename: [request: WorkspaceSummary, name: string];
    save: [request: WorkspaceSummary];
    delete: [request: WorkspaceSummary];
    dragStart: [request: WorkspaceSummary];
}>();

const isDragging = shallowRef(false);
const isRenaming = shallowRef(false);
const renameDraft = shallowRef('');

/*
 * Rename.
 */

const setRenaming = (open: boolean) => {
    isRenaming.value = open;
    renameDraft.value = props.request.name;
};

const submitRename = () => {
    const name = renameDraft.value.trim();

    if (name) {
        emit('rename', props.request, name);
        isRenaming.value = false;
    }
};

const menuItems: AppContextMenuItem[][] = [
    [
        {
            label: 'Rename',
            afterClose: () => setRenaming(true),
        },
        {
            label: 'Delete',
            onSelect: () => emit('delete', props.request),
        },
    ],
];

/*
 * Drag and drop.
 */

const onDragStart = (event: DragEvent) => {
    event.dataTransfer?.setData(REQUEST_DRAG_TYPE, props.request.id);

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }

    isDragging.value = true;
    emit('dragStart', props.request);
};
</script>

<template>
    <li
        class="relative"
        :class="{ 'opacity-40': isDragging }"
        draggable="true"
        :data-id="request.id"
        :data-folder="request.folder"
        :data-reorder="reorderEdge"
        data-testid="workspace-item"
        @dragstart="onDragStart"
        @dragend="isDragging = false"
    >
        <DropIndicator v-if="reorderEdge" :edge="reorderEdge" />

        <AppContextMenu :items="menuItems">
            <div
                class="group hover:bg-elevated focus-within:bg-elevated relative rounded-sm"
                @dblclick="setRenaming(true)"
            >
                <ExplorerRow
                    :is-active="isActive"
                    :title="request.file"
                    @click="emit('open', request.id)"
                >
                    <HttpVerbLabel class="min-w-8" :method="request.method" />
                    <span class="flex-1 whitespace-nowrap">
                        {{ request.name }}
                    </span>
                </ExplorerRow>
                <div
                    v-if="isDirty"
                    class="pointer-events-none absolute inset-y-0 end-0 flex items-stretch"
                >
                    <span
                        class="to-elevated w-8 bg-linear-to-r from-transparent opacity-0 transition-opacity group-focus-within:opacity-100 group-hover:opacity-100 pointer-coarse:opacity-100"
                        aria-hidden="true"
                    />
                    <span
                        class="group-focus-within:bg-elevated group-hover:bg-elevated pe-tight pointer-coarse:bg-elevated flex items-center"
                    >
                        <span class="size-item-sm relative">
                            <span
                                class="absolute inset-0 flex items-center justify-center transition group-focus-within:scale-50 group-focus-within:opacity-0 group-hover:scale-50 group-hover:opacity-0 pointer-coarse:scale-50 pointer-coarse:opacity-0"
                                title="Unsaved changes"
                                data-testid="workspace-item-dirty"
                            >
                                <span class="bg-warning size-dot-xs rounded-full" />
                            </span>
                            <AppButton
                                icon="i-hugeicons-floppy-disk"
                                color="neutral"
                                variant="ghost"
                                size="xs"
                                square
                                class="absolute inset-0 scale-50 opacity-0 transition group-focus-within:pointer-events-auto group-focus-within:scale-100 group-focus-within:opacity-100 group-hover:pointer-events-auto group-hover:scale-100 group-hover:opacity-100 pointer-coarse:pointer-events-auto pointer-coarse:scale-100 pointer-coarse:opacity-100"
                                :aria-label="`Save changes to ${request.name}`"
                                title="Save changes"
                                data-testid="workspace-save"
                                @click="emit('save', request)"
                            />
                        </span>
                    </span>
                </div>
                <AppInputPopover
                    v-model="renameDraft"
                    :open="isRenaming"
                    header="Name"
                    test-id="workspace-rename"
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
