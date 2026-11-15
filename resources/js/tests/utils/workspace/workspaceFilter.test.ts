import type { RouteDefinition } from '@/interfaces/routes';
import type { WorkspaceSummary } from '@/interfaces/workspace';
import { emptyRouteFilters } from '@/utils/routes';
import {
    buildWorkspaceTree,
    filterWorkspaceRequests,
    pruneEmptyFolders,
} from '@/utils/workspace';
import { describe, expect, it } from 'vitest';

const request = (overrides: Partial<WorkspaceSummary>): WorkspaceSummary => ({
    id: 'a',
    name: 'List users',
    method: 'GET',
    endpoint: '/users',
    route: null,
    file: 'users/list.json',
    folder: 'users',
    hash: 'x',
    ...overrides,
});

const requests = [
    request({ id: 'a' }),
    request({
        id: 'b',
        name: 'Create user',
        method: 'POST',
        endpoint: '/users',
        folder: 'admin',
    }),
];

const routes = [
    {
        method: 'GET',
        endpoint: '/users',
        metadata: { auth: 'required', middleware: ['auth'] },
    },
] as unknown as RouteDefinition[];

describe('filterWorkspaceRequests', () => {
    it('matches the keyword against the name, endpoint, folder and method', () => {
        expect(
            filterWorkspaceRequests(requests, 'create', emptyRouteFilters(), routes),
        ).toHaveLength(1);
        expect(
            filterWorkspaceRequests(requests, 'ADMIN', emptyRouteFilters(), routes),
        ).toHaveLength(1);
        expect(
            filterWorkspaceRequests(requests, '/users', emptyRouteFilters(), routes),
        ).toHaveLength(2);
        expect(
            filterWorkspaceRequests(requests, 'nothing', emptyRouteFilters(), routes),
        ).toHaveLength(0);
    });

    it('filters by method', () => {
        const filters = { ...emptyRouteFilters(), methods: ['POST'] };

        expect(
            filterWorkspaceRequests(requests, '', filters, routes).map(item => item.id),
        ).toEqual(['b']);
    });

    it('reads auth and middleware from the route of the request', () => {
        const filters = { ...emptyRouteFilters(), requiresAuth: true };

        expect(
            filterWorkspaceRequests(requests, '', filters, routes).map(item => item.id),
        ).toEqual(['a']);
    });
});

describe('pruneEmptyFolders', () => {
    it('drops the folders that hold no request', () => {
        const tree = buildWorkspaceTree(['users', 'admin', 'empty'], [requests[0]]);

        expect(pruneEmptyFolders(tree).folders.map(folder => folder.path)).toEqual([
            'users',
        ]);
    });
});
