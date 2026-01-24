import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import { AuthorizationType } from '@/interfaces/generated';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { useSettingsStore } from '@/stores/core/useSettingsStore';

/*
 * Fixtures.
 */

const STORAGE_KEY = 'nimbus-user-preferences';

describe('useSettingsStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        window.localStorage.removeItem(STORAGE_KEY);
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('loads stored preferences when available', () => {
            // Arrange

            const preferences = {
                autoRefreshRoutes: false,
                maxHistoryLogs: 50,
                theme: 'dark' as const,
                defaultRequestBodyType: RequestBodyTypeEnum.JSON,
                defaultAuthorizationType: AuthorizationType.Bearer,
            };
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences));

            // Act

            const store = useSettingsStore();

            // Assert

            expect(store.preferences.autoRefreshRoutes).toBe(false);
            expect(store.preferences.theme).toBe('dark');
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('persists preference changes automatically', async () => {
            // Arrange

            const store = useSettingsStore();

            // Act

            store.updatePreference('theme', 'dark');
            await nextTick();

            // Assert

            const stored = JSON.parse(window.localStorage?.getItem(STORAGE_KEY) ?? '{}');
            expect(stored.theme).toBe('dark');
        });

        it('resets preferences to defaults', async () => {
            // Arrange

            const store = useSettingsStore();
            store.updatePreference('theme', 'dark');
            await nextTick();

            // Act

            store.resetPreferences();

            // Assert

            expect(store.preferences.theme).toBe('system');
        });
    });
});
