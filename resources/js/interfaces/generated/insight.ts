/*
 * This file is auto-generated.
 * Don't update it manually, otherwise, your changes will be lost.
 * To update the file run `php bin/intellisense`.
 *
 * Generated at: 2026-11-11T21:25:27+00:00.
 */

export interface InsightQuery {
    sql: string;
    bindings: unknown[];
    time_ms: number;
    offset_ms: number;
    connection: string;
}

export interface InsightLog {
    level: string;
    message: string;
    context: Record<string, unknown>;
}

export interface InsightGate {
    ability: string;
    result: boolean | null;
    arguments: string[];
    user: string | null;
}

export interface InsightCacheEntry {
    operation: 'hit' | 'miss' | 'write' | 'forget';
    key: string;
}

export interface InsightJobEntry {
    job: string;
    queue: string | null;
    connection: string | null;
}

export interface InsightMailEntry {
    subject: string;
    to: string[];
    mailable: string | null;
}

export interface InsightNotificationEntry {
    notification: string;
    channel: string;
}

export interface InsightHttpEntry {
    method: string;
    url: string;
    status: number | null;
    duration_ms: number | null;
    offset_ms: number | null;
}

export interface InsightEventEntry {
    name: string;
}

export interface InsightFrame {
    file: string;
    line: number;
    call: string;
    code: Record<number, string>;
}

export interface InsightExceptionInfo {
    class: string;
    message: string;
    file: string;
    line: number;
    trace: InsightFrame[];
}

export interface InsightDropped {
    queries: number;
    logs: number;
    cache: number;
    jobs: number;
    mail: number;
    notifications: number;
    http: number;
    events: number;
}

export interface Insight {
    queries: InsightQuery[];
    logs: InsightLog[];
    gates: InsightGate[];
    cache: InsightCacheEntry[];
    jobs: InsightJobEntry[];
    mail: InsightMailEntry[];
    notifications: InsightNotificationEntry[];
    http: InsightHttpEntry[];
    events: InsightEventEntry[];
    exception: InsightExceptionInfo | null;
    peak_memory: number;
    duration_ms: number;
    dropped: InsightDropped;
    truncated: boolean;
}
