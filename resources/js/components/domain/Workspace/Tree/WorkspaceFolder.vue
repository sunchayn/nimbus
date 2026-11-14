<script setup lang="ts">
/**
 * @component WorkspaceFolder
 * @description One folder of the workspace tree, with its requests and its sub folders.
 */
import { AppContextMenu, type AppContextMenuItem } from '@/components/base/context-menu';
import { AppIcon } from '@/components/base/icons';
import { AppInputPopover } from '@/components/base/popover';
import { useFolderDrag } from '@/composables/workspace/useFolderDrag';
import { FOLDER_DRAG_TYPE, REQUEST_DRAG_TYPE } from '@/config/workspace';
import type { WorkspaceFolderNode, WorkspaceSummary } from '@/interfaces/workspace';
import { useWorkspaceStore } from '@/stores/workspace/useWorkspaceStore';
import { computed, shallowRef } from 'vue';
import DropIndicator from './DropIndicator.vue';
import WorkspaceRequestItem from './WorkspaceRequestItem.vue';

/*
 * Types & Interfaces.
 */

export interface WorkspaceFolderProps {
    node: WorkspaceFolderNode;
    activeId?: string;
    dirtyIds?: string[];
    dropPath?: string | null;
    isRoot?: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<WorkspaceFolderProps>();

const workspaceStore = useWorkspaceStore();

const emit = defineEmits<{
    open: [id: string];
    rename: [request: WorkspaceSummary, name: string];
    save: [request: WorkspaceSummary];
    delete: [request: WorkspaceSummary];
    deleteFolder: [folder: WorkspaceFolderNode];
    renameFolder: [folder: WorkspaceFolderNode, name: string];
    drop: [id: string, folder: string];
    reorderRequest: [id: string, target: string, position: 'before' | 'after'];
    reorder: [folder: string, target: string, position: 'before' | 'after'];
}>();

const isOpen = computed(() => workspaceStore.isFolderOpen(props.node.path));

/*
 * Rename.
 */

const isRenaming = shallowRef(false);
const renameDraft = shallowRef('');

const setRenaming = (open: boolean) => {
    isRenaming.value = open;
    renameDraft.value = props.node.name;
};

const submitRename = () => {
    const name = renameDraft.value.trim();

    if (name) {
        emit('renameFolder', props.node, name);
        isRenaming.value = false;
    }
};

const folderMenuItems: AppContextMenuItem[][] = [
    [
        {
            label: 'Rename folder',
            afterClose: () => setRenaming(true),
        },
        {
            label: 'Delete folder',
            onSelect: () => emit('deleteFolder', props.node),
        },
    ],
];

/*
 * Drag and drop.
 * The root list handles every drag event, and the folder under the pointer is the drop target.
 */

const FOLDER_SELECTOR = '[data-testid="workspace-folder"]';
const ITEM_SELECTOR = '[data-testid="workspace-item"]';

const folderDrag = useFolderDrag();

const reorderEdge = computed(() =>
    folderDrag.target.value?.path === props.node.path
        ? folderDrag.target.value.position
        : undefined,
);

const parentOf = (path: string) =>
    path.includes('/') ? path.slice(0, path.lastIndexOf('/')) : '';

const onFolderDragStart = (event: DragEvent) => {
    event.dataTransfer?.setData(FOLDER_DRAG_TYPE, props.node.path);

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }

    folderDrag.folder.value = props.node.path;
};

/**
 * Finds the sibling of the dragged folder under the pointer and the side of it the folder would land on.
 */
