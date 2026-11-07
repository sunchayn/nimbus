import { AppSwitchButton } from '@/components/base/switch';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

describe('AppSwitchButton', () => {
    it('renders the label and a switch reflecting the model value', () => {
        // Arrange & Act

        const wrapper = mount(AppSwitchButton, {
            props: { modelValue: true, label: 'Pretty' },
        });

        // Assert

        expect(wrapper.text()).toContain('Pretty');
        expect(wrapper.find('[role="switch"]').attributes('aria-checked')).toBe('true');
    });

    it('emits update:modelValue when the switch is clicked', async () => {
        // Arrange

        const onUpdate = vi.fn();
        const wrapper = mount(AppSwitchButton, {
            props: {
                modelValue: false,
                label: 'Pretty',
                'onUpdate:modelValue': onUpdate,
            },
        });

        // Act

        await wrapper.find('[role="switch"]').trigger('click');

        // Assert

        expect(onUpdate).toHaveBeenCalledWith(true);
    });

    it('passes attributes to the root element', () => {
        // Arrange & Act

        const wrapper = mount(AppSwitchButton, {
            props: { label: 'Pretty' },
            attrs: { 'data-testid': 'pretty-switch' },
        });

        // Assert

        expect(wrapper.attributes('data-testid')).toBe('pretty-switch');
    });
});
