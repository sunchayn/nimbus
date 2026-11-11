import InsightLogs from '@/components/domain/Client/Response/ResponseInsight/Logs/InsightLogs.vue';
import type { InsightLog } from '@/interfaces/http';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

const makeLog = (overrides: Partial<InsightLog> = {}): InsightLog => ({
    level: 'info',
    message: 'plain',
    context: {},
    ...overrides,
});

const render = (logs: InsightLog[], dropped?: number) =>
    mount(InsightLogs, { props: { logs, dropped } });

describe('InsightLogs', () => {
    it('lists each log with its level and message', () => {
        const wrapper = render([
            makeLog({ level: 'info', message: 'first' }),
            makeLog({ level: 'warning', message: 'second' }),
        ]);

        const logs = wrapper.findAll('[data-testid="insight-log"]');

        expect(logs).toHaveLength(2);
        expect(logs[0].text()).toContain('info');
        expect(logs[0].text()).toContain('first');
        expect(logs[1].text()).toContain('warning');
    });

    it('makes only a log with a context collapsible', () => {
        const wrapper = render([
            makeLog(),
            makeLog({ message: 'with context', context: { id: 5 } }),
        ]);

        const logs = wrapper.findAll('[data-testid="insight-log"]');

        expect(logs[0].find('[data-testid="insight-card-trigger"]').exists()).toBe(false);
        expect(logs[1].find('[data-testid="insight-card-trigger"]').exists()).toBe(true);
    });

    it('shows the context as formatted JSON once the card is opened', async () => {
        const wrapper = render([makeLog({ context: { id: 5 } })]);

        expect(wrapper.find('[data-testid="insight-log-context"]').exists()).toBe(false);

        await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');
        await flushPromises();

        expect(wrapper.find('[data-testid="insight-log-context"]').text()).toBe(
            JSON.stringify({ id: 5 }, null, 2),
        );
    });

    it.each([['emergency'], ['alert'], ['critical'], ['error']])(
        'puts an error dot on the %s level',
        level => {
            const wrapper = render([makeLog({ level })]);

            expect(
                wrapper.find('[data-testid="insight-flag-dot"]').attributes('data-tone'),
            ).toBe('error');
        },
    );

    it.each([['debug'], ['info'], ['notice'], ['warning']])(
        'puts no dot on the %s level',
        level => {
            const wrapper = render([makeLog({ level })]);

            expect(wrapper.find('[data-testid="insight-flag-dot"]').exists()).toBe(false);
        },
    );

    it('shows a note when nothing was logged', () => {
        expect(render([]).text()).toContain('Nothing was logged.');
    });

    it('tells how many logs were not shown', () => {
        const wrapper = render([makeLog()], 7);

        expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain('7');
    });
});
