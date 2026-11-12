import ExplorerSidebar from '@/components/domain/Workspace/ExplorerSidebar.vue';
import { useConfigStore } from '@/stores';
import { createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const createWrapper = (workspaceEnabled: boolean) => {
    const pinia = createTestingPinia({ createSpy: vi.fn, stubActions: true });
    useConfigStore().workspaceEnabled = workspaceEnabled;

    return mount(ExplorerSidebar, {
        props: { routes: {}, loading: false, error: null, snapshotAt: null },
        global: {
            plugins: [pinia],
            stubs: { RouteExplorer: true, WorkspaceExplorer: true },
        },
    });
};

describe('ExplorerSidebar', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('shows only the routes when the workspace is off', () => {
        const wrapper = createWrapper(false);

        expect(wrapper.find('[data-testid="explorer-sidebar-tabs"]').exists()).toBe(
            false,
        );
        expect(wrapper.findComponent({ name: 'WorkspaceExplorer' }).exists()).toBe(false);
    });

    it('offers the Routes and Workspace tabs when the workspace is on', () => {
        const wrapper = createWrapper(true);
        const tabs = wrapper.find('[data-testid="explorer-sidebar-tabs"]');

        expect(tabs.text()).toContain('Routes');
        expect(tabs.text()).toContain('Workspace');
        expect(tabs.text()).not.toContain('Collections');
    });

    it('keeps both lists rendered and hides the one that is not selected', () => {
        const wrapper = createWrapper(true);

        const routes = wrapper.findComponent({ name: 'RouteExplorer' });
        const workspace = wrapper.findComponent({ name: 'WorkspaceExplorer' });

        expect(routes.exists()).toBe(true);
        expect(workspace.exists()).toBe(true);
        expect(routes.classes()).not.toContain('invisible');
        expect(workspace.classes()).toContain('invisible');
    });
});
