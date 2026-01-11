/* eslint-disable @typescript-eslint/no-explicit-any */

import { RoutesGroup } from '@/interfaces';
import { useRoutesStore } from '@/stores/routes/useRoutesStore';
import { SourceRouteConfigArray } from '@/utils/routes/route-processor';
import { beforeEach, describe, expect, it, MockedFunction, vi } from 'vitest';
import { NimbusConfig } from '../../../types/global';

// Mock the utility functions
vi.mock('@/utils/routes', async () => {
    // Import the real module first
    const original: any = await vi.importActual('@/utils/routes');

    return {
        ...original,
        parseRouteExtractionException: vi.fn(exception => {
            if (!exception) {
                return null;
            }

            return { message: exception, type: 'extraction_error' };
        }),
        processRoutesData: vi.fn(data => data),
        searchRoutes: vi.fn(() => []),
    };
});

// Mock window.Nimbus
const mockWindowNimbus: Pick<NimbusConfig, 'routes' | 'routeExtractorException'> = {
    routes: null,
    routeExtractorException: null,
};

Object.defineProperty(window, 'Nimbus', {
    value: mockWindowNimbus,
    writable: true,
});

const undoProcessRoutesDataMock = async () => {
    const { processRoutesData: originalProcess } =
        await vi.importActual('@/utils/routes');
    const { processRoutesData } = await import('@/utils/routes');

    // Temporarily replace the mocked function with the original
    (processRoutesData as MockedFunction<any>).mockImplementation(
        // @ts-expect-error won't annotate `originalProcess`
        originalProcess,
    );
};

