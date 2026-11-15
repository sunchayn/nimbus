import WorkspaceExplorer from '@/components/domain/Workspace/Explorer/WorkspaceExplorer.vue';
import { useTabsStore } from '@/stores/request/useTabsStore';
import { useWorkspaceStore } from '@/stores/workspace/useWorkspaceStore';
import { createTestingPinia } from '@pinia/testing';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

const summary = {
    id: 'request-1234',
    name: 'Create user',
    method: 'POST',
    endpoint: '/api/users',
    route: 'users.store',
    file: 'requests/create-user.request.json',
    folder: '',
    hash: 'hash-1',
};

const createWrapper = (state: Record<string, unknown>) => {
    const pinia = createTestingPinia({
        createSpy: vi.fn,
        stubActions: true,
        initialState: { workspace: state },
    });

    // Stubbed actions return nothing, and every folder is open unless collapsed.
    vi.mocked(useWorkspaceStore().isFolderOpen).mockReturnValue(true);

    const wrapper = mount(WorkspaceExplorer, {
        global: { plugins: [pinia] },
        attachTo: document.body,
    });

    return { wrapper, store: useWorkspaceStore() };
};

const openMenu = async (
    wrapper: ReturnType<typeof createWrapper>['wrapper'],
    selector: string,
) => {
    await wrapper.find(selector).trigger('contextmenu');
    await flushPromises();
};

const clickMenuItem = (label: string) =>
    [...document.body.querySelectorAll<HTMLElement>('[role="menuitem"]')]
        .find(item => item.textContent?.trim() === label)
        ?.click();

