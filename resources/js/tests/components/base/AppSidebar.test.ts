import { AppSidebar, AppSidebarMenu } from '@/components/base/sidebar';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

describe('AppSidebar', () => {
    describe('Rendering', () => {
        it('renders the header, body and footer slots', () => {
            // Arrange & Act

            const wrapper = mount(AppSidebar, {
                props: { collapsible: 'icon', open: false },
                slots: {
                    header: '<span>Head</span>',
                    default: '<span>Body</span>',
                    footer: '<span>Foot</span>',
                },
            });

            // Assert

            expect(wrapper.text()).toContain('Head');
            expect(wrapper.text()).toContain('Body');
            expect(wrapper.text()).toContain('Foot');
            expect(wrapper.find('[data-collapsible="icon"]').exists()).toBe(true);
        });
    });
});

describe('AppSidebarMenu', () => {
    describe('Interaction', () => {
        it('renders items and runs onSelect on click', async () => {
            // Arrange

            const onSelect = vi.fn();
            const wrapper = mount(AppSidebarMenu, {
                props: {
                    items: [
                        { label: 'Settings', icon: 'i-hugeicons-settings-01', onSelect },
                    ],
                },
            });

            // Act

            await wrapper.find('button, a').trigger('click');

            // Assert

            expect(wrapper.text()).toContain('Settings');
            expect(onSelect).toHaveBeenCalled();
        });
    });
});
