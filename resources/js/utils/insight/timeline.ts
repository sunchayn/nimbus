import type { Insight } from '@/interfaces/http';
import { SLOW_QUERY_MS, flattenSql, groupQueries } from './queries';

export type TimelineRowKind = 'query' | 'http';

export interface TimelineRow {
    kind: TimelineRowKind;
    /** The position of the row in `Insight.queries` or `Insight.http`, depending on its kind. */
    index: number;
    /** The statement on one line, or the method and URL of the call. */
    label: string;
    /** What a tooltip shows, which is the full statement or URL. */
    title: string;
    ms: number;
    /** Distance from the start of the request, from 0 to 100. */
    offsetPercent: number;
    /** Share of the request, from 0 to 100. */
    widthPercent: number;
    isSlow: boolean;
    /** A query of an N+1 group after the first one is nested under it, and every other row is flat. */
    depth: 0 | 1;
}

export interface TimelineTick {
    /** Position on the axis, from 0 to 100. */
    percent: number;
    label: string;
}

export interface Timeline {
    requestMs: number;
    queriesMs: number;
    /** What the request did besides running queries. */
    otherMs: number;
    /** Share of the request spent in queries, from 0 to 100. */
    queriesPercent: number;
    rows: TimelineRow[];
    /** Ticks of the axis, which runs from 0 to the end of the request. */
    ticks: TimelineTick[];
}

/**
 * A tick closer than this to the end of the axis would overlap the label of the end tick.
 * The end label is right aligned and wide, because it has the exact value.
 */
const END_TICK_CLEARANCE_PERCENT = 30;

const round = (value: number, digits = 2) => {
    const factor = 10 ** digits;

    return Math.round(value * factor) / factor;
};

/**
 * Picks a round step so the axis has about four ticks.
 */
const niceStep = (max: number): number => {
    const raw = max / 4;
    const magnitude = 10 ** Math.floor(Math.log10(raw));
    const fraction = raw / magnitude;
    const factor = fraction <= 1 ? 1 : fraction <= 2 ? 2 : fraction <= 5 ? 5 : 10;

    return factor * magnitude;
};

/**
 * Round ticks from 0, and one last tick at the end of the axis with its exact value.
 */
export const buildAxisTicks = (max: number): TimelineTick[] => {
    if (max <= 0) {
        return [];
    }

    const step = niceStep(max);
    const ticks: TimelineTick[] = [];

    for (let value = 0; value < max; value += step) {
        const percent = round((value / max) * 100, 1);

        if (percent <= 100 - END_TICK_CLEARANCE_PERCENT) {
            ticks.push({ percent, label: formatMilliseconds(round(value, 4)) });
        }
    }

    ticks.push({ percent: 100, label: formatMilliseconds(round(max, 2)) });

    return ticks;
};

/** The width of one character of a tick label, with some room to spare. */
const TICK_CHAR_PX = 8;

/** The space kept between two tick labels. */
const TICK_GAP_PX = 8;

/**
 * Keeps the ticks whose labels fit on an axis of the given width without touching each other.
 * The last tick always stays, and so does the first unless the two would touch. A width of 0 means the axis is not measured, so every tick stays.
 */
export const fitTicks = (ticks: TimelineTick[], widthPx: number): TimelineTick[] => {
    if (widthPx <= 0 || ticks.length < 3) {
        return ticks;
    }

    const span = (tick: TimelineTick) => tick.label.length * TICK_CHAR_PX;
    const first = ticks[0];
    const last = ticks[ticks.length - 1];

    // When the two edge labels cannot both fit, the length of the request matters more than the zero.
    if (span(first) + TICK_GAP_PX + span(last) > widthPx) {
        return [last];
    }

    // The first label starts on the left edge and the last one ends on the right edge.
    let reachedPx = span(first);
    const lastStartPx = widthPx - span(last);
    const kept = [first];

    for (const tick of ticks.slice(1, -1)) {
        const centerPx = (tick.percent / 100) * widthPx;
        const startPx = centerPx - span(tick) / 2;
        const endPx = centerPx + span(tick) / 2;

        if (startPx - reachedPx >= TICK_GAP_PX && lastStartPx - endPx >= TICK_GAP_PX) {
            kept.push(tick);
            reachedPx = endPx;
        }
    }

    return [...kept, last];
};

