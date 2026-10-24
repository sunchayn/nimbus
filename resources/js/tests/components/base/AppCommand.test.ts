import { AppCommand } from '@/components/base/command';
import { mount } from '@vue/test-utils';
import { beforeAll, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const createGroups = (onSelect = vi.fn()) => [
    {
        id: 'text',
        label: 'Text',
        items: [{ label: 'Email', onSelect }, { label: 'Name' }],
    },
];

const createWrapper = (props = {}, slots = {}) =>
    mount(AppCommand, {
        props: { groups: createGroups(), placeholder: 'Search...', ...props },
        slots,
        attachTo: document.body,
    });

describe('AppCommand', () => {
    beforeAll(() => {
        // jsdom does not implement scrollIntoView, which the listbox calls on highlight.
        Element.prototype.scrollIntoView = vi.fn();
    });

    describe('Rendering', () => {
        it('renders the search input with its placeholder', () => {
            // Arrange & Act

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.get('input').attributes('placeholder')).toBe('Search...');
            wrapper.unmount();
        });

        it('renders group labels and items', () => {
            // Arrange & Act

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.text()).toContain('Text');
            expect(wrapper.findAll('[role="option"]').map(o => o.text())).toEqual([
                'Email',
                'Name',
            ]);
            wrapper.unmount();
        });

        it('renders the empty slot when there are no groups', () => {
            // Arrange & Act

            const wrapper = createWrapper({ groups: [] }, { empty: 'No results' });

            // Assert

            expect(wrapper.text()).toContain('No results');
            wrapper.unmount();
        });

        it('renders the footer slot', () => {
            // Arrange & Act

            const wrapper = createWrapper(
                {},
                { footer: '<p data-testid="foot">hint</p>' },
            );

            // Assert

            expect(wrapper.find('[data-testid="foot"]').exists()).toBe(true);
            wrapper.unmount();
        });

        it('renders the header slot alongside the footer slot', () => {
            // Arrange & Act

            const wrapper = createWrapper(
                {},
                {
                    header: '<p data-testid="head">tabs</p>',
                    footer: '<p data-testid="foot">hint</p>',
                },
            );

            // Assert

            expect(wrapper.find('[data-testid="head"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="foot"]').exists()).toBe(true);
            wrapper.unmount();
        });
    });

    describe('Behavior', () => {
        it('updates the search term model when typing', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = createWrapper({ 'onUpdate:searchTerm': onUpdate });

            // Act

            await wrapper.get('input').setValue('em');

            // Assert

            expect(onUpdate).toHaveBeenLastCalledWith('em');
            wrapper.unmount();
        });

        it('calls the item onSelect handler when an item is clicked', async () => {
            // Arrange

            const onSelect = vi.fn();
            const wrapper = createWrapper({ groups: createGroups(onSelect) });

            // Act

            await wrapper.findAll('[role="option"]')[0].trigger('click');

            // Assert

            expect(onSelect).toHaveBeenCalledTimes(1);
            wrapper.unmount();
        });
    });

    describe('Exposed API', () => {
        it('focuses the search input through focusInput', () => {
            // Arrange

            const wrapper = createWrapper();

            // Act

            wrapper.vm.focusInput();

            // Assert

            expect(document.activeElement).toBe(wrapper.get('input').element);
            wrapper.unmount();
        });
    });
});
