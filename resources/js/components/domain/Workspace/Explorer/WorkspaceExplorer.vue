<script setup lang="ts">
/**
 * @component WorkspaceExplorer
 * @description The saved requests of the workspace, listed as a folder tree in the sidebar.
 */
import { AppButton } from '@/components/base/button';
import type { AppContextMenuItem } from '@/components/base/context-menu';
import { AppInputPopover } from '@/components/base/popover';
import { AppScrollArea } from '@/components/base/scroll-area';
import WorkspaceConfirmDialog from '@/components/domain/Workspace/Dialogs/WorkspaceConfirmDialog.vue';
import ExplorerItemRow from '@/components/domain/Explorer/ExplorerItemRow.vue';
import ExplorerTree from '@/components/domain/Explorer/ExplorerTree.vue';
import WorkspaceSaveOverlay from '@/components/domain/Workspace/Tree/WorkspaceSaveOverlay.vue';
import type { RouteDefinition } from '@/interfaces/routes';
import type { WorkspaceFolderNode, WorkspaceSummary } from '@/interfaces/workspace';
import { useTabsStore } from '@/stores/request/useTabsStore';
import { useWorkspaceStore } from '@/stores/workspace/useWorkspaceStore';
import {
    emptyRouteFilters,
    hasActiveRouteFilters,
    type RouteFilters,
} from '@/utils/routes';
import {
    buildWorkspaceTree,
    filterWorkspaceRequests,
    isTabDirty,
    pruneEmptyFolders,
    reorderFolderPaths,
    reorderRequestIds,
} from '@/utils/workspace';
import { computed, onMounted, shallowRef } from 'vue';
import WorkspaceEmptyState from './WorkspaceEmptyState.vue';

const props = withDefaults(
    defineProps<{
        // The keyword and filters come from the toolbar above the explorer.
        search?: string;
        filters?: RouteFilters;
        routes?: RouteDefinition[];
    }>(),
    {
        search: '',
        filters: () => emptyRouteFilters(),
        routes: () => [],
    },
);

const workspaceStore = useWorkspaceStore();
const tabsStore = useTabsStore();

const pendingDelete = shallowRef<WorkspaceSummary | null>(null);
const isConfirmOpen = shallowRef(false);

const pendingFolderDelete = shallowRef<WorkspaceFolderNode | null>(null);
const isFolderConfirmOpen = shallowRef(false);

const folderRequestCount = computed(
    () =>
        (workspaceStore.listing?.requests ?? []).filter(
            request =>
                request.folder === pendingFolderDelete.value?.path ||
                request.folder.startsWith(`${pendingFolderDelete.value?.path}/`),
        ).length,
);

const folderDeleteDescription = computed(() => {
    const count = folderRequestCount.value;
    const contents = count === 1 ? '1 request' : `${count} requests`;

    return `This deletes the folder ${pendingFolderDelete.value?.path ?? ''} and everything inside it, including ${contents}.`;
});

const folderValue = shallowRef('');
const isFolderOpen = shallowRef(false);

const tree = computed(() =>
    buildWorkspaceTree(
        workspaceStore.listing?.folders ?? [],
        workspaceStore.listing?.requests ?? [],
    ),
);

const isFiltering = computed(
    () => props.search.trim().length > 0 || hasActiveRouteFilters(props.filters),
);

// The full tree keeps serving the reordering, the shown one only has the matches.
const shownTree = computed(() =>
    isFiltering.value
        ? pruneEmptyFolders(
              buildWorkspaceTree(
                  workspaceStore.listing?.folders ?? [],
                  filterWorkspaceRequests(
                      workspaceStore.listing?.requests ?? [],
                      props.search,
                      props.filters,
                      props.routes,
                  ),
              ),
          )
        : tree.value,
);

const hasNoMatches = computed(
    () =>
        isFiltering.value &&
        shownTree.value.items.length === 0 &&
        shownTree.value.folders.length === 0,
);

