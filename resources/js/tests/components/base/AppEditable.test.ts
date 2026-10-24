import { AppEditable } from '@/components/base/editable';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

describe('AppEditable', () => {
    describe('Rendering', () => {
        it('renders the model value in the preview', () => {
            // Arrange & Act

            const wrapper = mount(AppEditable, { props: { modelValue: 'Staging' } });

            // Assert

            expect(wrapper.text()).toContain('Staging');
        });

        it('renders slot content in place of the model value', () => {
            // Arrange & Act

            const wrapper = mount(AppEditable, {
                props: { modelValue: 'Staging' },
                attachTo: document.body,
                slots: { default: 'Custom preview' },
            });

            // Assert

            expect(wrapper.text()).toContain('Custom preview');
        });

        it('passes data-testid through to the root', () => {
            // Arrange & Act

            const wrapper = mount(AppEditable, {
                attrs: { 'data-testid': 'editor' },
            });

            // Assert

            expect(wrapper.attributes('data-testid')).toBe('editor');
        });
    });

    describe('Behavior', () => {
        it('shows the input after the preview is focused', async () => {
            // Arrange & Act

            const wrapper = mount(AppEditable, {
                props: { modelValue: 'Staging' },
                attachTo: document.body,
            });

            wrapper.vm.focus();
            await nextTick();

            // Assert

            expect(wrapper.find('input').isVisible()).toBe(true);
            expect(wrapper.find('input').element.value).toBe('Staging');
        });

        it('emits update:modelValue when submitted', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = mount(AppEditable, {
                props: { modelValue: '', 'onUpdate:modelValue': onUpdate },
                attachTo: document.body,
            });

            // Act

            wrapper.vm.focus();
            await nextTick();

            await wrapper.find('input').setValue('Production');
            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            // Assert

            expect(onUpdate).toHaveBeenCalledWith('Production');
        });

        it('emits submit when Enter is pressed', async () => {
            // Arrange

            const onSubmit = vi.fn();
            const wrapper = mount(AppEditable, {
                props: { modelValue: 'Staging', onSubmit },
                attachTo: document.body,
            });

            // Act

            wrapper.vm.focus();
            await nextTick();

            await wrapper.find('input').trigger('keydown', { key: 'Enter' });

            // Assert

            expect(onSubmit).toHaveBeenCalledWith('Staging');
        });
    });
});
