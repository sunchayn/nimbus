import { AuthorizationType } from '@/interfaces/generated';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { useSettingsStore } from '@/stores/core/useSettingsStore';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

const STORAGE_KEY = 'nimbus-user-preferences';

describe('useSettingsStore', () => {
    it('loads stored preferences when available', () => {
        window.localStorage.setItem(
            STORAGE_KEY,
            JSON.stringify({
                autoRefreshRoutes: false,
                maxHistoryLogs: 50,
                theme: 'dark',
                defaultRequestBodyType: RequestBodyTypeEnum.JSON,
                defaultAuthorizationType: AuthorizationType.Bearer,
            }),
        );

        const store = useSettingsStore();

        expect(store.preferences.autoRefreshRoutes).toBe(false);
        expect(store.preferences.maxHistoryLogs).toBe(50);
        expect(store.preferences.theme).toBe('dark');
        expect(store.preferences.defaultRequestBodyType).toBe(RequestBodyTypeEnum.JSON);
        expect(store.preferences.defaultAuthorizationType).toBe(AuthorizationType.Bearer);
    });

    it('persists preference changes automatically', async () => {
        const store = useSettingsStore();

        store.updatePreference('theme', 'dark');

        await nextTick();

        expect(JSON.parse(window.localStorage?.getItem(STORAGE_KEY) ?? '{}').theme).toBe('dark');
    });

    it('resets preferences to defaults', async () => {
        const store = useSettingsStore();

        store.updatePreference('theme', 'dark');

        await Promise.resolve();

        store.resetPreferences();

        expect(store.preferences.theme).toBe('system');
    });
});