const isEmpty = computed(
    () =>
        !workspaceStore.listing?.requests.length &&
        !workspaceStore.listing?.folders.length,
);

onMounted(() => workspaceStore.load());

const askToDelete = (request: WorkspaceSummary) => {
    pendingDelete.value = request;
    isConfirmOpen.value = true;
};

const confirmDelete = () => {
    const request = pendingDelete.value;

    isConfirmOpen.value = false;

    if (request) {
        void workspaceStore.remove(request.id);
    }
};

const saveChanges = async (request: WorkspaceSummary) => {
    const tab = tabsStore.tabs.find(item => item.workspace?.id === request.id);

    if (tab) {
        await workspaceStore.save({ tabId: tab.id });
    }
};

const askToDeleteFolder = (folder: WorkspaceFolderNode) => {
    pendingFolderDelete.value = folder;
    isFolderConfirmOpen.value = true;
};

const confirmDeleteFolder = () => {
    const folder = pendingFolderDelete.value;

    isFolderConfirmOpen.value = false;

    if (folder) {
        void workspaceStore.removeFolder(folder.path);
    }
};

const reorderFolder = (moved: string, target: string, position: 'before' | 'after') =>
    workspaceStore.reorderFolders(
        reorderFolderPaths(tree.value, moved, target, position),
    );

const reorderRequest = (moved: string, target: string, position: 'before' | 'after') => {
    const order = reorderRequestIds(
        workspaceStore.listing?.requests ?? [],
        moved,
        target,
        position,
    );

    if (order) {
        void workspaceStore.reorderRequests(order.folder, order.ids);

        return;
    }

    const source = workspaceStore.listing?.requests.find(request => request.id === moved);
    const sibling = workspaceStore.listing?.requests.find(
        request => request.id === target,
    );

    // A request dropped next to one of another folder moves into that folder.
    if (source && sibling && source.folder !== sibling.folder) {
        void workspaceStore.moveBeside(moved, target, position);
    }
};

const renameFolder = (folder: WorkspaceFolderNode, name: string) =>
    workspaceStore.renameFolder(folder.path, name);

const renameRequest = (request: WorkspaceSummary, name: string) =>
    workspaceStore.move(request.id, { name });

const confirmFolder = () => {
    const folder = folderValue.value.trim();

    if (!folder) {
        return;
    }

    folderValue.value = '';
    isFolderOpen.value = false;

    void workspaceStore.createFolder(folder);
};

const dirtyIds = computed(() =>
    tabsStore.tabs.filter(isTabDirty).flatMap(tab => tab.workspace?.id ?? []),
);

const hasUnsavedTabs = computed(() => tabsStore.tabs.some(tab => !tab.workspace));

const isMigrateOpen = shallowRef(false);

const confirmMigrate = async () => {
    isMigrateOpen.value = false;

    await workspaceStore.migrateTabs();
};

const requestMenu = (request: WorkspaceSummary): AppContextMenuItem[][] => [
    [
        {
            label: 'Delete',
            icon: 'i-hugeicons-delete-01',
            onSelect: () => askToDelete(request),
        },
    ],
];

const folderMenu = (folder: WorkspaceFolderNode): AppContextMenuItem[][] => [
    [
        {
            label: 'Delete folder',
            icon: 'i-hugeicons-delete-01',
            onSelect: () => askToDeleteFolder(folder),
        },
    ],
];

const moveTo = (id: string, folder: string) => workspaceStore.move(id, { folder });
</script>

