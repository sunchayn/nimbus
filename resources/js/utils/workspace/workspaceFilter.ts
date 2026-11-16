import type { RouteDefinition } from '@/interfaces/routes/routes';
import type { WorkspaceFolderNode, WorkspaceSummary } from '@/interfaces/workspace';
import { type RouteFilters, routeMatchesFilters } from '@/utils/routes';

/**
 * Keeps the saved requests that match the keyword and the route filters.
 * The auth and middleware filters read the route of a request, so a request without a known route never passes them.
 */
export const filterWorkspaceRequests = (
    requests: WorkspaceSummary[],
    search: string,
    filters: RouteFilters,
    routes: RouteDefinition[],
): WorkspaceSummary[] => {
    const term = search.trim().toLowerCase();
    const needsRoute = filters.requiresAuth || filters.middleware.length > 0;

    return requests.filter(request => {
        if (
            term &&
            ![request.name, request.endpoint, request.folder, request.method].some(
                value => value.toLowerCase().includes(term),
            )
        ) {
            return false;
        }

        if (filters.methods.length && !filters.methods.includes(request.method)) {
            return false;
        }

        if (!needsRoute) {
            return true;
        }

        const route = routes.find(
            item => item.method === request.method && item.endpoint === request.endpoint,
        );

        return route !== undefined && routeMatchesFilters(route, filters);
    });
};

/**
 * Drops the folders that hold no request, at any depth.
 */
export const pruneEmptyFolders = (node: WorkspaceFolderNode): WorkspaceFolderNode => {
    const folders = node.folders
        .map(pruneEmptyFolders)
        .filter(folder => folder.items.length > 0 || folder.folders.length > 0);

    return { ...node, folders };
};
