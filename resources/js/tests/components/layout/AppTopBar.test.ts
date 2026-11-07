import AppTopBar from '@/components/layout/AppTopBar.vue';
import { useCommandPalette } from '@/composables/ui/useCommandPalette';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import type * as VueRouter from 'vue-router';

/*
 * Fixtures.
 */

vi.mock('@/stores', () => ({
    useEnvironmentVariablesStore: () => ({
        enabledEnvironments: [],
        activeEnvironment: { id: 'a', name: 'Local' },
        isCurrentHost: true,
    }),
}));

vi.mock('@/composables/environment/useEnvironmentSwitch', () => ({
    useEnvironmentSwitch: () => vi.fn(),
}));

vi.mock('vue-router', async importOriginal => ({
    ...(await importOriginal<typeof VueRouter>()),
    useRouter: () => ({ push: vi.fn() }),
    useRoute: () => ({ name: 'main' }),
}));

describe('AppTopBar', () => {
    afterEach(() => {
        useCommandPalette().isHelpOpen.value = false;
    });

    it('opens the shortcuts dialog from the help button', async () => {
        // Arrange

        const wrapper = mount(AppTopBar, { attachTo: document.body });

        // Act

        await wrapper.get('[data-testid="shortcuts-button"]').trigger('click');

        // Assert

        expect(useCommandPalette().isHelpOpen.value).toBe(true);
        wrapper.unmount();
    });
});
