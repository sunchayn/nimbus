/**
 * Route processing utilities
 */

export {
    calculateTotalRouteCount,
    findRouteByName,
    findRoutesByEndpoint,
    listRoutes,
    processRoutesData,
    searchRoutes,
} from './routeProcessor';

export { describeRouteParameter, describeThrottle, isSignedRoute } from './routeMetadata';

export {
    collectMethods,
    collectMiddleware,
    collectRoutes,
    emptyRouteFilters,
    filterFolders,
    hasActiveRouteFilters,
    routeMatchesFilters,
    type RouteFilters,
} from './routeFilters';

export { buildRoutesTree, routeKey } from './routesTree';
