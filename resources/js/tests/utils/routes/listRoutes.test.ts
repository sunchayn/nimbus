import type { RouteDefinition, RoutesFolder } from '@/interfaces/routes/routes';
import { listRoutes } from '@/utils/routes';
import { describe, expect, it } from 'vitest';

const route = (method: string, endpoint: string): RouteDefinition => ({
    method,
    endpoint,
    shortEndpoint: endpoint,
    schema: { shape: {}, extractionErrors: null },
});

describe('listRoutes', () => {
    it('returns an empty list without routes', () => {
        // Arrange & Act & Assert

        expect(listRoutes(null)).toEqual([]);
    });

    it('lists routes of nested folders with the methods of the same endpoint', () => {
        // Arrange

        const get = route('GET', 'api/users');
        const post = route('POST', 'api/users');
        const show = route('GET', 'api/users/{user}');
        const folders: RoutesFolder[] = [
            {
                name: 'users',
                path: 'users',
                routes: [get, post],
                children: [
                    { name: 'show', path: 'users/show', routes: [show], children: [] },
                ],
            },
        ];

        // Act

        const listed = listRoutes({ v1: folders });

        // Assert

        expect(listed.map(item => item.route)).toEqual([get, post, show]);
        expect(listed[0].siblings).toEqual([get, post]);
        expect(listed[2].siblings).toEqual([show]);
    });
});