describe('useRoutesStore', () => {
    let store: ReturnType<typeof useRoutesStore>;

    beforeEach(() => {
        store = useRoutesStore();

        // Reset window.Nimbus
        mockWindowNimbus.routes = null;
        mockWindowNimbus.routeExtractorException = null;
    });

    describe('initial state', () => {
        it('should initialize with correct default state', () => {
            expect(store.routes).toBeNull();
            expect(store.routeExtractorException).toBeNull();
            expect(store.isLoading).toBe(false);
            expect(store.error).toBeNull();
        });

        it('should have correct computed properties initially', () => {
            expect(store.hasRoutes).toBe(false);
            expect(store.hasExtractionError).toBe(false);
            expect(store.routeVersions).toEqual([]);
            expect(store.totalRouteCount).toBe(0);
        });
    });

    describe('fetchAvailableRoutes', () => {
        it('should fetch routes successfully', async () => {
            const mockRoutesConfigSource: SourceRouteConfigArray = {
                v1: {
                    users: [
                        {
                            methods: ['GET', 'HEAD'],
                            uri: '/api/users',
                            shortUri: '/api/users',
                            extractionError: null,
                            schema: {},
                        },
                        {
                            methods: ['POST', 'PUT'],
                            uri: '/api/users',
                            shortUri: '/api/users',
                            extractionError: null,
                            schema: {},
                        },
                    ],
                },
            };

            const expectedOutput: { [key: string]: RoutesGroup[] } = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: '/api/users',
                                shortEndpoint: '/api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                            {
                                method: 'HEAD',
                                endpoint: '/api/users',
                                shortEndpoint: '/api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                            {
                                method: 'POST',
                                endpoint: '/api/users',
                                shortEndpoint: '/api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                            {
                                method: 'PUT',
                                endpoint: '/api/users',
                                shortEndpoint: '/api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
            };

            mockWindowNimbus.routes = JSON.stringify(mockRoutesConfigSource);

            await undoProcessRoutesDataMock();

            await store.fetchAvailableRoutes();

            expect(store.isLoading).toBe(false);
            expect(store.error).toBeNull();
            expect(store.routes).toEqual(expectedOutput);
        });

        it('should handle non-string routes source', async () => {
            // @ts-expect-error asserting edge case.
            mockWindowNimbus.routes = { some: 'object' };

            await store.fetchAvailableRoutes();

            expect(store.routes).toBeNull();
            expect(store.error).toBeNull();
        });

        it('should handle invalid JSON in routes', async () => {
            mockWindowNimbus.routes = 'invalid json';

            await store.fetchAvailableRoutes();

            expect(store.routes).toBeNull();
            expect(store.error).toBe(
                'Unexpected token \'i\', "invalid json" is not valid JSON',
            );
        });

        it('should handle fetch errors', async () => {
            mockWindowNimbus.routes = '{"valid": "json"}';

            // Mock processRoutesData to throw an error
            const { processRoutesData } = await import('@/utils/routes');
            (processRoutesData as MockedFunction<any>).mockImplementation(() => {
                throw new Error('Processing failed');
            });

            await store.fetchAvailableRoutes();

            expect(store.routes).toBeNull();
            expect(store.error).toBe('Processing failed');
        });

        it('should set loading state correctly', async () => {
            mockWindowNimbus.routes = JSON.stringify({});

            expect(store.isLoading).toBe(false);

            // Fake loading timeout.
            const delay = (ms: number) => new Promise(res => setTimeout(res, ms));
            const { processRoutesData } = await import('@/utils/routes');
            (processRoutesData as MockedFunction<any>).mockImplementation(async () => {
                await delay(50);
            });

            const fetchPromise = store.fetchAvailableRoutes();

            expect(store.isLoading).toBe(true);

            await fetchPromise;
            expect(store.isLoading).toBe(false);
        });
    });

    describe('initializeRoutes', () => {
        it.skip('should fetch routes', async () => {
            // TODO [Test] Check why the spy is not working.
            // const mockRoutesData = { v1: [] };

            await store.initializeRoutes();

            expect(store.fetchAvailableRoutes).toHaveBeenCalled();
        });

        it('should parse route extraction exception', () => {
            const mockException = 'Route extraction failed';
            mockWindowNimbus.routeExtractorException = mockException;

            store.initializeRoutes();

            expect(store.routeExtractorException).toEqual({
                message: mockException,
                type: 'extraction_error',
            });
        });

        it('should handle null route extraction exception', () => {
            mockWindowNimbus.routeExtractorException = null;

            store.initializeRoutes();

            expect(store.routeExtractorException).toBeNull();
        });
    });

    describe('resetRoutesState', () => {
        it('should reset all state to initial values', () => {
            store.routes = { v1: [] };

            store.routeExtractorException = {
                exception: {
                    message: 'error',
                },
                routeContext: {},
            };

            store.error = 'Some error';

            store.resetRoutesState();

            expect(store.routes).toBeNull();
            expect(store.routeExtractorException).toBeNull();
            expect(store.error).toBeNull();
        });
    });

    describe('computed properties', () => {
        it('should compute hasRoutes correctly', () => {
            expect(store.hasRoutes).toBe(false);

            store.routes = {};
            expect(store.hasRoutes).toBe(false);

            store.routes = { v1: [] };
            expect(store.hasRoutes).toBe(true);
        });

        it('should compute hasExtractionError correctly', () => {
            expect(store.hasExtractionError).toBe(false);

            store.routeExtractorException = {
                exception: {
                    message: 'error',
                },
                routeContext: {},
            };

            expect(store.hasExtractionError).toBe(true);
        });

        it('should compute routeVersions correctly', () => {
            expect(store.routeVersions).toEqual([]);

            store.routes = { v1: [], v2: [] };
            expect(store.routeVersions).toEqual(['v1', 'v2']);
        });

        it('should compute totalRouteCount correctly', async () => {
            expect(store.totalRouteCount).toBe(0);

            // @ts-expect-error we care about the count only not structure.
            store.routes = { v1: [{ resource: 'users', routes: [{}, {}] }] };
            expect(store.totalRouteCount).toBe(2);
        });

        it('should compute getRoutesByVersion correctly', () => {
            store.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [],
                    },
                ],
                v2: [
                    {
                        resource: 'posts',
                        routes: [],
                    },
                ],
            };

            expect(store.getRoutesByVersion('v1')).toEqual([
                { resource: 'users', routes: [] },
            ]);
            expect(store.getRoutesByVersion('v2')).toEqual([
                { resource: 'posts', routes: [] },
            ]);
            expect(store.getRoutesByVersion('v3')).toEqual([]);
        });

        it('should compute searchRoutes correctly', async () => {
            const { searchRoutes } = await import('@/utils/routes');

            const mockRoutes = { v1: [] };
            store.routes = mockRoutes;

            (searchRoutes as MockedFunction<any>).mockImplementation(() => [
                'fake-results',
            ]);

            const result = store.searchRoutes('test');

            // Assert the base `searchRoutes` function was called properly.
            expect(searchRoutes).toHaveBeenCalledWith(mockRoutes, 'test');
            expect(result).toEqual(['fake-results']);
        });
    });

    describe('edge cases', () => {
        beforeEach(async () => {
            await undoProcessRoutesDataMock();
        });

        it('should handle empty routes object', async () => {
            mockWindowNimbus.routes = JSON.stringify({});

            await store.fetchAvailableRoutes();

            expect(store.routes).toEqual({});
            expect(store.hasRoutes).toBe(false);
        });

        it('should handle routes with empty groups', async () => {
            const mockRoutesData = {
                v1: {
                    users: [],
                    posts: [],
                },
            };

            mockWindowNimbus.routes = JSON.stringify(mockRoutesData);

            await store.fetchAvailableRoutes();

            expect(store.routes).toEqual({
                v1: [
                    { resource: 'posts', routes: [] }, // <- also sorted.
                    { resource: 'users', routes: [] },
                ],
            });

            expect(store.hasRoutes).toBe(true);
        });

        it('should handle malformed route data gracefully', async () => {
            mockWindowNimbus.routes = JSON.stringify({
                v1: {
                    users: [],
                    posts: null,
                },
            });

            await store.fetchAvailableRoutes();

            expect(store.routes).toEqual(null);
        });

        it('should handle concurrent fetch calls', async () => {
            mockWindowNimbus.routes = JSON.stringify({ v1: [] });

            const fetch1 = store.fetchAvailableRoutes();
            const fetch2 = store.fetchAvailableRoutes();

            await Promise.all([fetch1, fetch2]);

            expect(store.routes).toEqual({ v1: [] });
        });
    });
});
