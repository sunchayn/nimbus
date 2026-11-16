import type {
    StoredWorkspaceRequest,
    WorkspaceListing,
    WorkspaceRequestPayload,
} from '@/interfaces/workspace';
import axios from 'axios';

const url = (path = '') => `${window.Nimbus?.basePath ?? ''}/api/workspace${path}`;

export const fetchWorkspace = async (): Promise<WorkspaceListing> =>
    (await axios.get<WorkspaceListing>(url())).data;

export const fetchWorkspaceRequest = async (
    id: string,
): Promise<StoredWorkspaceRequest> =>
    (await axios.get<StoredWorkspaceRequest>(url(`/requests/${id}`))).data;

/**
 * Creates or updates a request file. The hash is null for a new file.
 */
export const saveWorkspaceRequest = async (
    id: string,
    payload: WorkspaceRequestPayload,
    hash: string | null,
    folder?: string,
): Promise<StoredWorkspaceRequest> =>
    (
        await axios.put<StoredWorkspaceRequest>(url(`/requests/${id}`), {
            ...payload,
            hash,
            folder,
        })
    ).data;

export const deleteWorkspaceRequest = async (id: string): Promise<void> => {
    await axios.delete(url(`/requests/${id}`));
};

/**
 * Creates a folder and returns its normalized path.
 */
export const createWorkspaceFolder = async (folder: string): Promise<string> =>
    (await axios.post<{ folder: string }>(url('/folders'), { folder })).data.folder;

/**
 * Saves the order the folders are listed in.
 */
export const reorderWorkspaceFolders = async (folders: string[]): Promise<void> => {
    await axios.put(url('/folders/order'), { folders });
};

/**
 * Saves the order of the requests in a folder. An empty folder is the root.
 */
export const reorderWorkspaceRequests = async (
    folder: string,
    requests: string[],
): Promise<void> => {
    await axios.put(url('/requests/order'), { folder, requests });
};

/**
 * Renames a folder and returns its new path.
 */
export const renameWorkspaceFolder = async (
    folder: string,
    name: string,
): Promise<string> =>
    (await axios.patch<{ folder: string }>(url('/folders'), { folder, name })).data
        .folder;

/**
 * Deletes a folder with every request inside it.
 */
export const deleteWorkspaceFolder = async (folder: string): Promise<void> => {
    await axios.delete(url('/folders'), { params: { folder } });
};

/**
 * Moves a request to another folder, renames it, or both.
 */
export const moveWorkspaceRequest = async (
    id: string,
    changes: { folder?: string; name?: string },
    hash: string,
): Promise<StoredWorkspaceRequest> =>
    (
        await axios.put<StoredWorkspaceRequest>(url(`/requests/${id}/location`), {
            ...changes,
            hash,
        })
    ).data;

/**
 * Tells whether the file changed or disappeared since the tab loaded it.
 */
export const isWorkspaceConflict = (error: unknown): boolean =>
    axios.isAxiosError(error) && error.response?.status === 409;

/**
 * Reads the message of a failed workspace call.
 */
export const workspaceErrorMessage = (error: unknown): string => {
    if (!axios.isAxiosError(error)) {
        return error instanceof Error ? error.message : 'Something went wrong.';
    }

    if (error.response?.status === 409) {
        return 'The file changed on disk. Open it again from the Workspace tab, then save.';
    }

    const errors = error.response?.data?.errors as Record<string, string[]> | undefined;

    return (
        Object.values(errors ?? {})[0]?.[0] ??
        error.response?.data?.message ??
        error.message
    );
};
