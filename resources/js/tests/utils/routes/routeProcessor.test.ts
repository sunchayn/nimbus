import {
    calculateTotalRouteCount,
    processRoutesData,
    type SourceFolder,
    type SourceRouteConfig,
} from '@/utils/routes/routeProcessor';
import { describe, expect, it } from 'vitest';

const sourceRoute = (shortUri: string, methods = ['GET']): SourceRouteConfig => ({
    uri: `api/${shortUri}`,
    shortUri,
    methods,
    schema: {},
    extractionError: null,
    metadata: {},
    keywords: [],
});

const sourceFolders: SourceFolder[] = [
    {
        name: 'creator',
        path: 'creator',
        routes: [sourceRoute('creator/profile'), sourceRoute('creator/avatar')],
        children: [
            {
                name: 'auth',
                path: 'creator/auth',
                routes: [],
                children: [
                    {
                        name: 'password',
                        path: 'creator/auth/password',
                        routes: [
                            sourceRoute('creator/auth/password/reset', ['POST', 'PUT']),
                        ],
                        children: [],
                    },
                ],
            },
        ],
    },
];

describe('processRoutesData', () => {
    it('keeps the folder tree of each version', async () => {
        const result = await processRoutesData({ v1: sourceFolders });

        expect(result.v1).toHaveLength(1);
        expect(result.v1[0].children[0].children[0].path).toBe('creator/auth/password');
    });

    it('creates one route per method and sorts them inside their folder', async () => {
        const result = await processRoutesData({ v1: sourceFolders });

        expect(result.v1[0].routes.map(route => route.shortEndpoint)).toEqual([
            'creator/avatar',
            'creator/profile',
        ]);
        expect(
            result.v1[0].children[0].children[0].routes.map(route => route.method),
        ).toEqual(['POST', 'PUT']);
    });
});

describe('calculateTotalRouteCount', () => {
    it('counts routes in nested folders', async () => {
        const routes = await processRoutesData({ v1: sourceFolders });

        expect(calculateTotalRouteCount(routes)).toBe(4);
    });
});
