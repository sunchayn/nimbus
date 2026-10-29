import { AppRadioCardGroup } from '@/components/base/radio-card';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const items = [
    { value: 'a', label: 'A', description: 'First' },
    { value: 'b', label: 'B', description: 'Second' },
];

describe('AppRadioCardGroup', () => {
    it('marks the model value as checked', () => {
        // Arrange & Act

        const wrapper = mount(AppRadioCardGroup, {
            props: { items, label: 'Pick', modelValue: 'b' },
        });

        // Assert

        expect(wrapper.find('[role="radiogroup"]').attributes('aria-label')).toBe('Pick');
        expect(
            wrapper.find('[data-testid="radio-card-b"]').attributes('aria-checked'),
        ).toBe('true');
        expect(
            wrapper.find('[data-testid="radio-card-a"]').attributes('aria-checked'),
        ).toBe('false');
    });

    it('emits update:modelValue on click', async () => {
        // Arrange

        const wrapper = mount(AppRadioCardGroup, {
            props: { items, label: 'Pick', modelValue: 'a' },
        });

        // Act

        await wrapper.find('[data-testid="radio-card-b"]').trigger('click');

        // Assert

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['b']);
    });

    it('selects on Enter', async () => {
        // Arrange

        const wrapper = mount(AppRadioCardGroup, {
            props: { items, label: 'Pick', modelValue: 'a' },
        });

        // Act

        await wrapper.find('[data-testid="radio-card-b"]').trigger('keydown.enter');

        // Assert

        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['b']);
    });
});
