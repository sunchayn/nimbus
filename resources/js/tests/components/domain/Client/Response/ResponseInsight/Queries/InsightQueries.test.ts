import InsightQueries from '@/components/domain/Client/Response/ResponseInsight/Queries/InsightQueries.vue';
import type { InsightQuery } from '@/interfaces/http';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { makeQuery, mixedQueries, resetOffsets } from '../helpers';

const copy = vi.fn();

vi.mock('@vueuse/core', async importOriginal => ({
    ...(await importOriginal<Record<string, unknown>>()),
    useClipboard: () => ({ copy, copied: ref(false) }),
}));

const render = (queries: InsightQuery[], dropped?: number) =>
    mount(InsightQueries, { props: { queries, dropped }, attachTo: document.body });

const groupsOf = (wrapper: ReturnType<typeof render>) =>
    wrapper.findAll('[data-testid="insight-query-group"]');

describe('InsightQueries', () => {
    beforeEach(() => {
        resetOffsets();
        copy.mockClear();
    });

    describe('summary', () => {
        it('sums up the queries in one line above the list', () => {
            const wrapper = render(mixedQueries());

            expect(wrapper.find('[data-testid="insight-queries-summary"]').text()).toBe(
                '7 queries in 3 groups, 156 ms total',
            );
        });

        it('leaves out the groups when every query has its own', () => {
            const wrapper = render([makeQuery('select 1'), makeQuery('select 2')]);

            expect(wrapper.find('[data-testid="insight-queries-summary"]').text()).toBe(
                '2 queries, 2 ms total',
            );
        });

        it('uses the singular for one query', () => {
            const wrapper = render([makeQuery('select 1')]);

            expect(wrapper.find('[data-testid="insight-queries-summary"]').text()).toBe(
                '1 query, 1 ms total',
            );
        });

        it('shows a note instead of cards when no query ran', () => {
            const wrapper = render([]);

            expect(wrapper.text()).toContain('No queries were run.');
            expect(groupsOf(wrapper)).toHaveLength(0);
        });

        it('tells how many queries were not shown', () => {
            const wrapper = render([makeQuery('select 1')], 12);

            expect(wrapper.find('[data-testid="insight-dropped"]').text()).toContain(
                '12',
            );
        });
    });

    describe('cards', () => {
        it('draws one card with a database icon for each group', () => {
            const wrapper = render(mixedQueries());

            expect(groupsOf(wrapper)).toHaveLength(3);
            expect(wrapper.findAll('[data-testid="insight-query-icon"]')).toHaveLength(3);
        });

        it('counts the queries of a group', () => {
            const wrapper = render(mixedQueries());

            expect(groupsOf(wrapper)[0].text()).toContain('x5');
            expect(groupsOf(wrapper)[1].text()).not.toContain('x1');
        });

        it('flags an N+1 and a slow query with a dot on a neutral chip', () => {
            const wrapper = render(mixedQueries());

            const flags = groupsOf(wrapper).map(card =>
                card
                    .findAll('[data-testid="insight-flag-dot"]')
                    .map(dot => [dot.attributes('data-tone'), dot.classes()]),
            );

            expect(flags[0]).toEqual([
                ['warning', expect.arrayContaining(['bg-warning'])],
            ]);
            expect(flags[1]).toEqual([['error', expect.arrayContaining(['bg-error'])]]);
            expect(flags[2]).toEqual([]);
            expect(groupsOf(wrapper)[0].text()).toContain('N+1');
            expect(groupsOf(wrapper)[1].text()).toContain('Slow');
        });

        it('shows the SQL in the header only while the card is collapsed', async () => {
            const wrapper = render([makeQuery('select 1')]);

            expect(wrapper.find('[data-testid="insight-query-label"]').exists()).toBe(
                false,
            );

            await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');

            expect(wrapper.find('[data-testid="insight-query-label"]').text()).toBe(
                'select 1',
            );
        });

        it('collapses and opens a card', async () => {
            const wrapper = render([makeQuery('select 1')]);

            expect(wrapper.find('[data-testid="insight-sql"]').exists()).toBe(true);

            await wrapper.find('[data-testid="insight-card-trigger"]').trigger('click');

            expect(wrapper.find('[data-testid="insight-sql"]').exists()).toBe(false);
        });

        it('opens the first three groups and the ones that need attention', () => {
            const queries = [
                makeQuery('select 1'),
                makeQuery('select 2'),
                makeQuery('select 3'),
                makeQuery('select 4'),
                makeQuery('select 5', 150),
            ];

            const wrapper = render(queries);

            const open = groupsOf(wrapper).map(card =>
                card.find('[data-testid="insight-sql"]').exists(),
            );

            expect(open).toEqual([true, true, true, false, true]);
        });

        it('shows the time of a group, with the word total only for several queries', () => {
            const wrapper = render(mixedQueries());

            const timings = wrapper
                .findAll('[data-testid="insight-query-timing"]')
                .map(item => item.text());

            expect(timings[0]).toBe('5 ms total');
            expect(timings[1]).toBe('150 ms');
        });

        it('names the connection only when the queries use more than one', () => {
            const one = render([makeQuery('select 1')]);
            const two = render([
                makeQuery('select 1'),
                { ...makeQuery('select 2'), connection: 'mysql' },
            ]);

            expect(one.find('[data-testid="insight-query-timing"]').text()).not.toContain(
                'on sqlite',
            );
            expect(two.find('[data-testid="insight-query-timing"]').text()).toContain(
                'on sqlite',
            );
        });

        it('shows the SQL with its bindings filled in, and copies it', async () => {
            const wrapper = render([
                makeQuery('select * from users where id = ?', 1, [7]),
            ]);

            expect(wrapper.find('[data-testid="insight-sql"]').text()).toBe(
                'select * from users where id = 7',
            );

            await wrapper.find('[data-testid="insight-sql-copy"]').trigger('click');

            expect(copy).toHaveBeenCalledWith('select * from users where id = 7');
        });

        it('lists the runs of a group only for a group of several queries', () => {
            const wrapper = render(mixedQueries());

            expect(
                groupsOf(wrapper).map(card =>
                    card.find('[data-testid="insight-query-runs"]').exists(),
                ),
            ).toEqual([true, false, false]);
        });
    });

    describe('filters', () => {
        it('shows the chips with their counts only for kinds that exist', () => {
            const none = render([makeQuery('select 1')]);
            const some = render(mixedQueries());

            expect(none.find('[data-testid="insight-query-filters"]').exists()).toBe(
                false,
            );
            expect(
                some.find('[data-testid="insight-filter-n-plus-one"]').text(),
            ).toContain('1');
            expect(some.find('[data-testid="insight-filter-slow"]').text()).toContain(
                '1',
            );
        });

        it('hides the chip of a kind that does not exist', () => {
            const wrapper = render([makeQuery('select 1', 150)]);

            expect(wrapper.find('[data-testid="insight-filter-slow"]').exists()).toBe(
                true,
            );
            expect(
                wrapper.find('[data-testid="insight-filter-n-plus-one"]').exists(),
            ).toBe(false);
        });

        it('lists only the matching groups when a chip is on', async () => {
            const wrapper = render(mixedQueries());

            await wrapper.find('[data-testid="insight-filter-slow"]').trigger('click');

            expect(groupsOf(wrapper)).toHaveLength(1);
            expect(groupsOf(wrapper)[0].text()).toContain('Slow');
        });

        it('lists the groups that match any active chip', async () => {
            const wrapper = render(mixedQueries());

            await wrapper.find('[data-testid="insight-filter-slow"]').trigger('click');
            await wrapper
                .find('[data-testid="insight-filter-n-plus-one"]')
                .trigger('click');

            expect(groupsOf(wrapper)).toHaveLength(2);
        });

        it('lists all the groups again when a chip is switched off', async () => {
            const wrapper = render(mixedQueries());
            const chip = wrapper.find('[data-testid="insight-filter-slow"]');

            await chip.trigger('click');
            await chip.trigger('click');

            expect(groupsOf(wrapper)).toHaveLength(3);
        });
    });
});
