import InsightQueryRuns from '@/components/domain/Client/Response/ResponseInsight/Queries/InsightQueryRuns.vue';
import type { InsightQuery } from '@/interfaces/http';
import { groupQueries } from '@/utils/insight';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import {
    activeTab,
    makeInsight,
    makeQuery,
    mixedQueries,
    openTab,
    render as renderPanel,
    resetOffsets,
    stubScrolling,
} from '../helpers';

const usersQueries = (count: number) =>
    Array.from({ length: count }, (_, id) =>
        makeQuery('select * from users where id = ?', 1, [id]),
    );

const render = (queries: InsightQuery[]) =>
    mount(InsightQueryRuns, { props: { group: groupQueries(queries)[0], queries } });

const open = async (wrapper: ReturnType<typeof render>) => {
    await wrapper.find('[data-testid="insight-query-runs-trigger"]').trigger('click');
    await flushPromises();
};

describe('InsightQueryRuns', () => {
    beforeEach(() => {
        resetOffsets();
    });

    it('counts the other runs and sums up their time on a closed trigger', () => {
        const wrapper = render(usersQueries(5));

        const trigger = wrapper.find('[data-testid="insight-query-runs-trigger"]');

        expect(trigger.text()).toContain('4 more queries');
        expect(trigger.text()).toContain('Average 1 ms, slowest 1 ms');
        expect(wrapper.findAll('[data-testid="insight-query-run-row"]')).toHaveLength(0);
    });

    it('lists the other runs with their bindings once opened', async () => {
        const wrapper = render(usersQueries(5));

        await open(wrapper);

        const rows = wrapper.findAll('[data-testid="insight-query-run-row"]');

        expect(rows).toHaveLength(4);
        expect(rows[0].text()).toContain('id = 1');
        expect(rows[3].text()).toContain('id = 4');
    });

    it('leaves out the first query, which the card already shows', async () => {
        const wrapper = render(usersQueries(5));

        await open(wrapper);

        expect(wrapper.text()).not.toContain('id = 0');
    });

    it('shows a run as its statement only, with no timing and no button to leave the tab', async () => {
        const wrapper = render(usersQueries(5));

        await open(wrapper);

        for (const row of wrapper.findAll('[data-testid="insight-query-run-row"]')) {
            // The copy button of the statement is the only button in a run.
            expect(
                row.findAll('button').map(button => button.attributes('data-testid')),
            ).toEqual(['insight-sql-copy']);
            expect(row.text()).not.toMatch(/\d+(\.\d+)? ms/);
        }
    });

    it('cuts a long list of runs short', async () => {
        const wrapper = render(usersQueries(30));

        await open(wrapper);

        expect(wrapper.findAll('[data-testid="insight-query-run-row"]')).toHaveLength(24);
        expect(wrapper.find('[data-testid="insight-query-runs-more"]').text()).toBe(
            'and 5 more',
        );
    });

    it('shows no note about hidden runs when all of them fit', async () => {
        const wrapper = render(usersQueries(5));

        await open(wrapper);

        expect(wrapper.find('[data-testid="insight-query-runs-more"]').exists()).toBe(
            false,
        );
    });

    describe('in the panel', () => {
        let scrollIntoView: ReturnType<typeof stubScrolling>;

        beforeEach(() => {
            scrollIntoView = stubScrolling();
        });

        it('stays on the Queries tab when a run is clicked', async () => {
            const wrapper = renderPanel(makeInsight({ queries: mixedQueries() }));

            await openTab(wrapper, 'Queries');
            await wrapper
                .find('[data-testid="insight-query-runs-trigger"]')
                .trigger('click');
            await flushPromises();
            await wrapper
                .findAll('[data-testid="insight-query-run-row"]')[1]
                .trigger('click');
            await flushPromises();

            expect(activeTab(wrapper)).toContain('Queries');
            expect(scrollIntoView).not.toHaveBeenCalled();
        });
    });
});
