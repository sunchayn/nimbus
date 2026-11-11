import InsightSection from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightSection.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

describe('InsightSection', () => {
    it('derives its test id from its name', () => {
        const wrapper = mount(InsightSection, { props: { name: 'Auth Checks' } });

        expect(wrapper.attributes('data-testid')).toBe('insight-auth-checks');
    });

    it('shows its content', () => {
        const wrapper = mount(InsightSection, {
            props: { name: 'Logs' },
            slots: { default: '<span data-testid="content">Entry</span>' },
        });

        expect(wrapper.find('[data-testid="content"]').exists()).toBe(true);
    });

    it('shows the empty note in place of the content', () => {
        const wrapper = mount(InsightSection, {
            props: { name: 'Logs', empty: 'Nothing was logged.' },
            slots: { default: '<span data-testid="content">Entry</span>' },
        });

        expect(wrapper.text()).toContain('Nothing was logged.');
        expect(wrapper.find('[data-testid="content"]').exists()).toBe(false);
    });

    it('tells how many items were not shown', () => {
        const wrapper = mount(InsightSection, { props: { name: 'Logs', dropped: 12 } });

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toBe(
            '12 more not shown.',
        );
    });

    it('shows no dropped note when nothing was dropped', () => {
        const wrapper = mount(InsightSection, { props: { name: 'Logs', dropped: 0 } });

        expect(wrapper.find('[data-testid="insight-dropped"]').exists()).toBe(false);
    });
});
