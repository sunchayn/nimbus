import type { Insight, InsightHttpEntry, InsightQuery } from '@/interfaces/http';
import {
    buildAxisTicks,
    buildTimeline,
    fitTicks,
    formatBytes,
    formatMilliseconds,
} from '@/utils/insight';
import { describe, expect, it } from 'vitest';

const query = (offset_ms: number, time_ms: number, sql = 'select 1'): InsightQuery => ({
    sql,
    bindings: [],
    time_ms,
    connection: 'sqlite',
    offset_ms,
});

const call = (
    offset_ms: number | null,
    duration_ms: number | null,
): InsightHttpEntry => ({
    method: 'GET',
    url: `https://a.test/${offset_ms}`,
    status: duration_ms === null ? null : 200,
    duration_ms,
    offset_ms,
});

const insight = (duration_ms: number, queries: InsightQuery[] = []): Insight => ({
    queries,
    logs: [],
    gates: [],
    cache: [],
    jobs: [],
    mail: [],
    notifications: [],
    http: [],
    events: [],
    exception: null,
    peak_memory: 0,
    duration_ms,
    dropped: {
        queries: 0,
        logs: 0,
        cache: 0,
        jobs: 0,
        mail: 0,
        notifications: 0,
        http: 0,
        events: 0,
    },
    truncated: false,
});

describe('buildTimeline', () => {
    it('splits the request into query time and the rest', () => {
        expect(buildTimeline(insight(100, [query(0, 10), query(20, 15)]))).toMatchObject({
            requestMs: 100,
            queriesMs: 25,
            otherMs: 75,
            queriesPercent: 25,
        });
    });

    it('places each query at its real offset on the request axis', () => {
        const timeline = buildTimeline(
            insight(100, [query(10, 10, 'select 0'), query(60, 20, 'select 1')]),
        );

        expect(timeline?.rows).toMatchObject([
            {
                kind: 'query',
                index: 0,
                label: 'select 0',
                ms: 10,
                offsetPercent: 10,
                widthPercent: 10,
            },
            { kind: 'query', index: 1, offsetPercent: 60, widthPercent: 20 },
        ]);
    });

    it('keeps the full statement as the title and a flat one as the label', () => {
        const timeline = buildTimeline(
            insight(10, [query(0, 1, 'select *\n from users')]),
        );

        expect(timeline?.rows[0]).toMatchObject({
            label: 'select * from users',
            title: 'select *\n from users',
        });
    });

    it('marks a slow query', () => {
        const timeline = buildTimeline(
            insight(500, [
                query(0, 150, 'select * from orders'),
                query(200, 1, 'select 1'),
            ]),
        );

        expect(timeline?.rows.map(row => row.isSlow)).toEqual([true, false]);
    });

    it('nests the other queries of an N+1 group under the first one', () => {
        const timeline = buildTimeline(
            insight(500, [
                query(0, 150, 'select * from orders'),
                ...Array.from({ length: 5 }, (_, i) =>
                    query(200 + i * 10, 1, 'select * from users where id = ?'),
                ),
                query(400, 2, 'select * from posts'),
            ]),
        );

        expect(timeline?.rows.map(row => [row.index, row.depth])).toEqual([
            [0, 0],
            [1, 0],
            [2, 1],
            [3, 1],
            [4, 1],
            [5, 1],
            [6, 0],
        ]);
    });

    it('keeps the real offset of a nested query, and its own number', () => {
        const timeline = buildTimeline(
            insight(500, [
                ...Array.from({ length: 5 }, (_, i) =>
                    query(100 + i * 50, 1, 'select * from users where id = ?'),
                ),
                query(120, 1, 'select * from posts'),
            ]),
        );

        // The query at 120 ms comes between the first two, but the group is kept together.
        expect(timeline?.rows.map(row => row.index)).toEqual([0, 1, 2, 3, 4, 5]);
        expect(timeline?.rows.find(row => row.index === 3)).toMatchObject({
            depth: 1,
            offsetPercent: 50,
        });
        expect(timeline?.rows.find(row => row.index === 5)?.depth).toBe(0);
    });

    it('leaves queries that are not in an N+1 group flat', () => {
        const timeline = buildTimeline(
            insight(100, [query(0, 1, 'select 1'), query(10, 1, 'select 1')]),
        );

        expect(timeline?.rows.map(row => row.depth)).toEqual([0, 0]);
    });

    it('adds an HTTP call that has a start time and leaves out one that has none', () => {
        const timeline = buildTimeline({
            ...insight(100),
            http: [call(30, 20), call(null, null)],
        });

        expect(timeline?.rows).toHaveLength(1);
        expect(timeline?.rows[0]).toMatchObject({
            kind: 'http',
            index: 0,
            label: 'GET https://a.test/30',
            offsetPercent: 30,
            widthPercent: 20,
        });
    });

    it('orders the rows by start time', () => {
        const timeline = buildTimeline({
            ...insight(100, [query(50, 5)]),
            http: [call(10, 5)],
        });

        expect(timeline?.rows.map(row => row.kind)).toEqual(['http', 'query']);
    });

    it('stretches the request to cover queries that end after the duration', () => {
        expect(buildTimeline(insight(5, [query(0, 20)]))).toMatchObject({
            requestMs: 20,
            otherMs: 0,
            queriesPercent: 100,
        });
    });

    it('keeps the request summary when there are no queries', () => {
        expect(buildTimeline(insight(40))).toMatchObject({
            requestMs: 40,
            queriesMs: 0,
            rows: [],
        });
    });

    it('returns nothing without a duration', () => {
        expect(buildTimeline(insight(0))).toBeNull();
    });
});

