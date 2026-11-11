import { flushPromises } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    activeTab,
    makeHttp,
    makeInsight,
    makeQuery,
    mixedQueries,
    render,
    resetOffsets,
    stubScrolling,
} from '../helpers';

// A click on a row of the timeline goes through the panel, which owns the tabs.
const clickRow = async (wrapper: ReturnType<typeof render>, index: number) => {
    await wrapper
        .findAll('[data-testid="insight-waterfall-row"]')
        [index].trigger('click');
    await flushPromises();
};

const groupsOf = (wrapper: ReturnType<typeof render>) =>
    wrapper.findAll('[data-testid="insight-query-group"]');

describe('timeline navigation', () => {
    let scrollIntoView: ReturnType<typeof stubScrolling>;

    beforeEach(() => {
        resetOffsets();
        scrollIntoView = stubScrolling();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('opens the Queries tab and scrolls to the clicked query', async () => {
        const wrapper = render(makeInsight({ queries: mixedQueries() }));

        await clickRow(wrapper, 6);

        expect(activeTab(wrapper)).toContain('Queries');
        expect(scrollIntoView).toHaveBeenCalledOnce();
    });

    it('opens the Queries tab for a nested query of an N+1 group too', async () => {
        const wrapper = render(makeInsight({ queries: mixedQueries() }));

        await clickRow(wrapper, 2);

        expect(activeTab(wrapper)).toContain('Queries');
    });

    it('opens the HTTP tab for a call', async () => {
        const wrapper = render(makeInsight({ http: [makeHttp()] }));

        await clickRow(wrapper, 0);

        expect(activeTab(wrapper)).toContain('HTTP');
    });

    it('highlights the group of the clicked query for a moment', async () => {
        vi.useFakeTimers();

        const wrapper = render(makeInsight({ queries: mixedQueries() }));

        // The second query belongs to the N+1 group.
        await clickRow(wrapper, 1);

        const group = () => groupsOf(wrapper).find(card => card.text().includes('N+1'));

        expect(group()?.attributes('data-highlighted')).toBeDefined();

        await vi.advanceTimersByTimeAsync(1300);

        expect(group()?.attributes('data-highlighted')).toBeUndefined();
    });

    it('lists a hidden group again when a filter would hide the clicked query', async () => {
        const wrapper = render(makeInsight({ queries: mixedQueries() }));

        await wrapper.find('[data-testid="insight-filter-slow"]').trigger('click');

        expect(groupsOf(wrapper)).toHaveLength(1);

        await clickRow(wrapper, 0);

        expect(groupsOf(wrapper)).toHaveLength(3);
    });

    it('opens a collapsed group for the clicked query', async () => {
        const queries = [
            makeQuery('select 1'),
            makeQuery('select 2'),
            makeQuery('select 3'),
            makeQuery('select 4'),
        ];

        const wrapper = render(makeInsight({ queries }));

        // The fourth group starts closed.
        expect(wrapper.findAll('[data-testid="insight-sql"]')).toHaveLength(3);

        await clickRow(wrapper, 3);

        expect(wrapper.findAll('[data-testid="insight-sql"]')).toHaveLength(4);
    });
});
