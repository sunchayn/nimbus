import InsightCard from '@/components/domain/Client/Response/ResponseInsight/Shared/InsightCard.vue';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const render = (props: Record<string, unknown> = {}, body = 'Body') =>
    mount(InsightCard, {
        props,
        slots: {
            header: '<span data-testid="header">Header</span>',
            default: `<p data-testid="body">${body}</p>`,
        },
    });

describe('InsightCard', () => {
    it('shows its header and body', () => {
        const wrapper = render();

        expect(wrapper.find('[data-testid="header"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="body"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="insight-card-trigger"]').exists()).toBe(false);
    });

    it('has no body area when it has no body', () => {
        const wrapper = mount(InsightCard, {
            slots: { header: '<span data-testid="header">Header</span>' },
        });

        expect(wrapper.find('[data-testid="header"]').exists()).toBe(true);
        expect(wrapper.find('.border-b').exists()).toBe(false);
    });

    it('keeps the body closed until a collapsible card is opened', async () => {
        const wrapper = render({ collapsible: true });

        expect(wrapper.find('[data-testid="body"]').exists()).toBe(false);

        await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');
        await flushPromises();

        expect(wrapper.find('[data-testid="body"]').exists()).toBe(true);
    });

    it('hands the open state to the header', async () => {
        const wrapper = mount(InsightCard, {
            props: { collapsible: true },
            slots: {
                header: '<template #header="{ open }"><span data-testid="state">{{ open }}</span></template>',
                default: 'Body',
            },
        });

        expect(wrapper.find('[data-testid="state"]').text()).toBe('false');

        await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');

        expect(wrapper.find('[data-testid="state"]').text()).toBe('true');
    });

    it.each([[false], [true]])(
        'marks a highlighted card (collapsible %s)',
        collapsible => {
            const wrapper = render({ collapsible, highlighted: true });

            expect(wrapper.attributes('data-highlighted')).toBeDefined();
        },
    );

    it('does not mark a card that is not highlighted', () => {
        expect(render().attributes('data-highlighted')).toBeUndefined();
    });
});
