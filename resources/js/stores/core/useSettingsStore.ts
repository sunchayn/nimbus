import { AuthorizationType } from '@/interfaces/generated';
import type { RequestBodyTypeEnum } from '@/interfaces/http';
import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';

export interface UserPreferences {
    // Application Behavior
    autoRefreshRoutes: boolean;
    maxHistoryLogs: number;

    // UI/UX Preferences
    theme: 'light' | 'dark' | 'system';

    // Request Defaults
    defaultRequestBodyType: RequestBodyTypeEnum | -1;
    defaultAuthorizationType: AuthorizationType;
}

const STORAGE_KEY = 'nimbus-user-preferences';

const defaultPreferences: UserPreferences = {
    autoRefreshRoutes: true,
    maxHistoryLogs: 100,
    theme: 'system',
    defaultRequestBodyType: -1,
    defaultAuthorizationType: AuthorizationType.CurrentUser,
};

export const useSettingsStore = defineStore('settings', () => {
    /*
     * State.
     */
    const preferences = ref<UserPreferences>({ ...defaultPreferences });

    /*
     * Computed.
     */
    const hasChanges = computed(() => {
        return JSON.stringify(preferences.value) !== JSON.stringify(defaultPreferences);
    });

    /*
     * Actions.
     */
    const loadPreferences = () => {
        try {
            const stored = window.localStorage.getItem(STORAGE_KEY);

            if (stored) {
                const parsed = JSON.parse(stored);

                preferences.value = parsed;
            }
        } catch (error) {
            console.error('Failed to load preferences:', error);
            preferences.value = { ...defaultPreferences };
        }
    };

    const savePreferences = () => {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(preferences.value));
        } catch (error) {
            console.error('Failed to save preferences:', error);
        }
    };

    const resetPreferences = () => {
        preferences.value = { ...defaultPreferences };
        savePreferences();
    };

    const updatePreference = <K extends keyof UserPreferences>(
        key: K,
        value: UserPreferences[K],
    ) => {
        preferences.value[key] = value;
    };

    /*
     * Auto-save on changes.
     */
    watch(
        preferences,
        () => {
            savePreferences();
        },
        { deep: true },
    );

    /*
     * Initialize.
     */
    loadPreferences();

    return {
        // State
        preferences,

        // Computed
        hasChanges,

        // Actions
        loadPreferences,
        savePreferences,
        resetPreferences,
        updatePreference,
    };
});
