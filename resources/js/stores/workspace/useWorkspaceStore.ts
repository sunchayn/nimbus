import { useAppToast } from '@/components/base/toast';
import type { WorkspaceListing } from '@/interfaces/workspace';
import { useConfigStore } from '@/stores/core/useConfigStore';
import { useTabsStore } from '@/stores/request/useTabsStore';
import { defaultRequestName } from '@/utils/request/tabTitle';
import {
    createWorkspaceFolder,
    deleteWorkspaceFolder,
    deleteWorkspaceRequest,
    fetchWorkspace,
    fetchWorkspaceRequest,
    isWorkspaceConflict,
    moveWorkspaceRequest,
    renameWorkspaceFolder,
    reorderRequestIds,
    reorderWorkspaceFolders,
    reorderWorkspaceRequests,
    saveWorkspaceRequest,
    serializeRequestFile,
    toRequestFile,
    UnsupportedWorkspaceRequestError,
    workspaceErrorMessage,
} from '@/utils/workspace';
import { defineStore } from 'pinia';
import { v4 as uuidv4 } from 'uuid';
import { computed, ref, shallowRef } from 'vue';

export interface SaveOptions {
    /** The request name. The saved name is kept when it is left out. */
    name?: string;
    /** Writes a new file instead of updating the file of the tab. */
    asNew?: boolean;
    /** The folder of a new file. It is ignored when a file is updated. */
    folder?: string;
    /** The tab to save. The active tab is used when it is left out. */
    tabId?: string;
}

/**
 * Store for the request files saved in the workspace.
 */