/**
 * Places each query and each HTTP call that has a start time on one axis that runs over the whole request.
 * A call without a start time, such as a failed one, cannot be placed and is left out.
 */
export const buildTimeline = (insight: Insight): Timeline | null => {
    const queriesMs = round(
        insight.queries.reduce((sum, query) => sum + query.time_ms, 0),
        4,
    );

    const calls = insight.http.flatMap((call, index) =>
        call.offset_ms === null || call.duration_ms === null
            ? []
            : [{ call, index, offset: call.offset_ms, ms: call.duration_ms }],
    );

    const lastEnd = Math.max(
        0,
        ...insight.queries.map(query => query.offset_ms + query.time_ms),
        ...calls.map(({ offset, ms }) => offset + ms),
    );

    // A bar that ends after the recorded duration would leave the axis, so the axis grows to hold it.
    const requestMs = Math.max(insight.duration_ms, lastEnd);

    if (requestMs <= 0) {
        return null;
    }

    // The first query of an N+1 group is the main row, and the others are nested right below it.
    const childrenOf = new Map<number, number[]>();
    const nested = new Set<number>();

    for (const group of groupQueries(insight.queries)) {
        if (group.isNPlusOne) {
            childrenOf.set(group.indices[0], group.indices.slice(1));
            group.indices.slice(1).forEach(index => nested.add(index));
        }
    }

    const place = (offset: number, ms: number) => ({
        offsetPercent: round((offset / requestMs) * 100, 2),
        widthPercent: round((ms / requestMs) * 100, 2),
    });

    const rows: TimelineRow[] = [
        ...insight.queries.map(
            (query, index): TimelineRow => ({
                kind: 'query',
                index,
                label: flattenSql(query.sql),
                title: query.sql,
                ms: query.time_ms,
                ...place(query.offset_ms, query.time_ms),
                isSlow: query.time_ms >= SLOW_QUERY_MS,
                depth: 0,
            }),
        ),
        ...calls.map(
            ({ call, index, offset, ms }): TimelineRow => ({
                kind: 'http',
                index,
                label: `${call.method} ${call.url}`,
                title: `${call.method} ${call.url}`,
                ms,
                ...place(offset, ms),
                isSlow: false,
                depth: 0,
            }),
        ),
    ].sort((a, b) => a.offsetPercent - b.offsetPercent);

    const queryRows = new Map(
        rows.filter(row => row.kind === 'query').map(row => [row.index, row]),
    );

    // The nesting is only visual, so every bar keeps its own start and width on the axis.
    const ordered = rows.flatMap((row): TimelineRow[] => {
        if (row.kind === 'query' && nested.has(row.index)) {
            return [];
        }

        const children = row.kind === 'query' ? (childrenOf.get(row.index) ?? []) : [];

        return [
            row,
            ...children.flatMap(index => {
                const child = queryRows.get(index);

                return child ? [{ ...child, depth: 1 as const }] : [];
            }),
        ];
    });

    return {
        requestMs,
        queriesMs,
        otherMs: Math.max(0, round(requestMs - queriesMs)),
        queriesPercent: round((queriesMs / requestMs) * 100, 1),
        rows: ordered,
        ticks: buildAxisTicks(requestMs),
    };
};

export const formatMilliseconds = (ms: number): string =>
    ms >= 1000 ? `${(ms / 1000).toFixed(2)} s` : `${Math.round(ms * 100) / 100} ms`;

export const formatBytes = (bytes: number): string =>
    bytes >= 1048576
        ? `${(bytes / 1048576).toFixed(1)} MB`
        : `${Math.round(bytes / 1024)} KB`;

/**
 * The number shown on the Insight tab.
 */
export const insightCount = (insight: Insight): number =>
    insight.queries.length +
    insight.logs.length +
    insight.gates.length +
    insight.cache.length +
    insight.jobs.length +
    insight.mail.length +
    insight.notifications.length +
    insight.http.length +
    insight.events.length +
    (insight.exception ? 1 : 0);
