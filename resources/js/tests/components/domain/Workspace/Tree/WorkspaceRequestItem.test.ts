import WorkspaceRequestItem from '@/components/domain/Workspace/Tree/WorkspaceRequestItem.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const request = {
    id: 'request-1234',
    name: 'Create user',
    method: 'POST',
    endpoint: '/api/users',
    route: 'users.store',
    file: 'requests/create-user.request.json',
    folder: '',
    hash: 'hash-1',
};

const createWrapper = (props: Record<string, unknown> = {}) =>
    mount(WorkspaceRequestItem, {
        props: { request, ...props },
        attachTo: document.body,
    });

const clickMenuItem = (label: string) =>
    [...document.body.querySelectorAll<HTMLElement>('[role="menuitem"]')]
        .find(item => item.textContent?.trim() === label)
        ?.click();

describe('WorkspaceRequestItem', () => {
    it('shows the name and the method of the request', () => {
        const wrapper = createWrapper();

        expect(wrapper.text()).toContain('Create user');
        expect(wrapper.text()).toContain('POST');
    });

    it('opens the request when the row is clicked', async () => {
        const wrapper = createWrapper();

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('open')).toEqual([['request-1234']]);
    });

    it('shows the unsaved dot and the save button only for a dirty request', () => {
        const clean = createWrapper();
        const dirty = createWrapper({ isDirty: true });

        expect(clean.find('[data-testid="workspace-item-dirty"]').exists()).toBe(false);
        expect(clean.find('[data-testid="workspace-save"]').exists()).toBe(false);
        expect(dirty.find('[data-testid="workspace-item-dirty"]').exists()).toBe(true);
        expect(dirty.find('[data-testid="workspace-save"]').exists()).toBe(true);
    });

    it('emits save when the save button is clicked', async () => {
        const wrapper = createWrapper({ isDirty: true });

        await wrapper.find('[data-testid="workspace-save"]').trigger('click');

        expect(wrapper.emitted('save')).toEqual([[request]]);
    });

    it('emits delete from the context menu', async () => {
        const wrapper = createWrapper();

        await wrapper.find('[data-testid="workspace-item"] > div').trigger('contextmenu');
        await flushPromises();
        clickMenuItem('Delete');
        await flushPromises();

        expect(wrapper.emitted('delete')).toEqual([[request]]);
    });

    it('renames the request from the popover on double click', async () => {
        const wrapper = createWrapper();

        await wrapper.find('[data-testid="workspace-item"] > div').trigger('dblclick');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="workspace-rename-input"]',
        );

        expect(input?.value).toBe('Create user');

        input!.value = 'Register user';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>('[data-testid="workspace-rename-form"]')
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(wrapper.emitted('rename')).toEqual([[request, 'Register user']]);
    });

    it('does not rename to an empty name', async () => {
        const wrapper = createWrapper();

        await wrapper.find('[data-testid="workspace-item"] > div').trigger('dblclick');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="workspace-rename-input"]',
        );

        input!.value = '   ';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>('[data-testid="workspace-rename-form"]')
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(wrapper.emitted('rename')).toBeUndefined();
    });

    it('tells which request is dragged and where a dragged request would land', async () => {
        const wrapper = createWrapper({ reorderEdge: 'before' });
        const item = wrapper.find('[data-testid="workspace-item"]');

        await item.trigger('dragstart');

        expect(wrapper.emitted('dragStart')).toEqual([[request]]);
        expect(item.attributes('data-id')).toBe('request-1234');
        expect(item.attributes('data-reorder')).toBe('before');
    });

    it('dims the row while it is dragged', async () => {
        const wrapper = createWrapper();
        const item = wrapper.find('[data-testid="workspace-item"]');

        await item.trigger('dragstart');
        expect(item.classes()).toContain('opacity-40');

        await item.trigger('dragend');
        expect(item.classes()).not.toContain('opacity-40');
    });
});