describe('fitTicks', () => {
    const ticks = buildAxisTicks(46.99);

    it('keeps every tick when the axis is not measured', () => {
        expect(fitTicks(ticks, 0)).toEqual(ticks);
    });

    it('keeps the inner tick when there is room for all labels', () => {
        expect(fitTicks(ticks, 400).map(tick => tick.label)).toEqual([
            '0 ms',
            '20 ms',
            '46.99 ms',
        ]);
    });

    it('drops the inner tick when its label would touch the end label', () => {
        expect(fitTicks(ticks, 150).map(tick => tick.label)).toEqual([
            '0 ms',
            '46.99 ms',
        ]);
    });

    it('keeps the first and the last tick when both fit', () => {
        const kept = fitTicks(buildAxisTicks(1234.56), 300);

        expect(kept[0].percent).toBe(0);
        expect(kept[kept.length - 1].percent).toBe(100);
    });

    it('keeps only the length of the request when the edge labels would touch', () => {
        expect(fitTicks(buildAxisTicks(217.73), 70).map(tick => tick.label)).toEqual([
            '217.73 ms',
        ]);
    });
});

describe('buildAxisTicks', () => {
    it('picks round steps from zero', () => {
        expect(buildAxisTicks(100).map(tick => tick.label)).toEqual([
            '0 ms',
            '50 ms',
            '100 ms',
        ]);
    });

    it('places each tick at its share of the axis', () => {
        expect(buildAxisTicks(2).map(tick => tick.percent)).toEqual([0, 25, 50, 100]);
    });

    it('drops a tick that would overlap the label of the end tick', () => {
        expect(buildAxisTicks(46.99).map(tick => tick.label)).toEqual([
            '0 ms',
            '20 ms',
            '46.99 ms',
        ]);
    });

    it('ends the axis with the exact length of the request', () => {
        const ticks = buildAxisTicks(137);

        expect(ticks[ticks.length - 1]).toEqual({
            percent: 100,
            label: '137 ms',
        });
    });

    it('handles fractions of a millisecond', () => {
        expect(buildAxisTicks(0.9).map(tick => tick.label)).toEqual([
            '0 ms',
            '0.5 ms',
            '0.9 ms',
        ]);
    });

    it('has no ticks for an empty axis', () => {
        expect(buildAxisTicks(0)).toEqual([]);
    });
});

describe('formatters', () => {
    it('formats milliseconds and bytes', () => {
        expect(formatMilliseconds(12.345)).toBe('12.35 ms');
        expect(formatMilliseconds(1500)).toBe('1.50 s');
        expect(formatBytes(2048)).toBe('2 KB');
        expect(formatBytes(2097152)).toBe('2.0 MB');
    });
});
