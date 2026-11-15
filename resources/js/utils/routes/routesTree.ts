import type { ExplorerNode } from '@/interfaces/explorer';
import type { RouteDefinition, RoutesFolder } from '@/interfaces/routes/routes';

const toNode = (folder: RoutesFolder): ExplorerNode<RouteDefinition> => ({
    name: folder.name,
    path: folder.path,
    folders: folder.children.map(toNode),
    items: folder.routes,
});

/**
 * Builds the explorer tree of a list of route folders, under a root folder with an empty path.
 */
export const buildRoutesTree = (
    folders: RoutesFolder[],
): ExplorerNode<RouteDefinition> => ({
    name: '',
    path: '',
    folders: folders.map(toNode),
    items: [],
});

/**
 * Gives the key of a route, which is its method and its endpoint.
 */
export const routeKey = (route: RouteDefinition): string =>
    `${route.method} ${route.endpoint}`;