<template>
    <div class="gap-tight mt-hair flex min-h-0 flex-col" data-testid="workspace-panel">
        <div v-if="workspaceStore.error" class="px-base" data-testid="workspace-error">
            <p class="mb-base text-xs">The workspace could not be loaded.</p>
            <p class="text-muted mb-base text-xs">{{ workspaceStore.error }}</p>
            <AppButton
                label="Retry"
                color="neutral"
                variant="outline"
                size="sm"
                data-testid="workspace-retry"
                @click="workspaceStore.load()"
            />
        </div>

        <template v-else>
            <div class="gap-tight flex items-center justify-start">
                <AppButton
                    v-if="hasUnsavedTabs"
                    icon="i-hugeicons-folder-transfer"
                    aria-label="Move my tabs here"
                    title="Move my tabs here"
                    color="neutral"
                    variant="outline"
                    size="sm"
                    square
                    :loading="workspaceStore.isMigrating"
                    data-testid="workspace-migrate"
                    @click="isMigrateOpen = true"
                />
                <AppInputPopover
                    v-model="folderValue"
                    v-model:open="isFolderOpen"
                    header="Folder"
                    placeholder="users or users/admin"
                    test-id="workspace-folder"
                    @submit="confirmFolder"
                >
                    <AppButton
                        icon="i-hugeicons-folder-add"
                        label="New folder"
                        color="neutral"
                        variant="outline"
                        size="sm"
                        data-testid="workspace-new-folder"
                    />
                </AppInputPopover>
            </div>

            <WorkspaceEmptyState v-if="isEmpty" :is-loading="workspaceStore.isLoading" />

            <p
                v-else-if="hasNoMatches"
                class="text-muted px-base text-xs"
                data-testid="workspace-no-matches"
            >
                No saved request matches your search.
            </p>

            <AppScrollArea v-else class="min-h-0 flex-1">
                <ExplorerTree
                    :node="shownTree"
                    :item-key="request => request.id"
                    :item-label="request => request.name"
                    :is-open="path => isFiltering || workspaceStore.isFolderOpen(path)"
                    :item-menu="requestMenu"
                    :folder-menu="folderMenu"
                    :draggable="!isFiltering"
                    renamable
                    test-id="workspace"
                    @toggle="workspaceStore.toggleFolder"
                    @rename-item="renameRequest"
                    @rename-folder="renameFolder"
                    @move-item="moveTo"
                    @reorder-folder="reorderFolder"
                    @reorder-item="reorderRequest"
                >
                    <template #item="{ item }">
                        <ExplorerItemRow
                            :method="item.method"
                            :is-active="tabsStore.activeTab?.workspace?.id === item.id"
                            :title="item.file"
                            @click="workspaceStore.open(item.id)"
                        >
                            {{ item.name }}
                        </ExplorerItemRow>
                    </template>
                    <template #item-overlay="{ item }">
                        <WorkspaceSaveOverlay
                            v-if="dirtyIds.includes(item.id)"
                            :request="item"
                            @save="saveChanges"
                        />
                    </template>
                </ExplorerTree>
            </AppScrollArea>
        </template>

        <WorkspaceConfirmDialog
            v-model:open="isConfirmOpen"
            title="Delete request"
            confirm-label="Delete"
            test-id="workspace-delete-confirm"
            @confirm="confirmDelete"
        >
            <template #description>
                <p class="mb-tight">
                    Are you sure you want to delete this request from the workspace?
                </p>
                <span>{{ pendingDelete?.endpoint }}</span>
            </template>
        </WorkspaceConfirmDialog>

        <WorkspaceConfirmDialog
            v-model:open="isFolderConfirmOpen"
            title="Delete folder"
            :description="folderDeleteDescription"
            confirm-label="Delete"
            test-id="workspace-delete-folder-confirm"
            @confirm="confirmDeleteFolder"
        />

        <WorkspaceConfirmDialog
            v-model:open="isMigrateOpen"
            title="Move my tabs into the workspace"
            description="Each open tab that is not saved yet is written as a request file. Tabs with form data are skipped. Your browser data is not deleted."
            confirm-label="Move tabs"
            confirm-color="primary"
            test-id="workspace-migrate-confirm"
            @confirm="confirmMigrate"
        />
    </div>
</template>
