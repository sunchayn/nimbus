import { AppSplitter } from '@/components/base/resizable';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppSplitter', () => {
    describe('Rendering', () => {
        it('renders the content of each panel slot', () => {
            // Arrange & Act

            const wrapper = mount(AppSplitter, {
                props: { items: [{}, {}], orientation: 'horizontal' },
                slots: {
                    'panel-0': '<div data-testid="left">Left</div>',
                    'panel-1': '<div data-testid="right">Right</div>',
                },
                attachTo: document.body,
            });

            // Assert

            expect(wrapper.find('[data-testid="left"]').text()).toBe('Left');
            expect(wrapper.find('[data-testid="right"]').text()).toBe('Right');
            expect(wrapper.findAll('[role="separator"]')).toHaveLength(1);

            wrapper.unmount();
        });
    });
});
