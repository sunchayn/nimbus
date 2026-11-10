import type { RouteDefinition, RoutesFolder } from '@/interfaces/routes/routes';
import {
    collectMethods,
    collectMiddleware,
    collectRoutes,
    emptyRouteFilters,
    filterFolders,
    hasActiveRouteFilters,
    routeMatchesFilters,
} from '@/utils/routes/routeFilters';
import { describe, expect, it } from 'vitest';

const route = (
    method: string,
    endpoint: string,
    metadata: RouteDefinition['metadata'] = {},
): RouteDefinition => ({
    method,
    endpoint,
    shortEndpoint: endpoint,
    schema: { shape: {}, extractionErrors: null },
    metadata,
});

const getUsers = route('GET', 'users', {
    name: 'users.index',
    auth: 'required',
    middleware: ['auth'],
});
const postUsers = route('POST', 'users', {
    name: 'users.store',
    middleware: ['throttle'],
});
const ping = route('GET', 'ping');

const folder: RoutesFolder = {
    name: 'users',
    path: 'users',
    routes: [getUsers, postUsers],
    children: [{ name: 'misc', path: 'users/misc', routes: [ping], children: [] }],
};

describe('routeFilters', () => {
    it('has no active filter by default', () => {
        expect(hasActiveRouteFilters(emptyRouteFilters())).toBe(false);
        expect(routeMatchesFilters(ping, emptyRouteFilters())).toBe(true);
    });

    it('filters by method', () => {
        const filters = { ...emptyRouteFilters(), methods: ['POST'] };

        expect(routeMatchesFilters(postUsers, filters)).toBe(true);
        expect(routeMatchesFilters(getUsers, filters)).toBe(false);
    });

    it('filters by auth requirement', () => {
        const filters = { ...emptyRouteFilters(), requiresAuth: true };

        expect(routeMatchesFilters(getUsers, filters)).toBe(true);
        expect(routeMatchesFilters(ping, filters)).toBe(false);
    });

    it('filters by middleware', () => {
        const filters = { ...emptyRouteFilters(), middleware: ['throttle'] };

        expect(routeMatchesFilters(postUsers, filters)).toBe(true);
        expect(routeMatchesFilters(getUsers, filters)).toBe(false);
        expect(routeMatchesFilters(ping, filters)).toBe(false);
    });

    it('requires every active filter to pass', () => {
        const filters = { ...emptyRouteFilters(), methods: ['GET'], requiresAuth: true };

        expect(routeMatchesFilters(getUsers, filters)).toBe(true);
        expect(routeMatchesFilters(ping, filters)).toBe(false);
    });

    it('drops folders left empty', () => {
        const result = filterFolders([folder], candidate => candidate.method === 'POST');

        expect(result).toHaveLength(1);
        expect(result[0].routes).toEqual([postUsers]);
        expect(result[0].children).toEqual([]);
        expect(filterFolders([folder], () => false)).toEqual([]);
    });

    it('collects routes, methods and middleware', () => {
        const routes = collectRoutes([folder]);

        expect(routes).toHaveLength(3);
        expect(collectMethods(routes)).toEqual(['GET', 'POST']);
        expect(collectMiddleware(routes)).toEqual(['auth', 'throttle']);
    });
});
