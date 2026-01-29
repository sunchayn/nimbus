import { useConfigStore } from '@/stores/core/useConfigStore';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import type { NimbusConfig } from '../../../types/global';

/*
 * Fixtures.
 */

declare global {
    interface Window {
        Nimbus: NimbusConfig;
    }
}

describe('useConfigStore', () => {
    const originalNimbus = window.Nimbus;

    beforeEach(() => {
        setActivePinia(createPinia());
    });

    afterEach(() => {
        window.Nimbus = originalNimbus;
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('reads configuration from Nimbus global', () => {
            // Arrange

            window.Nimbus = {
                apiBaseUrl: 'https://example.com',
                basePath: '/nimbus',
                isVersioned: true,
                headers: JSON.stringify([
                    { header: 'X-Test', type: 'raw', value: '123' },
                ]),
                currentUser: JSON.stringify({ id: 99 }),
                routes: '',
                routeExtractorException: null,
                applications: JSON.stringify({ main: 'Main API' }),
                activeApplication: 'main',
                sharedState: null,
                primaryProcessorName: null,
                showOperationId: null,
                globalException: null,
            };

            // Act

            const store = useConfigStore();

            // Assert

            expect(store.apiUrl).toBe('https://example.com');
            expect(store.appBasePath).toBe('/nimbus');
            expect(store.headers).toEqual([
                { header: 'X-Test', type: 'raw', value: '123' },
            ]);
            expect(store.isVersioned).toBe(true);
            expect(store.isLoggedIn).toBe(true);
            expect(store.userId).toBe(99);
        });
    });
});