const siblingUnder = (event: DragEvent) => {
    const dragged = folderDrag.folder.value;
    const over = folderUnder(event);

    if (dragged === null) {
        return null;
    }

    const prefix = parentOf(dragged) === '' ? '' : `${parentOf(dragged)}/`;

    if (over === dragged || !over.startsWith(prefix) || over === '') {
        return null;
    }

    const path = prefix + over.slice(prefix.length).split('/')[0];

    if (path === dragged) {
        return null;
    }

    const block = [
        ...(event.currentTarget as HTMLElement).querySelectorAll<HTMLElement>(
            FOLDER_SELECTOR,
        ),
    ].find(element => element.dataset.path === path);

    const header = block
        ?.querySelector<HTMLElement>('[data-testid="workspace-folder-toggle"]')
        ?.getBoundingClientRect();

    if (!header) {
        return null;
    }

    return {
        path,
        position: event.clientY < header.top + header.height / 2 ? 'before' : 'after',
    } as const;
};

/**
 * Finds the request under the pointer, and the side of it the dragged request would land on.
 * The request can come from another folder, in which case it moves there.
 */
const requestSiblingUnder = (event: DragEvent) => {
    const dragged = folderDrag.request.value;
    const item = (event.target as HTMLElement).closest<HTMLElement>(ITEM_SELECTOR);

    if (!dragged || !item?.dataset.id || item.dataset.id === dragged.id) {
        return null;
    }

    const box = item.getBoundingClientRect();

    return {
        id: item.dataset.id,
        position: event.clientY < box.top + box.height / 2 ? 'before' : 'after',
    } as const;
};

const requestEdge = (id: string) =>
    folderDrag.requestTarget.value?.id === id
        ? folderDrag.requestTarget.value.position
        : undefined;

const dropFolder = shallowRef<string | null>(null);

const activeDrop = computed(() => (props.isRoot ? dropFolder.value : props.dropPath));

const folderUnder = (event: DragEvent) =>
    (event.target as HTMLElement).closest<HTMLElement>(FOLDER_SELECTOR)?.dataset.path ??
    '';

const onDragOver = (event: DragEvent) => {
    if (event.dataTransfer?.types.includes(FOLDER_DRAG_TYPE)) {
        const sibling = siblingUnder(event);

        folderDrag.target.value = sibling;

        if (sibling) {
            event.preventDefault();
        }

        return;
    }

    if (event.dataTransfer?.types.includes(REQUEST_DRAG_TYPE)) {
        const sibling = requestSiblingUnder(event);

        event.preventDefault();
        folderDrag.requestTarget.value = sibling;
        dropFolder.value = sibling ? null : folderUnder(event);
    }
};

const isPointerInside = (event: DragEvent) => {
    const root = event.currentTarget as HTMLElement;
    const next = event.relatedTarget as Node | null;

    if (next) {
        return root.contains(next);
    }

    // Some browsers give no related target, so the pointer position decides.
    const box = root.getBoundingClientRect();

    return (
        event.clientX >= box.left &&
        event.clientX <= box.right &&
        event.clientY >= box.top &&
        event.clientY <= box.bottom
    );
};

const onDragLeave = (event: DragEvent) => {
    if (!isPointerInside(event)) {
        dropFolder.value = null;
        folderDrag.target.value = null;
        folderDrag.requestTarget.value = null;
    }
};

const onDrop = (event: DragEvent) => {
    const moved = folderDrag.folder.value;
    const target = siblingUnder(event) ?? folderDrag.target.value;

    if (event.dataTransfer?.types.includes(FOLDER_DRAG_TYPE) && moved && target) {
        event.preventDefault();
        emit('reorder', moved, target.path, target.position);
        onDragEnd();

        return;
    }

    const dragged = folderDrag.request.value;
    // The target is read again, so a drop never relies on an outdated dragover.
    const sibling = requestSiblingUnder(event) ?? folderDrag.requestTarget.value;

    if (event.dataTransfer?.types.includes(REQUEST_DRAG_TYPE) && dragged && sibling) {
        event.preventDefault();
        emit('reorderRequest', dragged.id, sibling.id, sibling.position);
        onDragEnd();

        return;
    }

    const id = event.dataTransfer?.getData(REQUEST_DRAG_TYPE);

    if (id) {
        event.preventDefault();
        emit('drop', id, folderUnder(event));
    }

    dropFolder.value = null;
};

