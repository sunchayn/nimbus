import { AppButton } from '@/components/base/button';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppButton', () => {
    describe('Rendering', () => {
        it('renders the slot content inside a button', () => {
            // Arrange & Act

            const wrapper = mount(AppButton, { slots: { default: 'Click me' } });

            // Assert

            expect(wrapper.element.tagName).toBe('BUTTON');
            expect(wrapper.text()).toBe('Click me');
        });

        it('renders the label prop when there is no slot', () => {
            // Arrange & Act

            const wrapper = mount(AppButton, { props: { label: 'Save' } });

            // Assert

            expect(wrapper.text()).toBe('Save');
        });

        it('passes data-testid through to the button', () => {
            // Arrange & Act

            const wrapper = mount(AppButton, { attrs: { 'data-testid': 'my-button' } });

            // Assert

            expect(wrapper.attributes('data-testid')).toBe('my-button');
        });

        it('renders an icon from the icon prop', () => {
            // Arrange & Act

            const wrapper = mount(AppButton, {
                props: { icon: 'i-hugeicons-cancel-01' },
            });

            // Assert

            expect(wrapper.find('svg').exists()).toBe(true);
        });

        it('renders the trailing icon next to the slot content', () => {
            // Arrange & Act

            const wrapper = mount(AppButton, {
                props: { trailingIcon: 'i-hugeicons-arrow-down-01' },
                slots: { default: 'Open' },
            });

            // Assert

            expect(wrapper.text()).toBe('Open');
            expect(wrapper.find('svg').exists()).toBe(true);
        });
    });

    describe('Props mapping', () => {
        it.each([
            ['solid', 'neutral'],
            ['outline', 'neutral'],
            ['subtle', 'neutral'],
            ['ghost', 'neutral'],
            ['link', 'neutral'],
            ['solid', 'error'],
        ] as const)('accepts variant %s with color %s', (variant, color) => {
            // Arrange & Act

            const wrapper = mount(AppButton, {
                props: { variant, color },
                slots: { default: 'Label' },
            });

            // Assert

            expect(wrapper.text()).toBe('Label');
            expect(wrapper.attributes('class')).toBeTruthy();
        });

        it('uses the as prop to change the rendered tag', () => {
            // Arrange & Act

            const wrapper = mount(AppButton, { props: { as: 'a' } });

            // Assert

            expect(wrapper.element.tagName).toBe('A');
        });
    });

    describe('Behavior', () => {
        it('calls the click handler', async () => {
            // Arrange

            let clicks = 0;
            const wrapper = mount(AppButton, {
                attrs: { onClick: () => clicks++ },
                slots: { default: 'Go' },
            });

            // Act

            await wrapper.trigger('click');

            // Assert

            expect(clicks).toBe(1);
        });

        it('does not click when disabled', async () => {
            // Arrange

            let clicks = 0;
            const wrapper = mount(AppButton, {
                props: { disabled: true },
                attrs: { onClick: () => clicks++ },
            });

            // Act

            await wrapper.trigger('click');

            // Assert

            expect(wrapper.attributes('disabled')).toBeDefined();
            expect(clicks).toBe(0);
        });
    });
});
