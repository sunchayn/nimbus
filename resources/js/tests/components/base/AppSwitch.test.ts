import { AppSwitch } from '@/components/base/switch';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

describe('AppSwitch', () => {
    describe('Rendering', () => {
        it('renders a switch role reflecting the model value', () => {
            // Arrange & Act

            const wrapper = mount(AppSwitch, { props: { modelValue: true } });

            // Assert

            const control = wrapper.find('[role="switch"]');
            expect(control.exists()).toBe(true);
            expect(control.attributes('aria-checked')).toBe('true');
        });

        it('renders the label prop', () => {
            // Arrange & Act

            const wrapper = mount(AppSwitch, { props: { label: 'Enable' } });

            // Assert

            expect(wrapper.text()).toContain('Enable');
        });
    });

    describe('Behavior', () => {
        it('emits update:modelValue when clicked', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = mount(AppSwitch, {
                props: { modelValue: false, 'onUpdate:modelValue': onUpdate },
            });

            // Act

            await wrapper.find('[role="switch"]').trigger('click');

            // Assert

            expect(onUpdate).toHaveBeenCalledWith(true);
        });

        it('does not emit when disabled', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = mount(AppSwitch, {
                props: {
                    modelValue: false,
                    disabled: true,
                    'onUpdate:modelValue': onUpdate,
                },
            });

            // Act

            await wrapper.find('[role="switch"]').trigger('click');

            // Assert

            expect(onUpdate).not.toHaveBeenCalled();
        });
    });
});
