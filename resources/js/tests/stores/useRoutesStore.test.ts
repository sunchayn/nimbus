import { useRoutesStore } from '@/stores/routes/useRoutesStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { NimbusConfig } from '../../../types/global';

/*
 * Fixtures.
 */

vi.mock('@/utils/routes', async () => {
    const original = (await vi.importActual('@/utils/routes')) as Record<string, unknown>;

    return {
        ...original,
        parseRouteExtractionException: vi.fn(exception => {
            return exception ? { message: exception, type: 'extraction_error' } : null;
        }),
        processRoutesData: vi.fn(data => data),
        searchRoutes: vi.fn(() => []),
    };
});

const mockWindowNimbus: Pick<NimbusConfig, 'routes' | 'routeExtractorException'> = {
    routes: null,
    routeExtractorException: null,
};

Object.defineProperty(window, 'Nimbus', {
    value: mockWindowNimbus,
    writable: true,
});

describe('useRoutesStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        mockWindowNimbus.routes = null;
        mockWindowNimbus.routeExtractorException = null;
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
            expect(store.isLoading).toBe(false);
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
