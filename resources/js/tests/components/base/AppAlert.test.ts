import { AppAlert } from '@/components/base/alert';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppAlert', () => {
    describe('Rendering', () => {
        it('renders the title and description props', () => {
            // Arrange & Act

            const wrapper = mount(AppAlert, {
                props: { title: 'Heads up', description: 'Authorization was dropped.' },
            });

            // Assert

            expect(wrapper.text()).toContain('Heads up');
            expect(wrapper.text()).toContain('Authorization was dropped.');
        });

        it('renders the description slot', () => {
            // Arrange & Act

            const wrapper = mount(AppAlert, {
                slots: { description: '<p>Slot body</p>' },
            });

            // Assert

            expect(wrapper.text()).toContain('Slot body');
        });

        it('passes data-testid through', () => {
            // Arrange & Act

            const wrapper = mount(AppAlert, {
                props: { title: 'Hint' },
                attrs: { 'data-testid': 'alert' },
            });

            // Assert

            expect(wrapper.attributes('data-testid')).toBe('alert');
        });
    });

    describe('Interactions', () => {
        it('emits update:open with false when the close button is clicked', async () => {
            // Arrange

            const wrapper = mount(AppAlert, { props: { title: 'Hint', close: true } });

            // Act

            await wrapper.find('button').trigger('click');

            // Assert

            expect(wrapper.emitted('update:open')?.[0]).toEqual([false]);
        });
    });
});
