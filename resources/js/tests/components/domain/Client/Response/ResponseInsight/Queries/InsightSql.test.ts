import InsightSql from '@/components/domain/Client/Response/ResponseInsight/Queries/InsightSql.vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const copy = vi.fn();

vi.mock('@vueuse/core', async importOriginal => ({
    ...(await importOriginal<Record<string, unknown>>()),
    useClipboard: () => ({ copy, copied: ref(false) }),
}));

const tokensOf = (sql: string) =>
    mount(InsightSql, { props: { sql } }).findAll(
        '[data-testid="insight-sql"] [data-token]',
    );

describe('InsightSql', () => {
    beforeEach(() => {
        copy.mockClear();
    });

    it('shows the whole statement', () => {
        const wrapper = mount(InsightSql, { props: { sql: 'select 1' } });

        expect(wrapper.find('[data-testid="insight-sql"]').text()).toBe('select 1');
    });

    it('marks keywords and strings', () => {
        const tokens = tokensOf("select * from users where name = 'a'");

        expect(
            tokens.filter(token => token.attributes('data-token') === 'keyword'),
        ).toHaveLength(3);
        expect(tokens.some(token => token.attributes('data-token') === 'string')).toBe(
            true,
        );
    });

    it('marks numbers and comments', () => {
        const types = tokensOf('select 1 -- note').map(token =>
            token.attributes('data-token'),
        );

        expect(types).toContain('number');
        expect(types).toContain('comment');
    });

    it('copies the statement it shows', async () => {
        const wrapper = mount(InsightSql, {
            props: { sql: 'select * from users where id = 7' },
        });

        await wrapper.find('[data-testid="insight-sql-copy"]').trigger('click');

        expect(copy).toHaveBeenCalledWith('select * from users where id = 7');
    });

    it('gives the copy button a background', () => {
        const wrapper = mount(InsightSql, { props: { sql: 'select 1' } });

        expect(wrapper.find('[data-testid="insight-sql-copy"]').classes()).toContain(
            'bg-elevated',
        );
    });
});
