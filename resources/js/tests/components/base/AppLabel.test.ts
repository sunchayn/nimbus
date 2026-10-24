import { AppLabel } from '@/components/base/label';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppLabel', () => {
    describe('Rendering', () => {
        it('renders slot content in a label element', () => {
            // Arrange & Act

            const wrapper = mount(AppLabel, { slots: { default: 'Name' } });

            // Assert

            expect(wrapper.element.tagName).toBe('LABEL');
            expect(wrapper.text()).toBe('Name');
        });

        it('links to a control through the for prop', () => {
            // Arrange & Act

            const wrapper = mount(AppLabel, { props: { for: 'user-id' } });

            // Assert

            expect(wrapper.attributes('for')).toBe('user-id');
        });
    });
});
