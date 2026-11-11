import InsightTimeline from '@/components/domain/Client/Response/ResponseInsight/Timeline/InsightTimeline.vue';
import type { Insight } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { useElementSize } from '@vueuse/core';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';
import { makeHttp, makeInsight, makeQuery, mixedQueries, resetOffsets } from '../helpers';

// The width of the axis is measured in the browser, so a test sets it by hand.
vi.mock('@vueuse/core', async importOriginal => {
    const { ref } = await import('vue');

    const width = ref(0);

    return {
        ...(await importOriginal<Record<string, unknown>>()),
        useElementSize: () => ({ width, height: ref(0) }),
    };
});

const setAxisWidth = (px: number) => {
    useElementSize(ref(null)).width.value = px;
};

const render = (insight: Insight) => mount(InsightTimeline, { props: { insight } });

// The width reaches the axis one tick after it is mounted.
const renderMeasured = async (insight: Insight) => {
    const wrapper = render(insight);

    await nextTick();
    await nextTick();

    return wrapper;
};

const rowsOf = (wrapper: ReturnType<typeof render>) =>
    wrapper.findAll('[data-testid="insight-waterfall-row"]');

const tickLabels = (wrapper: ReturnType<typeof render>) =>
    wrapper.findAll('[data-testid="insight-axis-tick"]').map(tick => tick.text());

