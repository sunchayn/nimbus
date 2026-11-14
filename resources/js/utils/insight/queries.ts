import type { InsightQuery } from '@/interfaces/http';

/** A group of this many queries with the same shape is flagged as an N+1. */
export const N_PLUS_ONE_THRESHOLD = 5;

/** A query at or above this duration in milliseconds is flagged as slow. */
export const SLOW_QUERY_MS = 100;

/**
 * Points at one query, for example after a click on the timeline.
 * The nonce changes on every pick, so choosing the same query twice still counts.
 */
export interface QueryFocus {
    index: number;
    nonce: number;
}

export interface QueryGroup {
    /** The SQL with placeholders, shared by every query in the group. */
    shape: string;
    /** The first query of the group with its bindings filled in. */
    sample: string;
    connection: string;
    count: number;
    totalMs: number;
    avgMs: number;
    maxMs: number;
    isNPlusOne: boolean;
    isSlow: boolean;
    /** The positions of the grouped queries in the original list. */
    indices: number[];
}

const formatBinding = (value: unknown): string => {
    if (value === null || value === undefined) {
        return 'NULL';
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    return `'${String(value).replaceAll("'", "''")}'`;
};

/**
 * Replaces each `?` placeholder with its binding, for reading only.
 */
export const fillBindings = (sql: string, bindings: unknown[]): string => {
    let position = 0;

    return sql.replace(/\?/g, match =>
        position < bindings.length ? formatBinding(bindings[position++]) : match,
    );
};

/**
 * Lists of placeholders differ in length between runs, so they are folded to one.
 */
const shapeOf = (sql: string): string =>
    sql.replace(/\(\s*\?(?:\s*,\s*\?)+\s*\)/g, '(?)');

/**
 * Groups queries with the same shape, in order of first appearance.
 */
export const groupQueries = (queries: InsightQuery[]): QueryGroup[] => {
    const groups = new Map<string, QueryGroup>();

    for (const [index, query] of queries.entries()) {
        const shape = shapeOf(query.sql);
        const key = `${query.connection}\u0000${shape}`;
        const group = groups.get(key);

        if (group) {
            group.count++;
            group.totalMs += query.time_ms;
            group.maxMs = Math.max(group.maxMs, query.time_ms);
            group.indices.push(index);
        } else {
            groups.set(key, {
                shape,
                sample: fillBindings(query.sql, query.bindings),
                connection: query.connection,
                count: 1,
                totalMs: query.time_ms,
                avgMs: 0,
                maxMs: query.time_ms,
                isNPlusOne: false,
                isSlow: false,
                indices: [index],
            });
        }
    }

    return [...groups.values()].map(group => ({
        ...group,
        totalMs: Math.round(group.totalMs * 100) / 100,
        avgMs: Math.round((group.totalMs / group.count) * 100) / 100,
        isNPlusOne: group.count >= N_PLUS_ONE_THRESHOLD,
        isSlow: group.maxMs >= SLOW_QUERY_MS,
    }));
};

/**
 * The statement on one line, so a list can truncate it with CSS and show the whole text in a tooltip.
 */
export const flattenSql = (sql: string): string => sql.replace(/\s+/g, ' ').trim();
