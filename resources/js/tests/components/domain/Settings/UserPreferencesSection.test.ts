import UserPreferencesSection from '@/components/domain/Settings/UserPreferencesSection.vue';
import { useSettingsStore } from '@/stores';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

describe('UserPreferencesSection', () => {
    beforeEach(() => {
        localStorage.clear();
        setActivePinia(createPinia());
    });

    it('shows the current history limit', () => {
        const wrapper = mount(UserPreferencesSection);
        const input = wrapper.find('[data-testid="max-history-logs-input"]');

        expect((input.element as HTMLInputElement).value).toBe('100');
    });

    it('saves a valid history limit', async () => {
        const settingsStore = useSettingsStore();
        const wrapper = mount(UserPreferencesSection);

        await wrapper.find('[data-testid="max-history-logs-input"]').setValue('250');

        expect(settingsStore.preferences.maxHistoryLogs).toBe(250);
    });

    it('ignores an invalid history limit', async () => {
        const settingsStore = useSettingsStore();
        const wrapper = mount(UserPreferencesSection);

        await wrapper.find('[data-testid="max-history-logs-input"]').setValue('5');

        expect(settingsStore.preferences.maxHistoryLogs).toBe(100);
    });
});
