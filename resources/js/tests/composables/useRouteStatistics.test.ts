import { useRouteStatistics } from '@/composables/data/useRouteStatistics';
import { RouteDefinition } from '@/interfaces/routes/routes';
import { useRoutesStore } from '@/stores';
import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('@/stores', () => ({
    useRoutesStore: vi.fn(),
}));

const mockRoutesStore = {
    routes: null as {
        [key: string]: Array<{ resource: string; routes: RouteDefinition[] }>;
    } | null,
};

describe('useRouteStatistics', () => {
    beforeEach(() => {
        /* eslint-disable  @typescript-eslint/no-explicit-any */
        vi.mocked(useRoutesStore).mockReturnValue(mockRoutesStore as any);
        /* eslint-enable  @typescript-eslint/no-explicit-any */

        mockRoutesStore.routes = null;
    });

    describe('routeStatistics computed', () => {
        it('returns zero statistics when no routes exist', () => {
            mockRoutesStore.routes = null;

            const { routeStatistics } = useRouteStatistics();

            expect(routeStatistics.value).toEqual({
                total: 0,
                withErrors: 0,
                withoutErrors: 0,
                errorRate: 0,
            });
        });

        it('returns zero statistics when routes object is empty', () => {
            mockRoutesStore.routes = {};

            const { routeStatistics } = useRouteStatistics();

            expect(routeStatistics.value).toEqual({
                total: 0,
                withErrors: 0,
                withoutErrors: 0,
                errorRate: 0,
            });
        });

        it('calculates statistics correctly for routes without errors', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                            {
                                method: 'POST',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
            };

            const { routeStatistics } = useRouteStatistics();

            expect(routeStatistics.value).toEqual({
                total: 2,
                withErrors: 0,
                withoutErrors: 2,
                errorRate: 0,
            });
        });

        it('calculates statistics correctly for routes with errors', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: 'Schema error',
                                },
                            },
                            {
                                method: 'POST',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
            };

            const { routeStatistics } = useRouteStatistics();

            expect(routeStatistics.value).toEqual({
                total: 2,
                withErrors: 1,
                withoutErrors: 1,
                errorRate: 50,
            });
        });

        it('calculates error rate correctly with rounding', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: 'Error',
                                },
                            },
                            {
                                method: 'POST',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                            {
                                method: 'PUT',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
            };

            const { routeStatistics } = useRouteStatistics();

            expect(routeStatistics.value.errorRate).toBe(33);
        });

        it('handles multiple versions correctly', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
                v2: [
                    {
                        resource: 'posts',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/posts',
                                shortEndpoint: 'api/posts',
                                schema: {
                                    shape: {},
                                    extractionErrors: 'Error',
                                },
                            },
                        ],
                    },
                ],
            };

            const { routeStatistics } = useRouteStatistics();

            expect(routeStatistics.value).toEqual({
                total: 2,
                withErrors: 1,
                withoutErrors: 1,
                errorRate: 50,
            });
        });
    });

    describe('displayableRoutesWithErrors computed', () => {
        it('returns empty array when no routes exist', () => {
            mockRoutesStore.routes = null;

            const { displayableRoutesWithErrors } = useRouteStatistics();

            expect(displayableRoutesWithErrors.value).toEqual([]);
        });

        it('returns empty array when no routes have errors', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
            };

            const { displayableRoutesWithErrors } = useRouteStatistics();

            expect(displayableRoutesWithErrors.value).toEqual([]);
        });

        it('returns routes with errors including resource and version metadata', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: 'Schema extraction failed',
                                },
                            },
                            {
                                method: 'POST',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
            };

            const { displayableRoutesWithErrors } = useRouteStatistics();

            expect(displayableRoutesWithErrors.value).toHaveLength(1);
            expect(displayableRoutesWithErrors.value[0]).toMatchObject({
                method: 'GET',
                endpoint: 'api/users',
                resource: 'users',
                version: 'v1',
                schema: {
                    extractionErrors: 'Schema extraction failed',
                },
            });
        });

        it('filters out routes without errors across multiple versions', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: 'Error',
                                },
                            },
                            {
                                method: 'POST',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: null,
                                },
                            },
                        ],
                    },
                ],
                v2: [
                    {
                        resource: 'posts',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/posts',
                                shortEndpoint: 'api/posts',
                                schema: {
                                    shape: {},
                                    extractionErrors: 'Another error',
                                },
                            },
                        ],
                    },
                ],
            };

            const { displayableRoutesWithErrors } = useRouteStatistics();

            expect(displayableRoutesWithErrors.value).toHaveLength(2);
            expect(displayableRoutesWithErrors.value[0].version).toBe('v1');
            expect(displayableRoutesWithErrors.value[1].version).toBe('v2');
        });

        it('handles empty string extraction errors as valid errors', () => {
            mockRoutesStore.routes = {
                v1: [
                    {
                        resource: 'users',
                        routes: [
                            {
                                method: 'GET',
                                endpoint: 'api/users',
                                shortEndpoint: 'api/users',
                                schema: {
                                    shape: {},
                                    extractionErrors: '',
                                },
                            },
                        ],
                    },
                ],
            };

            const { displayableRoutesWithErrors } = useRouteStatistics();

            expect(displayableRoutesWithErrors.value).toHaveLength(0);
        });
    });
});
