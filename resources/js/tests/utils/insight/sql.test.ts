import { tokenizeSql } from '@/utils/insight';
import { describe, expect, it } from 'vitest';

describe('tokenizeSql', () => {
    it('marks keywords case insensitively', () => {
        const tokens = tokenizeSql('SELECT id from users');

        expect(tokens.filter(token => token.type === 'keyword').map(t => t.text)).toEqual(
            ['SELECT', 'from'],
        );
    });

    it('marks strings, numbers, quoted identifiers, and comments', () => {
        const tokens = tokenizeSql("where `name` = 'it''s' and age > 18 -- note");

        const byType = (type: string) =>
            tokens.filter(token => token.type === type).map(token => token.text);

        expect(byType('identifier')).toEqual(['`name`']);
        expect(byType('string')).toEqual(["'it''s'"]);
        expect(byType('number')).toEqual(['18']);
        expect(byType('comment')).toEqual(['-- note']);
    });

    it('does not mark keywords inside strings or longer words', () => {
        const tokens = tokenizeSql("select 'from' as selected");

        expect(tokens.filter(token => token.type === 'keyword').map(t => t.text)).toEqual(
            ['select', 'as'],
        );
    });

    it('keeps the original text when the tokens are joined', () => {
        const sql = 'insert into t (a, b) values (?, "x") /* c */';

        expect(
            tokenizeSql(sql)
                .map(token => token.text)
                .join(''),
        ).toBe(sql);
    });
});