describe('WorkspaceExplorer', () => {
    it('loads the workspace when it mounts', () => {
        const { store } = createWrapper({});

        expect(store.load).toHaveBeenCalled();
    });

    it('shows a hint when nothing is saved', () => {
        const { wrapper } = createWrapper({
            listing: {
                folders: [],
                requests: [],
            },
        });

        expect(wrapper.find('[data-testid="workspace-empty"]').exists()).toBe(true);
    });

    it('opens a saved request on click', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: [],
                requests: [summary],
            },
        });

        await wrapper.find('[data-testid="workspace-item"] button').trigger('click');

        expect(store.open).toHaveBeenCalledWith('request-1234');
    });

    it('deletes a request only after the confirmation', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: [],
                requests: [summary],
            },
        });

        await openMenu(wrapper, '[data-testid="workspace-item"] > div');
        clickMenuItem('Delete');
        await flushPromises();

        expect(store.remove).not.toHaveBeenCalled();

        document.body
            .querySelector<HTMLElement>('[data-testid="workspace-delete-confirm"]')
            ?.click();
        await flushPromises();

        expect(store.remove).toHaveBeenCalledWith('request-1234');
    });

    it('offers a retry when loading fails', async () => {
        const { wrapper, store } = createWrapper({ error: 'Offline' });

        await wrapper.find('[data-testid="workspace-retry"]').trigger('click');

        expect(store.load).toHaveBeenCalledTimes(2);
    });

    it('shows folders and the requests inside them', () => {
        const { wrapper } = createWrapper({
            listing: {
                folders: ['users', 'empty'],
                requests: [{ ...summary, folder: 'users' }],
            },
        });

        const folders = wrapper.findAll('[data-testid="workspace-folder"]');

        expect(folders.map(folder => folder.attributes('data-path'))).toEqual([
            '',
            'users',
            'empty',
        ]);
        expect(folders[1].find('[data-testid="workspace-item"]').exists()).toBe(true);
    });

    it('moves a request dropped on a folder', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: ['users'],
                requests: [summary],
            },
        });

        const data = new Map<string, string>([
            ['application/x-nimbus-request', 'request-1234'],
        ]);
        const dataTransfer = {
            types: ['application/x-nimbus-request'],
            getData: (type: string) => data.get(type),
        };

        await wrapper
            .find('[data-testid="workspace-folder-toggle"]')
            .trigger('drop', { dataTransfer });

        expect(store.move).toHaveBeenCalledWith('request-1234', {
            folder: 'users',
        });
    });

    it('creates a folder from the popover', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: [],
                requests: [],
            },
        });

        await wrapper.find('[data-testid="workspace-new-folder"]').trigger('click');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="workspace-folder-input"]',
        );

        input!.value = 'users';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>('[data-testid="workspace-folder-form"]')
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(store.createFolder).toHaveBeenCalledWith('users');
    });

    it('renames a request from the popover', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: [],
                requests: [summary],
            },
        });

        await wrapper.find('[data-testid="workspace-item"] > div').trigger('dblclick');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="workspace-rename-input"]',
        );

        input!.value = 'Register user';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>('[data-testid="workspace-rename-form"]')
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(store.move).toHaveBeenCalledWith('request-1234', {
            name: 'Register user',
        });
    });
    it('moves the open tabs only after the confirmation', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: [],
                requests: [],
            },
        });

        const tabsStore = useTabsStore();

        tabsStore.tabs = [{ id: 'tab-1' } as never];

        await wrapper.vm.$nextTick();
        await wrapper.find('[data-testid="workspace-migrate"]').trigger('click');
        await flushPromises();

        expect(store.migrateTabs).not.toHaveBeenCalled();

        document.body
            .querySelector<HTMLElement>('[data-testid="workspace-migrate-confirm"]')
            ?.click();
        await flushPromises();

        expect(store.migrateTabs).toHaveBeenCalled();
    });

    it('renames a folder from the context menu', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: ['users'],
                requests: [],
            },
        });

        await openMenu(wrapper, '[data-testid="workspace-folder-toggle"]');
        clickMenuItem('Rename folder');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="workspace-folder-rename-input"]',
        );

        expect(input?.value).toBe('users');

        input!.value = 'People';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>(
                '[data-testid="workspace-folder-rename-form"]',
            )
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(store.renameFolder).toHaveBeenCalledWith('users', 'People');
    });

    it('reorders a request dropped next to a request of the same folder', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: [],
                requests: [
                    { ...summary, id: 'request-aaaa', name: 'First' },
                    { ...summary, id: 'request-bbbb', name: 'Second' },
                ],
            },
        });

        const dataTransfer = {
            types: ['application/x-nimbus-request'],
            setData: vi.fn(),
            getData: () => 'request-aaaa',
        };

        const [first, second] = wrapper.findAll('[data-testid="workspace-item"]');

        await first.trigger('dragstart', { dataTransfer });
        await second.trigger('dragover', { dataTransfer });

        expect(second.attributes('data-reorder')).toBe('after');

        await second.trigger('drop', { dataTransfer });

        expect(store.reorderRequests).toHaveBeenCalledWith('', [
            'request-bbbb',
            'request-aaaa',
        ]);
        expect(store.move).not.toHaveBeenCalled();
    });

    it('reorders requests inside a folder', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: ['users'],
                requests: [
                    { ...summary, id: 'request-aaaa', name: 'First', folder: 'users' },
                    { ...summary, id: 'request-bbbb', name: 'Second', folder: 'users' },
                ],
            },
        });

        const dataTransfer = {
            types: ['application/x-nimbus-request'],
            setData: vi.fn(),
            getData: () => 'request-aaaa',
        };

        const first = wrapper.find('[data-id="request-aaaa"]');
        const second = wrapper.find('[data-id="request-bbbb"]');

        await first.trigger('dragstart', { dataTransfer });
        await second.trigger('dragover', { dataTransfer });

        expect(second.attributes('data-reorder')).toBe('after');

        await second.trigger('drop', { dataTransfer });

        expect(store.reorderRequests).toHaveBeenCalledWith('users', [
            'request-bbbb',
            'request-aaaa',
        ]);
    });

    it('moves a request dropped next to a request of another folder', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: ['users'],
                requests: [
                    { ...summary, id: 'request-aaaa', name: 'First' },
                    { ...summary, id: 'request-bbbb', name: 'Second', folder: 'users' },
                ],
            },
        });

        const dataTransfer = {
            types: ['application/x-nimbus-request'],
            setData: vi.fn(),
            getData: () => 'request-aaaa',
        };

        const first = wrapper.find('[data-id="request-aaaa"]');
        const second = wrapper.find('[data-id="request-bbbb"]');

        await first.trigger('dragstart', { dataTransfer });
        await second.trigger('dragover', { dataTransfer });

        expect(second.find('[data-testid="workspace-drop-indicator"]').exists()).toBe(
            true,
        );

        await second.trigger('drop', { dataTransfer });

        expect(store.moveBeside).toHaveBeenCalledWith(
            'request-aaaa',
            'request-bbbb',
            'after',
        );
        expect(store.reorderRequests).not.toHaveBeenCalled();
    });

    it('deletes a folder only after the confirmation', async () => {
        const { wrapper, store } = createWrapper({
            listing: {
                folders: ['users'],
                requests: [{ ...summary, folder: 'users' }],
            },
        });

        await openMenu(wrapper, '[data-testid="workspace-folder-toggle"]');
        clickMenuItem('Delete folder');
        await flushPromises();

        expect(store.removeFolder).not.toHaveBeenCalled();
        expect(document.body.textContent).toContain('including 1 request');

        document.body
            .querySelector<HTMLElement>('[data-testid="workspace-delete-folder-confirm"]')
            ?.click();
        await flushPromises();

        expect(store.removeFolder).toHaveBeenCalledWith('users');
    });
});
