import { AppSelect } from '@/components/base/select';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

const items = [
    { label: 'Option 1', value: 'opt1' },
    { label: 'Option 2', value: 'opt2' },
];

const createWrapper = (props = {}) =>
    mount(AppSelect, {
        props: { items, placeholder: 'Select', ...props },
        attrs: { 'data-testid': 'select-trigger' },
        attachTo: document.body,
    });

describe('AppSelect', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('Rendering', () => {
        it('renders the placeholder when there is no value', () => {
            // Arrange & Act

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.text()).toContain('Select');
        });

        it('renders the label of the selected item', () => {
            // Arrange & Act

            const wrapper = createWrapper({ modelValue: 'opt2' });

            // Assert

            expect(wrapper.text()).toContain('Option 2');
        });

        it('exposes a combobox role and passes the test id to the trigger', () => {
            // Arrange & Act

            const wrapper = createWrapper();
            const trigger = wrapper.get('[data-testid="select-trigger"]');

            // Assert

            expect(trigger.attributes('role')).toBe('combobox');
        });

        it('renders custom label content through the item-label slot', async () => {
            // Arrange

            const wrapper = mount(AppSelect, {
                props: { items, open: true },
                slots: { 'item-label': '<span data-testid="custom">custom</span>' },
                attachTo: document.body,
            });

            // Act

            await nextTick();

            // Assert

            expect(document.body.querySelector('[data-testid="custom"]')).not.toBeNull();
            wrapper.unmount();
        });
    });

    describe('Overlay', () => {
        it('lists group labels and options in the body when open', async () => {
            // Arrange

            const wrapper = mount(AppSelect, {
                props: {
                    open: true,
                    items: [[{ type: 'label', label: 'Group A' }, ...items]],
                },
                attachTo: document.body,
            });

            // Act

            await nextTick();

            // Assert

            expect(document.body.textContent).toContain('Group A');
            expect(document.body.querySelectorAll('[role="option"]')).toHaveLength(2);
            wrapper.unmount();
        });

        it('emits update:modelValue when an option is chosen', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = mount(AppSelect, {
                props: { items, open: true, 'onUpdate:modelValue': onUpdate },
                attachTo: document.body,
            });
            await nextTick();

            // Act

            const option = document.body.querySelectorAll('[role="option"]')[1];
            option.dispatchEvent(
                new PointerEvent('pointerup', { pointerType: 'mouse', bubbles: true }),
            );
            await flushPromises();

            // Assert

            expect(onUpdate).toHaveBeenCalledWith('opt2');
            wrapper.unmount();
        });
    });
});