export const useWorkspaceStore = defineStore(
    'workspace',
    () => {
        const configStore = useConfigStore();
        const tabsStore = useTabsStore();
        const toast = useAppToast();

        /*
         * State.
         */

        const listing = shallowRef<WorkspaceListing | null>(null);
        const isLoading = shallowRef(false);
        const isSaving = shallowRef(false);
        const error = shallowRef<string | null>(null);
        const isSaveDialogOpen = shallowRef(false);
        const saveAsNew = shallowRef(false);
        const isConflictOpen = shallowRef(false);
        const conflictOptions = shallowRef<SaveOptions | null>(null);
        const isMigrating = shallowRef(false);

        const isEnabled = computed(() => configStore.workspaceEnabled);

        /*
         * Actions.
         */

        const load = async () => {
            if (!isEnabled.value) {
                return;
            }

            isLoading.value = true;
            error.value = null;

            try {
                listing.value = await fetchWorkspace();
            } catch (failure) {
                error.value = workspaceErrorMessage(failure);
            } finally {
                isLoading.value = false;
            }
        };

        const open = async (id: string) => {
            try {
                tabsStore.openSavedRequest(await fetchWorkspaceRequest(id));
            } catch (failure) {
                toast.error('The request could not be opened', {
                    description: workspaceErrorMessage(failure),
                });
            }
        };

        const openSaveDialog = (asNew = false) => {
            saveAsNew.value = asNew;
            isSaveDialogOpen.value = true;
        };

        /**
         * Saves a tab, which is the active one unless a tab id is given. A tab with no file yet needs a name first, so the dialog opens.
         * Returns whether the request was written.
         */
        const save = async ({
            name,
            asNew = false,
            folder,
            tabId,
        }: SaveOptions = {}): Promise<boolean> => {
            const tab = tabId
                ? tabsStore.tabs.find(item => item.id === tabId)
                : tabsStore.activeTab;

            if (!tab || isSaving.value) {
                return false;
            }

            const link = asNew ? undefined : tab.workspace;
            const finalName = (name ?? link?.name ?? '').trim();

            if (!finalName) {
                openSaveDialog(asNew);

                return false;
            }

            isSaving.value = true;

            try {
                const payload = toRequestFile(tab.request, finalName);
                const id = link?.id ?? uuidv4();

                const stored = await saveWorkspaceRequest(
                    id,
                    payload,
                    link?.hash || null,
                    folder,
                );

                tab.workspace = {
                    id,
                    name: finalName,
                    hash: stored.hash,
                    saved: serializeRequestFile(payload),
                };

                toast.success('Request saved to workspace');

                await load();

                return true;
            } catch (failure) {
                if (isWorkspaceConflict(failure) && link) {
                    // The file changed on disk, so the person chooses between the two versions.
                    conflictOptions.value = { name: finalName, tabId: tab.id };
                    isConflictOpen.value = true;

                    return false;
                }

                toast.error('The request was not saved', {
                    description: workspaceErrorMessage(failure),
                });

                return false;
            } finally {
                isSaving.value = false;
            }
        };

        /**
         * Settles a save that hit a conflict.
         * Reload drops the changes of the tab and takes the file. Overwrite keeps the tab and replaces the file.
         */
        const resolveConflict = async (choice: 'reload' | 'overwrite') => {
            const options = conflictOptions.value;
            const tab = options?.tabId
                ? tabsStore.tabs.find(item => item.id === options.tabId)
                : tabsStore.activeTab;
            const id = tab?.workspace?.id;

            isConflictOpen.value = false;
            conflictOptions.value = null;

            if (!tab || !id || !options) {
                return;
            }

            let stored;

            try {
                stored = await fetchWorkspaceRequest(id);
            } catch {
                // The file is gone, so a reload has nothing to load and an overwrite writes it again.
                stored = null;
            }

            if (choice === 'reload') {
                if (stored) {
                    tabsStore.reloadSavedRequest(stored);
                    toast.success('Request reloaded from the file');
                } else {
                    tab.workspace = undefined;
                    toast.warning('The file no longer exists', {
                        description:
                            'The tab is not saved. Save it to write the file again.',
                    });
                }

                await load();

                return;
            }

            if (stored && tab.workspace) {
                tab.workspace = { ...tab.workspace, hash: stored.hash };
            } else if (tab.workspace) {
                // Without a file, the tab is written as a new file under the same id.
                tab.workspace = { ...tab.workspace, hash: '' };
            }

            await save(options);
        };

        const dismissConflict = () => {
            isConflictOpen.value = false;
            conflictOptions.value = null;
        };

        /**
         * Writes every open tab that has no file yet to the workspace.
         * Tabs that cannot be written are skipped, and the browser storage is left as it is.
         */
        const migrateTabs = async () => {
            if (isMigrating.value) {
                return;
            }

            isMigrating.value = true;

            const skipped: string[] = [];
            let moved = 0;

            try {
                for (const tab of tabsStore.tabs.filter(
                    candidate => !candidate.workspace,
                )) {
                    const name =
                        (tab.customTitle ?? defaultRequestName(tab.title)).trim() ||
                        'Request';

                    try {
                        const payload = toRequestFile(tab.request, name);
                        const id = uuidv4();
                        const stored = await saveWorkspaceRequest(id, payload, null);

                        tab.workspace = {
                            id,
                            name,
                            hash: stored.hash,
                            saved: serializeRequestFile(payload),
                        };

                        moved++;
                    } catch (failure) {
                        skipped.push(
                            failure instanceof UnsupportedWorkspaceRequestError
                                ? `${name}: ${failure.message}`
                                : `${name}: ${workspaceErrorMessage(failure)}`,
                        );
                    }
                }
            } finally {
                isMigrating.value = false;
            }

            await load();

            if (skipped.length) {
                toast.warning(`${moved} tabs moved, ${skipped.length} skipped`, {
                    description: skipped.join('\n'),
                });
            } else if (moved) {
                toast.success(`${moved} tabs moved into the workspace`);
            } else {
                toast.warning('There are no tabs to move');
            }
        };

        /*
         * Optimistic updates.
         * The listing changes right away, and the server answer settles it.
         * The listing is read again when the last operation in flight ends, so a failure restores the real state.
         */

        let inFlight = 0;

        const optimistic = async <T>(
            change: (current: WorkspaceListing) => WorkspaceListing,
            request: () => Promise<T>,
        ): Promise<T> => {
            if (listing.value) {
                listing.value = change(listing.value);
            }

            inFlight++;

            try {
                return await request();
            } finally {
                inFlight--;

                if (inFlight === 0) {
                    await load();
                }
            }
        };

        const isInside = (folder: string, path: string) =>
            folder === path || folder.startsWith(`${path}/`);

        // The server turns every segment into a slug, so this only has to be close until it answers.
        const guessFolderPath = (folder: string) =>
            folder
                .split(/[/\\]/)
                .map(segment =>
                    segment
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, ''),
                )
                .filter(Boolean)
                .join('/');

        const remove = async (id: string) => {
            try {
                await optimistic(
                    current => ({
                        ...current,
                        requests: current.requests.filter(request => request.id !== id),
                    }),
                    async () => {
                        await deleteWorkspaceRequest(id);

                        // A tab of the deleted file stays open, but it is no longer saved.
                        for (const tab of tabsStore.tabs) {
                            if (tab.workspace?.id === id) {
                                tab.workspace = undefined;
                            }
                        }

                        toast.success('Request deleted');
                    },
                );
            } catch (failure) {
                toast.error('The request was not deleted', {
                    description: workspaceErrorMessage(failure),
                });
            }
        };

        const createFolder = async (folder: string): Promise<boolean> => {
            const guess = guessFolderPath(folder);

            const paths = guess
                .split('/')
                .filter(Boolean)
                .map((_, index, segments) => segments.slice(0, index + 1).join('/'));

            try {
                await optimistic(
                    current => ({
                        ...current,
                        folders: [...new Set([...current.folders, ...paths])],
                    }),
                    () => createWorkspaceFolder(folder),
                );
            } catch (failure) {
                toast.error('The folder was not created', {
                    description: workspaceErrorMessage(failure),
                });

                return false;
            }

            return true;
        };

        /**
         * Saves the order of the folders. Folders left out of the list keep their place at the end.
         */
        const reorderFolders = async (paths: string[]) => {
            try {
                await optimistic(
                    current => ({
                        ...current,
                        folders: [
                            ...paths,
                            ...current.folders.filter(folder => !paths.includes(folder)),
                        ],
                    }),
                    () => reorderWorkspaceFolders(paths),
                );
            } catch (failure) {
                toast.error('The folders were not reordered', {
                    description: workspaceErrorMessage(failure),
                });
            }
        };

        /**
         * Saves the order of the requests in a folder. Requests left out of the list keep their place after the listed ones.
         */
        const reorderRequests = async (folder: string, ids: string[]) => {
            try {
                await optimistic(
                    current => {
                        const inFolder = current.requests.filter(
                            request => request.folder === folder,
                        );
                        const listed = ids.flatMap(id =>
                            inFolder.filter(request => request.id === id),
                        );

                        return {
                            ...current,
                            requests: [
                                ...current.requests.filter(
                                    request => request.folder !== folder,
                                ),
                                ...listed,
                                ...inFolder.filter(request => !ids.includes(request.id)),
                            ],
                        };
                    },
                    () => reorderWorkspaceRequests(folder, ids),
                );
            } catch (failure) {
                toast.error('The requests were not reordered', {
                    description: workspaceErrorMessage(failure),
                });
            }
        };

        /**
         * Renames a folder without moving it. The requests inside it keep their ids, so open tabs stay linked.
         */
        const renameFolder = async (path: string, name: string): Promise<boolean> => {
            const parent = path.includes('/') ? path.slice(0, path.lastIndexOf('/')) : '';
            const renamed = [parent, guessFolderPath(name)].filter(Boolean).join('/');

            const rename = (value: string) =>
                isInside(value, path) ? renamed + value.slice(path.length) : value;

            collapsedFolders.value = collapsedFolders.value.map(rename);

            try {
                await optimistic(
                    current => ({
                        ...current,
                        folders: current.folders.map(rename),
                        requests: current.requests.map(request => ({
                            ...request,
                            folder: rename(request.folder),
                            file: request.file.replace(
                                `requests/${path}/`,
                                `requests/${renamed}/`,
                            ),
                        })),
                    }),
                    () => renameWorkspaceFolder(path, name),
                );
            } catch (failure) {
                toast.error('The folder was not renamed', {
                    description: workspaceErrorMessage(failure),
                });

                return false;
            }

            return true;
        };

        /**
         * Deletes a folder and the requests inside it.
         */
        const removeFolder = async (path: string) => {
            const inside = (listing.value?.requests ?? [])
                .filter(request => isInside(request.folder, path))
                .map(request => request.id);

            collapsedFolders.value = collapsedFolders.value.filter(
                folder => !isInside(folder, path),
            );

            try {
                await optimistic(
                    current => ({
                        ...current,
                        folders: current.folders.filter(
                            folder => !isInside(folder, path),
                        ),
                        requests: current.requests.filter(
                            request => !isInside(request.folder, path),
                        ),
                    }),
                    async () => {
                        await deleteWorkspaceFolder(path);

                        // Tabs of the deleted files stay open, but they are no longer saved.
                        for (const tab of tabsStore.tabs) {
                            if (tab.workspace && inside.includes(tab.workspace.id)) {
                                tab.workspace = undefined;
                            }
                        }

                        toast.success('Folder deleted');
                    },
                );
            } catch (failure) {
                toast.error('The folder was not deleted', {
                    description: workspaceErrorMessage(failure),
                });
            }
        };

        /**
         * Moves a request to a folder, renames it, or both. An empty folder is the root.
         */
        const move = async (
            id: string,
            changes: { folder?: string; name?: string },
        ): Promise<boolean> => {
            const summary = listing.value?.requests.find(request => request.id === id);

            if (!summary) {
                return false;
            }

            if (
                (changes.folder === undefined || changes.folder === summary.folder) &&
                (changes.name === undefined || changes.name === summary.name)
            ) {
                return true;
            }

            try {
                await optimistic(
                    current => {
                        const folder =
                            changes.folder === undefined
                                ? summary.folder
                                : guessFolderPath(changes.folder);

                        const updated = {
                            ...summary,
                            name: changes.name ?? summary.name,
                            folder,
                        };

                        // The server puts a request that changes folder at the end of the new folder.
                        return {
                            ...current,
                            requests:
                                folder === summary.folder
                                    ? current.requests.map(request =>
                                          request.id === id ? updated : request,
                                      )
                                    : [
                                          ...current.requests.filter(
                                              request => request.id !== id,
                                          ),
                                          updated,
                                      ],
                        };
                    },
                    async () => {
                        const stored = await moveWorkspaceRequest(
                            id,
                            changes,
                            summary.hash,
                        );

                        // A tab of the moved file stays linked, and a rename must not make it look changed.
                        for (const tab of tabsStore.tabs) {
                            if (tab.workspace?.id === id) {
                                tab.workspace = {
                                    ...tab.workspace,
                                    name: stored.request.name,
                                    hash: stored.hash,
                                    saved: JSON.stringify({
                                        ...JSON.parse(tab.workspace.saved),
                                        name: stored.request.name,
                                    }),
                                };
                            }
                        }
                    },
                );
            } catch (failure) {
                if (isWorkspaceConflict(failure)) {
                    // The list is reloaded after a failed move, so a second try uses the file as it is on disk.
                    toast.warning('The request was not moved', {
                        description:
                            'The file changed on disk, so the list was refreshed. Try again.',
                    });

                    return false;
                }

                toast.error('The request was not moved', {
                    description: workspaceErrorMessage(failure),
                });

                return false;
            }

            return true;
        };

        /*
         * Folder collapse state.
         * Folders are open by default, so only the closed ones are stored and they survive a reload.
         */

        const collapsedFolders = ref<string[]>([]);

        const isFolderOpen = (path: string) => !collapsedFolders.value.includes(path);

        const toggleFolder = (path: string) => {
            collapsedFolders.value = isFolderOpen(path)
                ? [...collapsedFolders.value, path]
                : collapsedFolders.value.filter(collapsed => collapsed !== path);
        };

        /**
         * Moves a request next to another one, in the folder of that request.
         * The list is reloaded once at the end, so the request does not show at the end of the folder first.
         */
        const moveBeside = async (
            id: string,
            target: string,
            position: 'before' | 'after',
        ) => {
            const folder = listing.value?.requests.find(
                request => request.id === target,
            )?.folder;

            if (folder === undefined) {
                return;
            }

            await optimistic(
                current => current,
                async () => {
                    if (!(await move(id, { folder }))) {
                        return;
                    }

                    const order = reorderRequestIds(
                        listing.value?.requests ?? [],
                        id,
                        target,
                        position,
                    );

                    if (order) {
                        await reorderRequests(order.folder, order.ids);
                    }
                },
            );
        };

        return {
            listing,
            isLoading,
            isSaving,
            error,
            isSaveDialogOpen,
            saveAsNew,
            isConflictOpen,
            isMigrating,
            isEnabled,
            load,
            open,
            openSaveDialog,
            save,
            resolveConflict,
            dismissConflict,
            migrateTabs,
            remove,
            createFolder,
            removeFolder,
            renameFolder,
            reorderFolders,
            reorderRequests,
            moveBeside,
            move,
            collapsedFolders,
            isFolderOpen,
            toggleFolder,
        };
    },
    { persist: { pick: ['collapsedFolders'] } },
);
