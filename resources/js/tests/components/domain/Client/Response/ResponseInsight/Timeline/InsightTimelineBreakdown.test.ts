import InsightTimelineBreakdown from '@/components/domain/Client/Response/ResponseInsight/Timeline/InsightTimelineBreakdown.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { makeInsight, makeQuery, resetOffsets, timelineOf } from '../helpers';

const render = (...queries: ReturnType<typeof makeQuery>[]) =>
    mount(InsightTimelineBreakdown, {
        props: { timeline: timelineOf(makeInsight({ duration_ms: 100, queries })) },
    });

describe('InsightTimelineBreakdown', () => {
    beforeEach(() => {
        resetOffsets();
    });

    it('splits the request time between queries and everything else', () => {
        const text = render(makeQuery('select 1', 10), makeQuery('select 2', 15)).text();

        expect(text).toContain('Queries 25 ms (25%)');
        expect(text).toContain('Everything else 75 ms');
    });

    it('sizes the query part of the bar by its share of the request', () => {
        const wrapper = render(makeQuery('select 1', 10), makeQuery('select 2', 15));

        expect(
            wrapper.find('[data-testid="insight-breakdown-queries"]').attributes('style'),
        ).toContain('width: 25%');
        expect(wrapper.find('[data-testid="insight-breakdown-other"]').exists()).toBe(
            true,
        );
    });

    it('shows no time for queries when none ran', () => {
        const wrapper = render();

        expect(wrapper.text()).toContain('Queries 0 ms (0%)');
        expect(wrapper.text()).toContain('Everything else 100 ms');
        expect(
            wrapper.find('[data-testid="insight-breakdown-queries"]').attributes('style'),
        ).toContain('width: 0%');
    });
});
