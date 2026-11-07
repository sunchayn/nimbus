import CommandPalette from '@/components/domain/CommandPalette/CommandPalette.vue';
import { useCommandPalette } from '@/composables/ui/useCommandPalette';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';
import type * as VueRouter from 'vue-router';

/*
 * Fixtures.
 */

const route = (method: string, endpoint: string) => ({
    method,
    endpoint,
    shortEndpoint: endpoint,
    schema: { shape: {}, extractionErrors: null },
});

const stores = vi.hoisted(() => ({}) as Record<string, any> /* eslint-disable-line */);

vi.mock('@/stores', () => ({
    useRoutesStore: () => stores.routes,
    useTabsStore: () => stores.tabs,
    useRequestStore: () => stores.request,
    useConfigStore: () => stores.config,
    useSettingsStore: () => stores.settings,
    useRequestsHistoryStore: () => stores.history,
    useEnvironmentVariablesStore: () => stores.environments,
}));

const push = vi.fn();

vi.mock('vue-router', async importOriginal => ({
    ...(await importOriginal<typeof VueRouter>()),
    useRouter: () => ({ push, currentRoute: { value: { name: 'main' } } }),
}));

vi.mock('@/composables/environment/useEnvironmentSwitch', () => ({
    useEnvironmentSwitch: () => stores.switchEnvironment,
}));

let mounted: VueWrapper | null = null;

const open = async (mode: 'all' | 'routes' = 'all') => {
    const wrapper = mount(CommandPalette, { attachTo: document.body });

    mounted = wrapper;

    useCommandPalette().openPalette(mode);
    await flushPromises();

    return wrapper;
};

const options = () =>
    Array.from(document.querySelectorAll('[role="option"]')).map(
        option => option.textContent?.trim() ?? '',
    );

const choose = (label: string) =>
    Array.from(document.querySelectorAll<HTMLElement>('[role="option"]'))
        .find(option => option.textContent?.includes(label))
        ?.click();

describe('CommandPalette', () => {
    beforeAll(() => {
        Element.prototype.scrollIntoView = vi.fn();
    });

    beforeEach(() => {
        vi.clearAllMocks();

        stores.routes = reactive({
            routes: {
                v1: [
                    {
                        name: 'users',
                        path: 'users',
                        children: [],
                        routes: [route('GET', 'api/users'), route('POST', 'api/users')],
                    },
                ],
            },
        });
        stores.tabs = reactive({
            tabs: [{ id: 't1', title: 'api/orders', method: 'GET' }],
            activeTabId: 't1',
            setActiveTab: vi.fn(),
            closeTab: vi.fn(),
        });
        stores.request = reactive({
            hasActiveRequest: true,
            pendingRequestData: { transactionMode: false },
            initializeRequest: vi.fn(),
            updateTransactionMode: vi.fn(),
        });
        stores.config = reactive({
            applications: { main: 'Main', admin: 'Admin' },
            activeApplication: 'main',
        });
        stores.settings = reactive({
            preferences: { theme: 'light' },
            updatePreference: vi.fn(),
        });
        stores.history = reactive({ clearLogs: vi.fn() });
        stores.environments = reactive({
            enabledEnvironments: [{ id: 'staging', name: 'Staging' }],
            baseUrl: 'http://localhost',
            resolve: (value: string) => value,
        });
        stores.switchEnvironment = vi.fn();
    });

    afterEach(async () => {
        useCommandPalette().closePalette();
        await flushPromises();
        mounted?.unmount();
        mounted = null;
    });

    it('lists actions, tabs, routes, and environments', async () => {
        // Arrange & Act

        await open();

        // Assert

        const labels = options();

        expect(labels).toContain('New tab');
        expect(labels).toContain('GET api/orders');
        expect(labels).toContain('POST api/users');
        expect(labels).toContain('Switch environment to Staging');
        expect(labels).toContain('Switch application to Admin');
    });

    it('only lists routes in the route picker mode', async () => {
        // Arrange & Act

        await open('routes');

        // Assert

        expect(options()).toEqual(['GET api/users', 'POST api/users']);
    });

    it('opens the selected route and closes the palette', async () => {
        // Arrange

        await open();

        // Act

        choose('POST api/users');
        await flushPromises();

        // Assert

        expect(stores.request.initializeRequest).toHaveBeenCalledWith(
            expect.objectContaining({ method: 'POST', endpoint: 'api/users' }),
            expect.arrayContaining([expect.objectContaining({ method: 'GET' })]),
        );
        expect(useCommandPalette().isPaletteOpen.value).toBe(false);
    });

    it('restores the focus to the element focused before opening', async () => {
        // Arrange

        const trigger = document.createElement('button');

        document.body.appendChild(trigger);
        trigger.focus();

        await open();

        // Act

        useCommandPalette().closePalette();
        await flushPromises();
        await new Promise(resolve => setTimeout(resolve, 50));

        // Assert

        expect(document.activeElement).toBe(trigger);
        trigger.remove();
    });

    it('switches to an open tab', async () => {
        // Arrange

        await open();

        // Act

        choose('GET api/orders');

        // Assert

        expect(stores.tabs.setActiveTab).toHaveBeenCalledWith('t1');
    });

    it('switches the environment', async () => {
        // Arrange

        await open();

        // Act

        choose('Switch environment to Staging');

        // Assert

        expect(stores.switchEnvironment).toHaveBeenCalledWith('staging');
    });

    it('toggles the theme and transaction mode', async () => {
        // Arrange

        await open();

        // Act

        choose('Toggle theme');
        useCommandPalette().openPalette();
        await flushPromises();
        choose('Toggle transaction mode');

        // Assert

        expect(stores.settings.updatePreference).toHaveBeenCalledWith('theme', 'dark');
        expect(stores.request.updateTransactionMode).toHaveBeenCalledWith(true);
    });

    it('asks twice before clearing the history', async () => {
        // Arrange

        await open();

        // Act

        choose('Clear history');
        await flushPromises();

        // Assert

        expect(stores.history.clearLogs).not.toHaveBeenCalled();
        expect(useCommandPalette().isPaletteOpen.value).toBe(true);

        choose('Select again to clear the history');

        expect(stores.history.clearLogs).toHaveBeenCalledTimes(1);
    });

    it('opens the settings page', async () => {
        // Arrange

        await open();

        // Act

        choose('Open settings');

        // Assert

        expect(push).toHaveBeenCalledWith({ name: 'settings' });
    });

    it('opens the toolbox dialog', async () => {
        // Arrange

        await open();
        useCommandPalette().isToolboxOpen.value = false;

        // Act

        choose('Open toolbox');

        // Assert

        expect(useCommandPalette().isToolboxOpen.value).toBe(true);
        expect(useCommandPalette().isPaletteOpen.value).toBe(false);
    });
});
