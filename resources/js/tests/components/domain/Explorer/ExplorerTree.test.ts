import ExplorerTree from '@/components/domain/Explorer/ExplorerTree.vue';
import type { ExplorerNode } from '@/interfaces/explorer';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

interface Item {
    id: string;
    name: string;
}

const tree: ExplorerNode<Item> = {
    name: '',
    path: '',
    folders: [
        {
            name: 'users',
            path: 'users',
            folders: [],
            items: [{ id: 'b', name: 'Beta' }],
        },
        { name: 'empty', path: 'empty', folders: [], items: [] },
    ],
    items: [{ id: 'a', name: 'Alpha' }],
};

const createWrapper = (props: Record<string, unknown> = {}) =>
    mount(ExplorerTree<Item>, {
        props: {
            node: tree,
            itemKey: (item: Item) => item.id,
            itemLabel: (item: Item) => item.name,
            isOpen: () => true,
            testId: 'tree',
            ...props,
        },
        slots: {
            item: `<template #item="{ item, folder }">
                <button data-testid="row">{{ item.name }} in "{{ folder.path }}"</button>
            </template>`,
            'item-overlay': `<template #item-overlay="{ item }">
                <span data-testid="overlay">{{ item.id }}</span>
            </template>`,
        },
        attachTo: document.body,
    });

const clickMenuItem = (label: string) =>
    [...document.body.querySelectorAll<HTMLElement>('[role="menuitem"]')]
        .find(item => item.textContent?.trim() === label)
        ?.click();

describe('ExplorerTree', () => {
    it('shows every folder and the items inside them through the slots', () => {
        const wrapper = createWrapper();

        expect(
            wrapper
                .findAll('[data-testid="tree-folder"]')
                .map(folder => folder.attributes('data-path')),
        ).toEqual(['', 'users', 'empty']);
        expect(wrapper.findAll('[data-testid="row"]').map(row => row.text())).toEqual([
            'Beta in "users"',
            'Alpha in ""',
        ]);
        expect(wrapper.findAll('[data-testid="overlay"]')).toHaveLength(2);
        expect(wrapper.find('[data-testid="tree-folder-empty"]').exists()).toBe(true);
    });

    it('hides the items of a closed folder', () => {
        const wrapper = createWrapper({ isOpen: (path: string) => path !== 'users' });

        expect(wrapper.findAll('[data-testid="row"]').map(row => row.text())).toEqual([
            'Alpha in ""',
        ]);
    });

    it('asks to toggle a folder when its row is clicked', async () => {
        const wrapper = createWrapper();

        await wrapper.find('[data-testid="tree-folder-toggle"]').trigger('click');

        expect(wrapper.emitted('toggle')).toEqual([['users']]);
    });

    it('does not drag, rename or open a menu unless they are turned on', async () => {
        const wrapper = createWrapper();
        const item = wrapper.find('[data-testid="tree-item"]');

        expect(item.attributes('draggable')).toBe('false');

        await item.find('div').trigger('dblclick');
        await item.find('div').trigger('contextmenu');
        await flushPromises();

        expect(
            document.body.querySelector('[data-testid="tree-rename-input"]'),
        ).toBeNull();
        expect(document.body.querySelector('[role="menuitem"]')).toBeNull();
    });

    it('renames an item from the popover on double click', async () => {
        const wrapper = createWrapper({ renamable: true });

        await wrapper.find('[data-testid="tree-item"] > div').trigger('dblclick');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="tree-rename-input"]',
        );

        expect(input?.value).toBe('Beta');

        input!.value = 'Gamma';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>('[data-testid="tree-rename-form"]')
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(wrapper.emitted('renameItem')).toEqual([
            [tree.folders[0].items[0], 'Gamma'],
        ]);
    });

    it('does not rename to an empty name', async () => {
        const wrapper = createWrapper({ renamable: true });

        await wrapper.find('[data-testid="tree-item"] > div').trigger('dblclick');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="tree-rename-input"]',
        );

        input!.value = '   ';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>('[data-testid="tree-rename-form"]')
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(wrapper.emitted('renameItem')).toBeUndefined();
    });

    it('adds the rename entry to the menu of an item', async () => {
        const onSelect = vi.fn();
        const wrapper = createWrapper({
            renamable: true,
            itemMenu: () => [[{ label: 'Delete', onSelect }]],
        });

        await wrapper.find('[data-testid="tree-item"] > div').trigger('contextmenu');
        await flushPromises();

        const labels = [...document.body.querySelectorAll('[role="menuitem"]')].map(
            item => item.textContent?.trim(),
        );

        expect(labels).toEqual(['Rename', 'Delete']);

        clickMenuItem('Delete');
        await flushPromises();

        expect(onSelect).toHaveBeenCalled();
    });

    it('renames a folder from its menu', async () => {
        const wrapper = createWrapper({ renamable: true });

        await wrapper.find('[data-testid="tree-folder-toggle"]').trigger('contextmenu');
        await flushPromises();
        clickMenuItem('Rename folder');
        await flushPromises();

        const input = document.body.querySelector<HTMLInputElement>(
            '[data-testid="tree-folder-rename-input"]',
        );

        expect(input?.value).toBe('users');

        input!.value = 'People';
        input!.dispatchEvent(new Event('input'));
        await flushPromises();

        document.body
            .querySelector<HTMLFormElement>('[data-testid="tree-folder-rename-form"]')
            ?.dispatchEvent(new Event('submit', { cancelable: true }));
        await flushPromises();

        expect(wrapper.emitted('renameFolder')).toEqual([[tree.folders[0], 'People']]);
    });

    it('marks the dragged item and where it would land', async () => {
        const wrapper = createWrapper({ draggable: true });
        const dataTransfer = {
            types: ['application/x-nimbus-request'],
            setData: vi.fn(),
            getData: () => 'a',
        };
        const [beta, alpha] = wrapper.findAll('[data-testid="tree-item"]');

        await alpha.trigger('dragstart', { dataTransfer });

        expect(alpha.classes()).toContain('opacity-40');
        expect(alpha.attributes('data-id')).toBe('a');

        await beta.trigger('dragover', { dataTransfer });

        expect(beta.attributes('data-reorder')).toBe('after');

        await beta.trigger('drop', { dataTransfer });

        expect(wrapper.emitted('reorderItem')).toEqual([['a', 'b', 'after']]);
    });

    it('moves an item dropped on a folder', async () => {
        const wrapper = createWrapper({ draggable: true });
        const dataTransfer = {
            types: ['application/x-nimbus-request'],
            getData: () => 'a',
        };

        await wrapper
            .find('[data-testid="tree-folder-toggle"]')
            .trigger('drop', { dataTransfer });

        expect(wrapper.emitted('moveItem')).toEqual([['a', 'users']]);
    });
});
