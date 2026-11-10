import type { RouteDefinition, RoutesFolder } from '@/interfaces/routes/routes';

export interface RouteFilters {
    methods: string[];
    requiresAuth: boolean;
    middleware: string[];
}

export const emptyRouteFilters = (): RouteFilters => ({
    methods: [],
    requiresAuth: false,
    middleware: [],
});

export const hasActiveRouteFilters = (filters: RouteFilters): boolean =>
    filters.methods.length > 0 || filters.requiresAuth || filters.middleware.length > 0;

/**
 * A route matches when it passes every active filter.
 * Several methods or several middleware match when any of them is present.
 */
export const routeMatchesFilters = (
    route: RouteDefinition,
    filters: RouteFilters,
): boolean => {
    if (filters.methods.length && !filters.methods.includes(route.method)) {
        return false;
    }

    if (filters.requiresAuth && route.metadata?.auth !== 'required') {
        return false;
    }

    if (
        filters.middleware.length &&
        !filters.middleware.some(name => route.metadata?.middleware?.includes(name))
    ) {
        return false;
    }

    return true;
};

/**
 * Keeps the routes that pass the predicate and drops the folders left with nothing.
 */
export const filterFolders = (
    folders: RoutesFolder[],
    predicate: (route: RouteDefinition) => boolean,
): RoutesFolder[] =>
    folders
        .map((folder): RoutesFolder | null => {
            const routes = folder.routes.filter(predicate);
            const children = filterFolders(folder.children, predicate);

            return routes.length > 0 || children.length > 0
                ? { ...folder, routes, children }
                : null;
        })
        .filter((folder): folder is RoutesFolder => folder !== null);

export const collectRoutes = (folders: RoutesFolder[]): RouteDefinition[] =>
    folders.flatMap(folder => [...folder.routes, ...collectRoutes(folder.children)]);

export const collectMethods = (routes: RouteDefinition[]): string[] =>
    [...new Set(routes.map(route => route.method))].sort();

export const collectMiddleware = (routes: RouteDefinition[]): string[] =>
    [...new Set(routes.flatMap(route => route.metadata?.middleware ?? []))].sort();