describe('InsightTimeline', () => {
    beforeEach(() => {
        resetOffsets();
        setAxisWidth(0);
    });

    describe('header', () => {
        it('shows the duration of the request and its peak memory', () => {
            const wrapper = render(
                makeInsight({
                    duration_ms: 259.68,
                    peak_memory: 8 * 1048576,
                    queries: [makeQuery('select 1')],
                }),
            );

            expect(wrapper.find('[data-testid="insight-stat-duration"]').text()).toBe(
                '259.68 ms',
            );
            expect(wrapper.find('[data-testid="insight-stat-memory"]').text()).toBe(
                '8.0 MB peak memory',
            );
        });

        it('leaves out the memory when it is not known', () => {
            const wrapper = render(makeInsight({ peak_memory: 0 }));

            expect(wrapper.find('[data-testid="insight-stat-memory"]').exists()).toBe(
                false,
            );
        });

        it('shows the split between queries and the rest', () => {
            const wrapper = render(
                makeInsight({
                    duration_ms: 100,
                    queries: [makeQuery('select 1', 10), makeQuery('select 2', 15)],
                }),
            );

            expect(wrapper.find('[data-testid="insight-breakdown"]').text()).toContain(
                '25 ms',
            );
        });

        it('shows no card for a request without a duration', () => {
            const wrapper = render(makeInsight({ duration_ms: 0 }));

            expect(wrapper.find('[data-testid="insight-stat-duration"]').exists()).toBe(
                false,
            );
            expect(rowsOf(wrapper)).toHaveLength(0);
        });
    });

    describe('rows', () => {
        it('draws a row with a bar for each query', () => {
            const wrapper = render(makeInsight({ queries: [makeQuery('select 1', 5)] }));

            expect(rowsOf(wrapper)).toHaveLength(1);
            expect(wrapper.findAll('[data-testid="insight-waterfall-bar"]')).toHaveLength(
                1,
            );
        });

        it('nests the other queries of an N+1 group under the first one', () => {
            const wrapper = render(makeInsight({ queries: mixedQueries() }));

            const depths = wrapper
                .findAll('[data-testid="insight-waterfall-cell"]')
                .map(cell => cell.attributes('data-depth'));

            expect(depths).toEqual(['0', '1', '1', '1', '1', '0', '0']);
        });

        it('puts a database icon on the main rows only', () => {
            const wrapper = render(makeInsight({ queries: mixedQueries() }));

            const icons = rowsOf(wrapper).map(row =>
                row.find('[data-testid="insight-waterfall-db-icon"]').exists(),
            );

            expect(icons).toEqual([true, false, false, false, false, true, true]);
        });

        it('adds an HTTP call that has a start time', () => {
            const wrapper = render(
                makeInsight({
                    http: [makeHttp({ method: 'POST', url: 'https://a.test/x' })],
                }),
            );

            expect(rowsOf(wrapper)).toHaveLength(1);
            expect(rowsOf(wrapper)[0].attributes('aria-label')).toContain(
                'HTTP call, POST https://a.test/x',
            );
        });

        it('leaves out an HTTP call that has no start time', () => {
            const wrapper = render(
                makeInsight({
                    http: [
                        makeHttp({ url: 'https://a.test/placed' }),
                        makeHttp({
                            url: 'https://a.test/failed',
                            status: null,
                            duration_ms: null,
                            offset_ms: null,
                        }),
                    ],
                }),
            );

            expect(rowsOf(wrapper)).toHaveLength(1);
            expect(wrapper.text()).toContain('https://a.test/placed');
            expect(wrapper.text()).not.toContain('https://a.test/failed');
        });

        it('orders the rows by start time', () => {
            const wrapper = render(
                makeInsight({
                    queries: [{ ...makeQuery('select 1'), offset_ms: 30 }],
                    http: [makeHttp({ offset_ms: 5 })],
                }),
            );

            expect(
                rowsOf(wrapper).map(row => row.attributes('aria-label')?.split(',')[0]),
            ).toEqual(['HTTP call', 'Query']);
        });

        it('draws no rows and no axis when nothing ran', () => {
            const wrapper = render(makeInsight({ duration_ms: 40 }));

            expect(rowsOf(wrapper)).toHaveLength(0);
            expect(wrapper.find('[data-testid="insight-timeline-axis"]').exists()).toBe(
                false,
            );
            expect(wrapper.find('[data-testid="insight-stat-duration"]').text()).toBe(
                '40 ms',
            );
        });

        it('tells which row was clicked', async () => {
            const wrapper = render(makeInsight({ queries: mixedQueries() }));

            await rowsOf(wrapper)[5].trigger('click');

            expect(wrapper.emitted('select')?.[0][0]).toMatchObject({
                kind: 'query',
                index: 5,
            });
        });
    });

    describe('axis', () => {
        const insight = (duration_ms: number) =>
            makeInsight({ duration_ms, queries: [makeQuery('select 1')] });

        it('labels the axis from zero to the length of the request', () => {
            const labels = tickLabels(render(insight(100)));

            expect(labels[0]).toBe('0 ms');
            expect(labels.at(-1)).toBe('100 ms');
        });

        it('keeps every label while the axis is not measured', () => {
            expect(tickLabels(render(insight(46.99)))).toEqual([
                '0 ms',
                '20 ms',
                '46.99 ms',
            ]);
        });

        it('keeps every label when the axis is wide enough', () => {
            setAxisWidth(400);

            expect(tickLabels(render(insight(46.99)))).toEqual([
                '0 ms',
                '20 ms',
                '46.99 ms',
            ]);
        });

        it('drops the inner label when the axis is too narrow for it', async () => {
            setAxisWidth(150);

            expect(tickLabels(await renderMeasured(insight(46.99)))).toEqual([
                '0 ms',
                '46.99 ms',
            ]);
        });

        it('keeps only the length of the request on a very narrow axis', async () => {
            setAxisWidth(70);

            expect(tickLabels(await renderMeasured(insight(217.73)))).toEqual([
                '217.73 ms',
            ]);
        });

        it('fits the labels again when the axis is resized', async () => {
            const wrapper = render(insight(46.99));

            setAxisWidth(150);
            await nextTick();
            await nextTick();

            expect(tickLabels(wrapper)).toEqual(['0 ms', '46.99 ms']);
        });
    });

    describe('trimmed insight', () => {
        it('tells that the insight was trimmed', () => {
            const wrapper = render(makeInsight({ truncated: true }));

            expect(wrapper.text()).toContain(
                'The insight was too large and was trimmed.',
            );
        });

        it('tells it also when there is no timeline', () => {
            const wrapper = render(makeInsight({ truncated: true, duration_ms: 0 }));

            expect(wrapper.text()).toContain(
                'The insight was too large and was trimmed.',
            );
        });

        it('says nothing for an insight that is whole', () => {
            const wrapper = render(makeInsight());

            expect(wrapper.text()).not.toContain('trimmed');
        });
    });
});
