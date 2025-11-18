import { useConfigStore } from '@/stores/core/useConfigStore';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { NimbusConfig } from '../../../types/global';

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

    it('reads configuration from Nimbus global', () => {
        window.Nimbus = {
            apiBaseUrl: 'https://example.com',
            basePath: '/nimbus',
            isVersioned: true,
            headers: JSON.stringify([{ header: 'X-Test', type: 'raw', value: '123' }]),
            currentUser: JSON.stringify({ id: 99 }),
            routes: '',
            routeExtractorException: null,
        };

        const store = useConfigStore();

        expect(store.apiUrl).toBe('https://example.com');
        expect(store.appBasePath).toBe('/nimbus');
        expect(store.headers).toEqual([{ header: 'X-Test', type: 'raw', value: '123' }]);
        expect(store.isVersioned).toBe(true);
        expect(store.isLoggedIn).toBe(true);
        expect(store.userId).toBe(99);
    });
});
