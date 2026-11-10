import type { RequestLog } from '@/interfaces/history/logs';
import {
    logMatchesQuery,
    logMatchesStatusClasses,
    statusClassOf,
} from '@/utils/history/historyFilter';
import { describe, expect, it } from 'vitest';

const log = {
    durationInMs: 1,
    isProcessing: false,
    request: { endpoint: 'api/users/5', body: '{"name":"Ada"}' },
    response: { statusCode: 404, statusText: 'Not Found', body: '{"error":"missing"}' },
} as unknown as RequestLog;

describe('historyFilter', () => {
    it.each(['users/5', '404', 'not found', 'ada', 'missing'])(
        'matches the query "%s"',
        query => {
            expect(logMatchesQuery(log, query)).toBe(true);
        },
    );

    it('does not match an unrelated query', () => {
        expect(logMatchesQuery(log, 'orders')).toBe(false);
    });

    it('matches everything for a blank query', () => {
        expect(logMatchesQuery(log, '  ')).toBe(true);
    });

    it('maps status codes to classes', () => {
        expect(statusClassOf(204)).toBe('2xx');
        expect(statusClassOf(503)).toBe('5xx');
        expect(statusClassOf(0)).toBeNull();
    });

    it('filters by status class', () => {
        expect(logMatchesStatusClasses(log, [])).toBe(true);
        expect(logMatchesStatusClasses(log, ['4xx'])).toBe(true);
        expect(logMatchesStatusClasses(log, ['2xx', '5xx'])).toBe(false);
    });
});
