import AppIcon from '@/components/base/icons/AppIcon.vue';
import InsightTimelineRow from '@/components/domain/Client/Response/ResponseInsight/Timeline/InsightTimelineRow.vue';
import type { TimelineRow, TimelineTick } from '@/utils/insight';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { makeHttp, makeInsight, makeQuery, resetOffsets, timelineOf } from '../helpers';

const ticks: TimelineTick[] = [
    { percent: 0, label: '0 ms' },
    { percent: 100, label: '100 ms' },
];

const render = (row: TimelineRow, props: { ticks?: TimelineTick[] } = {}) =>
    mount(InsightTimelineRow, { props: { row, ticks, ...props } });

const queryRow = (...queries: ReturnType<typeof makeQuery>[]) =>
    timelineOf(makeInsight({ duration_ms: 100, queries })).rows;

const bar = (wrapper: ReturnType<typeof render>) =>
    wrapper.find('[data-testid="insight-waterfall-bar"]');

const iconNames = (wrapper: ReturnType<typeof render>) =>
    wrapper.findAllComponents(AppIcon).map(icon => icon.props('name'));

// Five identical queries make an N+1 group, so every row after the first one is nested.
const nPlusOneRows = (slowIndex?: number) =>
    queryRow(
        ...Array.from({ length: 5 }, (_, id) =>
            makeQuery('select * from users where id = ?', id === slowIndex ? 150 : 1, [
                id,
            ]),
        ),
    );

describe('InsightTimelineRow', () => {
    beforeEach(() => {
        resetOffsets();
    });

    describe('a query', () => {
        it('shows its statement on one line, with the full statement in a title', () => {
            const [row] = queryRow(makeQuery('select *\n from users', 2));

            const wrapper = render(row);
            const button = wrapper.find('[data-testid="insight-waterfall-row"]');

            expect(wrapper.find('[data-testid="insight-waterfall-label"]').text()).toBe(
                'select * from users',
            );
            expect(button.attributes('title')).toBe('select *\n from users');
        });

        it('names itself and where a click leads for assistive technology', () => {
            const [row] = queryRow(makeQuery('select 1', 2));

            expect(
                render(row)
                    .find('[data-testid="insight-waterfall-row"]')
                    .attributes('aria-label'),
            ).toBe('Query, select 1, 2 ms. Show it in Queries.');
        });

        it('shows its time', () => {
            const [row] = queryRow(makeQuery('select 1', 12.345));

            expect(render(row).text()).toContain('12.35 ms');
        });

        it('has a database icon', () => {
            const [row] = queryRow(makeQuery('select 1'));

            expect(iconNames(render(row))).toEqual(['i-hugeicons-database']);
        });

        it('places the bar at its start and gives it its share of the request', () => {
            const [row] = queryRow({ ...makeQuery('select 1', 20), offset_ms: 30 });

            const style = bar(render(row)).attributes('style');

            expect(style).toContain('left: 30%');
            expect(style).toContain('width: 20%');
        });

        it('draws the bar in the main color', () => {
            const [row] = queryRow(makeQuery('select 1'));

            expect(bar(render(row)).classes()).toContain('bg-primary');
        });

        it('draws a slow query in red, and only its time and bar', () => {
            const [row] = queryRow(makeQuery('select 1', 150));

            const wrapper = render(row);

            expect(bar(wrapper).classes()).toContain('bg-error');
            expect(wrapper.find('.text-error').text()).toBe('150 ms');
        });
    });

    describe('a nested query of an N+1 group', () => {
        it('is indented and muted', () => {
            const row = nPlusOneRows()[1];

            const wrapper = render(row);

            expect(
                wrapper
                    .find('[data-testid="insight-waterfall-cell"]')
                    .attributes('data-depth'),
            ).toBe('1');
            expect(
                wrapper.find('[data-testid="insight-waterfall-label"]').classes(),
            ).toContain('text-muted');
        });

        it('has no database icon, since the first query of the group has one', () => {
            const rows = nPlusOneRows();

            expect(iconNames(render(rows[0]))).toEqual(['i-hugeicons-database']);
            expect(iconNames(render(rows[1]))).toEqual([]);
        });

        it('keeps its own start on the axis', () => {
            const row = nPlusOneRows()[3];

            expect(bar(render(row)).attributes('style')).toContain('left: 3%');
        });

        it('draws its bar in a lighter tone', () => {
            const rows = nPlusOneRows();

            expect(bar(render(rows[0])).classes()).toContain('bg-primary');
            expect(bar(render(rows[1])).classes()).toContain('bg-primary/40');
        });

        it('draws a slow one in a lighter red, and keeps its time muted', () => {
            const row = nPlusOneRows(2)[2];

            const wrapper = render(row);

            expect(bar(wrapper).classes()).toContain('bg-error/50');
            expect(wrapper.find('.text-error').exists()).toBe(false);
        });
    });

    describe('an HTTP call', () => {
        const httpRow = () =>
            timelineOf(
                makeInsight({
                    duration_ms: 100,
                    http: [
                        makeHttp({
                            method: 'POST',
                            url: 'https://example.com/hook',
                            offset_ms: 10,
                            duration_ms: 20,
                        }),
                    ],
                }),
            ).rows[0];

        it('shows its method and URL, and names itself for assistive technology', () => {
            const wrapper = render(httpRow());

            expect(wrapper.find('[data-testid="insight-waterfall-label"]').text()).toBe(
                'POST https://example.com/hook',
            );
            expect(
                wrapper
                    .find('[data-testid="insight-waterfall-row"]')
                    .attributes('aria-label'),
            ).toBe('HTTP call, POST https://example.com/hook, 20 ms. Show it in HTTP.');
        });

        it('has a globe icon and no database icon', () => {
            expect(iconNames(render(httpRow()))).toEqual(['i-hugeicons-globe-02']);
        });

        it('draws its bar in a neutral tone', () => {
            expect(bar(render(httpRow())).classes()).toContain('bg-accented');
        });
    });

    it('draws a grid line at each tick', () => {
        const [row] = queryRow(makeQuery('select 1'));

        const lines = render(row, {
            ticks: [...ticks, { percent: 50, label: '50 ms' }],
        }).findAll('.bg-accented\\/60');

        expect(lines.map(line => line.attributes('style'))).toEqual([
            'left: 0%;',
            'left: 100%;',
            'left: 50%;',
        ]);
    });

    it('asks to be opened when clicked', async () => {
        const [row] = queryRow(makeQuery('select 1'));

        const wrapper = render(row);

        await wrapper.find('[data-testid="insight-waterfall-row"]').trigger('click');

        expect(wrapper.emitted('select')).toHaveLength(1);
    });
});