function onDragEnd() {
    dropFolder.value = null;
    folderDrag.folder.value = null;
    folderDrag.target.value = null;
    folderDrag.request.value = null;
    folderDrag.requestTarget.value = null;
}
</script>

<template>
    <div
        class="data-[drop=true]:bg-elevated relative min-w-0 rounded-sm"
        :class="{ 'opacity-40': folderDrag.folder.value === node.path }"
        :data-drop="activeDrop === node.path"
        :data-reorder="reorderEdge"
        data-testid="workspace-folder"
        :data-path="node.path"
        @dragover="isRoot ? onDragOver($event) : undefined"
        @dragleave="isRoot ? onDragLeave($event) : undefined"
        @drop="isRoot ? onDrop($event) : undefined"
        @dragend="isRoot ? onDragEnd() : undefined"
    >
        <DropIndicator v-if="reorderEdge" :edge="reorderEdge" />

        <AppContextMenu v-if="!isRoot" :items="folderMenuItems">
            <button
                v-if="!isRoot"
                type="button"
                draggable="true"
                :aria-expanded="isOpen"
                class="hover:bg-elevated h-item-md gap-base px-base flex w-full items-center rounded-sm text-start text-sm"
                data-testid="workspace-folder-toggle"
                @click="workspaceStore.toggleFolder(node.path)"
                @dragstart="onFolderDragStart"
            >
                <AppIcon
                    name="i-hugeicons-arrow-right-01"
                    class="-mx-tight shrink-0 transition-transform"
                    :class="{ 'rotate-90': isOpen }"
                />
                <AppIcon name="i-hugeicons-folder-01" class="shrink-0" />
                <span class="flex-1 truncate">{{ node.name }}</span>
            </button>
        </AppContextMenu>

        <AppInputPopover
            v-if="!isRoot"
            v-model="renameDraft"
            :open="isRenaming"
            header="Folder name"
            test-id="workspace-folder-rename"
            @update:open="setRenaming"
            @submit="submitRename"
        >
            <template #anchor>
                <span class="h-item-md pointer-events-none absolute inset-x-0 top-0" />
            </template>
        </AppInputPopover>

        <ul
            v-show="isRoot || isOpen"
            :class="[
                'gap-tight flex min-w-0 flex-col',
                isRoot
                    ? 'min-h-item-md w-full'
                    : 'border-default ms-loose py-tight ps-tight border-s',
            ]"
        >
            <li v-for="child in node.folders" :key="child.path">
                <WorkspaceFolder
                    :node="child"
                    :active-id="activeId"
                    :dirty-ids="dirtyIds"
                    :drop-path="activeDrop"
                    @open="id => emit('open', id)"
                    @rename="(request, name) => emit('rename', request, name)"
                    @save="request => emit('save', request)"
                    @delete="request => emit('delete', request)"
                    @delete-folder="folder => emit('deleteFolder', folder)"
                    @rename-folder="(folder, name) => emit('renameFolder', folder, name)"
                    @drop="(id, folder) => emit('drop', id, folder)"
                    @reorder-request="
                        (id, target, position) =>
                            emit('reorderRequest', id, target, position)
                    "
                />
            </li>

            <WorkspaceRequestItem
                v-for="request in node.requests"
                :key="request.id"
                :request="request"
                :is-active="activeId === request.id"
                :is-dirty="dirtyIds?.includes(request.id)"
                :reorder-edge="requestEdge(request.id)"
                @drag-start="item => (folderDrag.request.value = item)"
                @open="id => emit('open', id)"
                @rename="(item, name) => emit('rename', item, name)"
                @save="item => emit('save', item)"
                @delete="item => emit('delete', item)"
            />

            <li
                v-if="!isRoot && !node.folders.length && !node.requests.length"
                class="h-item-md px-base text-muted flex items-center text-xs"
                data-testid="workspace-folder-empty"
            >
                Empty folder
            </li>
        </ul>
    </div>
</template>
