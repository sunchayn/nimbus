import type { InsightGate } from '@/interfaces/http';
import { deniedFirst } from '@/utils/insight';
import { describe, expect, it } from 'vitest';

describe('deniedFirst', () => {
    it('puts denied checks first and keeps the rest in order', () => {
        const check = (ability: string, result: boolean | null): InsightGate => ({
            ability,
            result,
            arguments: [],
            user: null,
        });

        expect(
            deniedFirst([
                check('a', true),
                check('b', false),
                check('c', null),
                check('d', false),
            ]).map(item => item.ability),
        ).toEqual(['b', 'd', 'a', 'c']);
    });
});
