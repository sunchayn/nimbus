import type { RequestLog } from '@/interfaces/history/logs';

export type StatusClass = '2xx' | '3xx' | '4xx' | '5xx';

export const STATUS_CLASSES: StatusClass[] = ['2xx', '3xx', '4xx', '5xx'];

export const statusClassOf = (statusCode: number): StatusClass | null => {
    const group = Math.floor(statusCode / 100);

    return group >= 2 && group <= 5 ? (`${group}xx` as StatusClass) : null;
};

const asText = (body: unknown): string => (typeof body === 'string' ? body : '');

/**
 * Matches the query against the path, the status, and the request and response bodies.
 */
export const logMatchesQuery = (log: RequestLog, query: string): boolean => {
    const needle = query.trim().toLowerCase();

    if (!needle) {
        return true;
    }

    return [
        log.request.endpoint,
        String(log.response?.statusCode ?? ''),
        log.response?.statusText ?? '',
        asText(log.request.body),
        asText(log.response?.body),
    ].some(haystack => haystack.toLowerCase().includes(needle));
};

export const logMatchesStatusClasses = (
    log: RequestLog,
    classes: StatusClass[],
): boolean =>
    classes.length === 0 ||
    (log.response !== undefined &&
        classes.includes(statusClassOf(log.response.statusCode) as StatusClass));
