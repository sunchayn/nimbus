import RouteExplorerBranding from '@/components/domain/RoutesExplorer/RouteExplorerBranding.vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

const mockConfigStore = reactive({ appName: 'Nimbus' });

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useConfigStore: () => mockConfigStore,
    };
});

describe('RouteExplorerBranding', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        mockConfigStore.appName = 'Nimbus';
    });

    it('renders the configured application name', () => {
        mockConfigStore.appName = 'Fliip App';

        const wrapper = mount(RouteExplorerBranding);

        expect(wrapper.text()).toBe('Fliip App');
    });

    it('keeps the brand heading classes', () => {
        const wrapper = mount(RouteExplorerBranding);

        expect(wrapper.get('div').classes()).toEqual(
            expect.arrayContaining([
                'text-foreground',
                'flex-1',
                'text-xl',
                'font-medium',
            ]),
        );
    });
});
