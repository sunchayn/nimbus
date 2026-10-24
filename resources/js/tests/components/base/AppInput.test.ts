import AppInput from '@/components/base/input/AppInput.vue';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(AppInput, {
        ...options,
        global: {
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('AppInput', () => {
    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('syncs model value updates', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = createWrapper({
                props: { modelValue: 'initial', 'onUpdate:modelValue': onUpdate },
            });

            // Act

            await wrapper.find('input').setValue('updated');

            // Assert

            expect(onUpdate).toHaveBeenCalledWith('updated');
        });

        it('passes data-testid, placeholder and type to the input element', () => {
            // Arrange & Act

            const wrapper = createWrapper({
                props: { placeholder: 'Key', type: 'password' },
                attrs: { 'data-testid': 'my-input' },
            });
            const input = wrapper.find('input');

            // Assert

            expect(input.attributes('data-testid')).toBe('my-input');
            expect(input.attributes('placeholder')).toBe('Key');
            expect(input.attributes('type')).toBe('password');
        });

        it('disables the input element', () => {
            // Arrange & Act

            const wrapper = createWrapper({ props: { disabled: true } });

            // Assert

            expect(wrapper.find('input').attributes('disabled')).toBeDefined();
        });
    });
});
