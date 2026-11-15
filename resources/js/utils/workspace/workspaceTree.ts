import type { WorkspaceFolderNode, WorkspaceSummary } from '@/interfaces/workspace';

/**
 * Builds the folder tree from the flat folder and request lists of the workspace.
 */
export const buildWorkspaceTree = (
    folders: string[],
    requests: WorkspaceSummary[],
): WorkspaceFolderNode => {
    const root: WorkspaceFolderNode = {
        name: '',
        path: '',
        folders: [],
        items: [],
    };
    const nodes = new Map<string, WorkspaceFolderNode>([['', root]]);

    const ensure = (path: string): WorkspaceFolderNode => {
        const existing = nodes.get(path);

        if (existing) {
            return existing;
        }

        const segments = path.split('/');
        const node: WorkspaceFolderNode = {
            name: segments[segments.length - 1],
            path,
            folders: [],
            items: [],
        };

        nodes.set(path, node);
        ensure(segments.slice(0, -1).join('/')).folders.push(node);

        return node;
    };

    // The server sends the folders in the order they are listed.
    for (const folder of folders) {
        ensure(folder);
    }

    for (const request of requests) {
        ensure(request.folder).items.push(request);
    }

    return root;
};

const parentOf = (path: string): string =>
    path.includes('/') ? path.slice(0, path.lastIndexOf('/')) : '';

/**
 * Lists every folder path after one folder moved next to a sibling.
 * A folder can only move among the folders of the same parent, and its content follows it.
 */
export const reorderFolderPaths = (
    root: WorkspaceFolderNode,
    moved: string,
    target: string,
    position: 'before' | 'after',
): string[] => {
    const nodes = new Map<string, WorkspaceFolderNode>();

    const index = (node: WorkspaceFolderNode) => {
        nodes.set(node.path, node);
        node.folders.forEach(index);
    };

    index(root);

    const orders = new Map<string, string[]>();
    const parent = nodes.get(parentOf(moved));

    if (moved !== target && parentOf(moved) === parentOf(target) && parent) {
        const siblings = parent.folders
            .map(folder => folder.path)
            .filter(path => path !== moved);

        siblings.splice(
            siblings.indexOf(target) + (position === 'after' ? 1 : 0),
            0,
            moved,
        );
        orders.set(parent.path, siblings);
    }

    const paths: string[] = [];

    const walk = (node: WorkspaceFolderNode) => {
        for (const path of orders.get(node.path) ??
            node.folders.map(folder => folder.path)) {
            paths.push(path);
            walk(nodes.get(path) as WorkspaceFolderNode);
        }
    };

    walk(root);

    return paths;
};

/**
 * Lists the request ids of a folder after one request moved next to a sibling.
 * A request can only move among the requests of its own folder.
 * Returns null when the move does nothing.
 */
export const reorderRequestIds = (
    requests: WorkspaceSummary[],
    moved: string,
    target: string,
    position: 'before' | 'after',
): { folder: string; ids: string[] } | null => {
    const source = requests.find(request => request.id === moved);
    const sibling = requests.find(request => request.id === target);

    if (!source || !sibling || moved === target || source.folder !== sibling.folder) {
        return null;
    }

    const ids = requests
        .filter(request => request.folder === source.folder && request.id !== moved)
        .map(request => request.id);

    ids.splice(ids.indexOf(target) + (position === 'after' ? 1 : 0), 0, moved);

    return { folder: source.folder, ids };
};
