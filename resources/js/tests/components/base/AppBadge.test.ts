import { AppBadge } from '@/components/base/badge';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppBadge', () => {
    describe('Rendering', () => {
        it('renders slot content', () => {
            // Arrange & Act

            const wrapper = mount(AppBadge, { slots: { default: 'GET' } });

            // Assert

            expect(wrapper.text()).toBe('GET');
        });

        it('renders the label prop when there is no slot', () => {
            // Arrange & Act

            const wrapper = mount(AppBadge, { props: { label: 'New' } });

            // Assert

            expect(wrapper.text()).toBe('New');
        });

        it('passes data-testid through', () => {
            // Arrange & Act

            const wrapper = mount(AppBadge, { attrs: { 'data-testid': 'badge' } });

            // Assert

            expect(wrapper.attributes('data-testid')).toBe('badge');
        });

        it.each(['solid', 'outline', 'soft', 'subtle'] as const)(
            'accepts the %s variant',
            variant => {
                // Arrange & Act

                const wrapper = mount(AppBadge, {
                    props: { variant, color: 'neutral' },
                    slots: { default: 'Tag' },
                });

                // Assert

                expect(wrapper.text()).toBe('Tag');
            },
        );
    });
});
