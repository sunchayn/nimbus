import { useErrorStore } from '@/stores/core/useErrorStore';
import { useRoutesStore } from '@/stores/routes/useRoutesStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const fetchMock = vi.fn();

vi.stubGlobal('fetch', fetchMock);

Object.defineProperty(window, 'Nimbus', {
    value: { basePath: '/nimbus' },
    writable: true,
});

const jsonResponse = (body: unknown, status = 200) =>
    ({ ok: status < 400, status, json: async () => body }) as Response;

describe('useRoutesStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('should initialize with correct default state', () => {
            // Act

            const store = useRoutesStore();

            // Assert

            expect(store.routes).toBeNull();
            expect(store.isLoading).toBe(true);
            expect(store.error).toBeNull();
        });

        it('should have correct computed properties initially', () => {
            // Act

            const store = useRoutesStore();

            // Assert

            expect(store.hasRoutes).toBe(false);
            expect(store.hasExtractionError).toBe(false);
        });
    });

    /*
     * Loading tests.
     */

    describe('initializeRoutes', () => {
        it('fetches the routes from the endpoint and stops loading', async () => {
            // Arrange

            const store = useRoutesStore();

            fetchMock.mockResolvedValue(jsonResponse({ v1: [] }));

            // Act

            const loading = store.initializeRoutes();

            expect(store.isLoading).toBe(true);

            await loading;

            // Assert

            expect(fetchMock).toHaveBeenCalledWith(
                '/nimbus/api/routes',
                expect.objectContaining({ credentials: 'same-origin' }),
            );
            expect(store.routes).toEqual({ v1: [] });
            expect(store.isLoading).toBe(false);
            expect(store.error).toBeNull();
        });

        it('keeps the extraction exception sent by the endpoint', async () => {
            // Arrange

            const store = useRoutesStore();
            const exception = { exception: { message: 'Broken route' } };

            fetchMock.mockResolvedValue(
                jsonResponse({ routeExtractorException: exception }, 500),
            );

            // Act

            await store.initializeRoutes();

            // Assert

            expect(store.routes).toBeNull();
            expect(store.routeExtractorException).toEqual(exception);
            expect(store.hasExtractionError).toBe(true);
            expect(store.isLoading).toBe(false);
        });

        it('hands a global exception to the error store', async () => {
            // Arrange

            const store = useRoutesStore();
            const exception = { exception: { message: 'Missing package' } };

            fetchMock.mockResolvedValue(
                jsonResponse({ globalException: exception }, 500),
            );

            // Act

            await store.initializeRoutes();

            // Assert

            expect(useErrorStore().globalError).toEqual(exception);
            expect(store.isLoading).toBe(false);
        });

        it('reports a network failure and can retry', async () => {
            // Arrange

            const store = useRoutesStore();

            fetchMock.mockRejectedValueOnce(new Error('Network down'));

            // Act

            await store.initializeRoutes();

            // Assert

            expect(store.error).toBe('Network down');
            expect(store.routes).toBeNull();
            expect(store.isLoading).toBe(false);

            // Act

            fetchMock.mockResolvedValue(jsonResponse({ v1: [] }));

            await store.initializeRoutes();

            // Assert

            expect(store.error).toBeNull();
            expect(store.routes).toEqual({ v1: [] });
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('should reset all state to initial values', () => {
            // Arrange

            const store = useRoutesStore();
            store.routes = { v1: [] };
            store.error = 'Some error';

            // Act

            store.resetRoutesState();

            // Assert

            expect(store.routes).toBeNull();
            expect(store.error).toBeNull();
        });

        it('should compute routeVersions correctly', () => {
            // Arrange

            const store = useRoutesStore();

            // Act

            store.routes = { v1: [], v2: [] };

            // Assert

            expect(store.routeVersions).toEqual(['v1', 'v2']);
        });
    });
});
