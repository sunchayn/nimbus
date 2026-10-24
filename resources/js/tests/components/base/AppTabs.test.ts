import { AppTabs } from '@/components/base/tabs';
import { mount } from '@vue/test-utils';
import { beforeAll, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

const items = [
    { label: 'Tab 1', value: 'tab1', slot: 'one' },
    { label: 'Tab 2', value: 'tab2', slot: 'two' },
];

const createWrapper = (props = {}) =>
    mount(AppTabs, {
        props: { items, modelValue: 'tab1', ...props },
        slots: {
            one: '<div data-testid="c1">Content 1</div>',
            two: '<div data-testid="c2">Content 2</div>',
        },
        attachTo: document.body,
    });

describe('AppTabs', () => {
    beforeAll(() => {
        // jsdom has no scrolling, and the active tab is scrolled into view on change.
        Element.prototype.scrollTo = vi.fn();
    });

    describe('Rendering', () => {
        it('renders one tab per item with its label', () => {
            // Arrange & Act

            const wrapper = createWrapper();
            const tabs = wrapper.findAll('[role="tab"]');

            // Assert

            expect(tabs.map(tab => tab.text())).toEqual(['Tab 1', 'Tab 2']);
            wrapper.unmount();
        });

        it('marks the model value tab as selected', () => {
            // Arrange & Act

            const wrapper = createWrapper({ modelValue: 'tab2' });
            const tabs = wrapper.findAll('[role="tab"]');

            // Assert

            expect(tabs[1].attributes('aria-selected')).toBe('true');
            expect(tabs[0].attributes('aria-selected')).toBe('false');
            wrapper.unmount();
        });

        it('shows the active panel content', () => {
            // Arrange & Act

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.get('[data-testid="c1"]').isVisible()).toBe(true);
            wrapper.unmount();
        });
    });

    describe('Mounting behavior', () => {
        it('keeps inactive panels mounted by default', () => {
            // Arrange & Act

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.find('[data-testid="c2"]').exists()).toBe(true);
            wrapper.unmount();
        });

        it('unmounts inactive panels when unmountOnHide is true', () => {
            // Arrange & Act

            const wrapper = createWrapper({ unmountOnHide: true });

            // Assert

            expect(wrapper.find('[data-testid="c2"]').exists()).toBe(false);
            wrapper.unmount();
        });
    });

    describe('Scroll masks', () => {
        it('shows no edge fade when the list does not overflow', async () => {
            // Arrange & Act

            const wrapper = createWrapper();
            await new Promise(resolve => setTimeout(resolve, 50));

            // Assert

            expect(wrapper.attributes('data-mask-start')).toBeUndefined();
            expect(wrapper.attributes('data-mask-end')).toBeUndefined();
            wrapper.unmount();
        });
    });

    describe('Interaction', () => {
        it('emits update:modelValue when another tab is activated', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = createWrapper({ 'onUpdate:modelValue': onUpdate });

            // Act

            await wrapper.findAll('[role="tab"]')[1].trigger('mousedown', { button: 0 });
            await nextTick();

            // Assert

            expect(onUpdate).toHaveBeenCalledWith('tab2');
            wrapper.unmount();
        });
    });
});
