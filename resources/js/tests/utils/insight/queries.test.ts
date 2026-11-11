import type { InsightQuery } from '@/interfaces/http';
import { fillBindings, flattenSql, groupQueries } from '@/utils/insight';
import { describe, expect, it } from 'vitest';

const query = (sql: string, time_ms = 1, bindings: unknown[] = []): InsightQuery => ({
    sql,
    bindings,
    time_ms,
    connection: 'sqlite',
    offset_ms: 0,
});

describe('fillBindings', () => {
    it('fills placeholders in order and escapes strings', () => {
        expect(
            fillBindings('select * from t where a = ? and b = ? and c = ? and d = ?', [
                1,
                "O'Neil",
                null,
                true,
            ]),
        ).toBe("select * from t where a = 1 and b = 'O''Neil' and c = NULL and d = true");
    });

    it('keeps a placeholder that has no binding', () => {
        expect(fillBindings('select ?', [])).toBe('select ?');
    });
});

describe('groupQueries', () => {
    it('groups queries of the same shape and counts them', () => {
        const groups = groupQueries([
            query('select * from posts where id = ?', 2, [1]),
            query('select * from users', 3),
            query('select * from posts where id = ?', 4, [2]),
        ]);

        expect(groups).toHaveLength(2);
        expect(groups[0]).toMatchObject({
            count: 2,
            totalMs: 6,
            avgMs: 3,
            maxMs: 4,
            sample: 'select * from posts where id = 1',
        });
    });

    it('keeps the positions of the grouped queries in the original list', () => {
        const groups = groupQueries([
            query('select * from posts where id = ?', 2, [1]),
            query('select * from users', 3),
            query('select * from posts where id = ?', 4, [2]),
        ]);

        expect(groups.map(group => group.indices)).toEqual([[0, 2], [1]]);
    });

    it('flags five or more of the same shape as an N+1', () => {
        const four = groupQueries(Array.from({ length: 4 }, () => query('select ?')));
        const five = groupQueries(Array.from({ length: 5 }, () => query('select ?')));

        expect(four[0].isNPlusOne).toBe(false);
        expect(five[0].isNPlusOne).toBe(true);
    });

    it('flags a query of 100 ms or more as slow', () => {
        expect(groupQueries([query('select 1', 99.9)])[0].isSlow).toBe(false);
        expect(groupQueries([query('select 1', 100)])[0].isSlow).toBe(true);
    });

    it('folds lists of placeholders of different lengths into one shape', () => {
        const groups = groupQueries([
            query('select * from t where id in (?, ?)', 1, [1, 2]),
            query('select * from t where id in (?, ?, ?)', 1, [1, 2, 3]),
        ]);

        expect(groups).toHaveLength(1);
        expect(groups[0].count).toBe(2);
    });
});

describe('flattenSql', () => {
    it('puts a statement on one line', () => {
        expect(flattenSql('select *\n  from users\n\twhere id = ?')).toBe(
            'select * from users where id = ?',
        );
    });

    it('trims the statement', () => {
        expect(flattenSql('  begin  ')).toBe('begin');
    });
});
