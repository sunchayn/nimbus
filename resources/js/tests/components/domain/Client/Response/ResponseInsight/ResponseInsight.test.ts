import { flushPromises } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import {
    activeTab,
    makeException,
    makeHttp,
    makeInsight,
    makeQuery,
    openTab,
    render,
    resetOffsets,
    stubScrolling,
    tabLabels,
} from './helpers';

describe('ResponseInsight', () => {
    beforeEach(() => {
        resetOffsets();
        stubScrolling();
    });

    describe('tabs', () => {
        it('shows each kind of data in its own tab with a count', () => {
            const wrapper = render(
                makeInsight({
                    logs: [{ level: 'info', message: 'hi', context: {} }],
                    queries: [makeQuery('select 1'), makeQuery('select 2')],
                }),
            );

            expect(tabLabels(wrapper)).toEqual([
                'Timeline',
                'Queries2',
                'Logs1',
                'Gates0',
            ]);
        });

        it('shows a tab for each kind that has entries and hides the others', () => {
            const wrapper = render(
                makeInsight({
                    cache: [{ operation: 'hit', key: 'users' }],
                    jobs: [{ job: 'App\\Jobs\\Ship', queue: null, connection: null }],
                    events: [{ name: 'App\\Events\\Shipped' }],
                }),
            );

            expect(tabLabels(wrapper)).toEqual([
                'Timeline',
                'Queries0',
                'Logs0',
                'Gates0',
                'Cache1',
                'Jobs1',
                'Events1',
            ]);
        });

        it('adds a sandbox tab counting what was captured', () => {
            const wrapper = render(
                makeInsight({
                    sandbox: {
                        mail: [{ subject: 'Hi', to: ['a@b.test'], body: 'x' }],
                        jobs: [{ job: 'App\\Jobs\\Ship', queue: 'default' }],
                    },
                }),
            );

            expect(tabLabels(wrapper).at(-1)).toBe('Sandbox2');
        });

        it('keeps the sandbox tab when an effect was sandboxed and nothing was captured', () => {
            const wrapper = render(makeInsight({ sandbox: { mail: [] } }));

            expect(tabLabels(wrapper).at(-1)).toBe('Sandbox0');
        });

        it('has no sandbox tab when nothing was sandboxed', () => {
            expect(tabLabels(render(makeInsight())).join(' ')).not.toContain('Sandbox');
        });

        it.each([
            ['cache', { cache: [{ operation: 'hit' as const, key: 'k' }] }, 'Cache'],
            ['jobs', { jobs: [{ job: 'J', queue: null, connection: null }] }, 'Jobs'],
            ['mail', { mail: [{ subject: 'S', to: [], mailable: null }] }, 'Mail'],
            [
                'notifications',
                { notifications: [{ notification: 'N', channel: 'mail' }] },
                'Notifications',
            ],
            ['http', { http: [makeHttp()] }, 'HTTP'],
            ['events', { events: [{ name: 'E' }] }, 'Events'],
        ])('adds the %s tab only when there are entries', (_, overrides, label) => {
            expect(tabLabels(render(makeInsight()))).not.toContain(`${label}0`);
            expect(tabLabels(render(makeInsight(overrides)))).toContain(`${label}1`);
        });

        it('keeps the tab of a kind whose entries were all dropped', () => {
            const wrapper = render(
                makeInsight({ dropped: { ...makeInsight().dropped, mail: 4 } }),
            );

            expect(tabLabels(wrapper)).toContain('Mail0');
        });

        it('shows the timeline first', () => {
            expect(activeTab(render(makeInsight()))).toContain('Timeline');
        });

        it('opens the tab that is clicked', async () => {
            const wrapper = render(makeInsight({ http: [makeHttp()] }));

            await openTab(wrapper, 'HTTP');

            expect(activeTab(wrapper)).toContain('HTTP');
        });
    });

    describe('counts', () => {
        it('shows the count of a tab as a plain number', () => {
            const wrapper = render(makeInsight({ queries: [makeQuery('select 1')] }));

            const counts = wrapper.findAll('[data-testid="insight-tab-count"]');

            expect(counts.map(count => count.text())).toEqual(['1', '0', '0']);
        });

        it('marks the tabs without items as empty', () => {
            const wrapper = render(makeInsight({ queries: [makeQuery('select 1')] }));

            const counts = wrapper.findAll('[data-testid="insight-tab-count"]');

            expect(counts.map(count => count.attributes('data-empty'))).toEqual([
                undefined,
                'true',
                'true',
            ]);
        });

        it('shows no count on the timeline tab', () => {
            const wrapper = render(makeInsight());

            expect(
                wrapper
                    .findAll('[role="tab"]')[0]
                    .find('[data-testid="insight-tab-count"]')
                    .exists(),
            ).toBe(false);
        });
    });

    describe('exception tab', () => {
        it('is added only when an exception was thrown', () => {
            expect(tabLabels(render(makeInsight()))).not.toContain('Exception');
            expect(
                tabLabels(render(makeInsight({ exception: makeException() }))),
            ).toContain('Exception');
        });

        it('comes first and is the one that opens', () => {
            const wrapper = render(makeInsight({ exception: makeException() }));

            expect(tabLabels(wrapper)[0]).toBe('Exception');
            expect(activeTab(wrapper)).toBe('Exception');
        });

        it('shows the exception', () => {
            const wrapper = render(
                makeInsight({ exception: makeException({ message: 'Boom' }) }),
            );

            expect(wrapper.find('[data-testid="insight-exception-message"]').text()).toBe(
                'Boom',
            );
        });

        it('is a plain label, with no count and no colored dot', () => {
            const wrapper = render(makeInsight({ exception: makeException() }));

            const tab = wrapper.findAll('[role="tab"]')[0];

            expect(tab.find('[data-testid="insight-tab-count"]').exists()).toBe(false);
            expect(tab.find('.bg-error').exists()).toBe(false);
        });
    });

    describe('when the insight changes', () => {
        it('selects the first tab when the active tab is gone', async () => {
            const wrapper = render(makeInsight({ http: [makeHttp()] }));

            await openTab(wrapper, 'HTTP');

            expect(activeTab(wrapper)).toContain('HTTP');

            await wrapper.setProps({ insight: makeInsight() });
            await flushPromises();

            expect(activeTab(wrapper)).toContain('Timeline');
        });

        it('selects the first tab when the exception tab disappears', async () => {
            const wrapper = render(makeInsight({ exception: makeException() }));

            expect(activeTab(wrapper)).toContain('Exception');

            await wrapper.setProps({ insight: makeInsight() });
            await flushPromises();

            expect(activeTab(wrapper)).toContain('Timeline');
        });

        it('keeps the active tab while it is still there', async () => {
            const wrapper = render(makeInsight({ http: [makeHttp()] }));

            await openTab(wrapper, 'HTTP');
            await wrapper.setProps({
                insight: makeInsight({ http: [makeHttp(), makeHttp()] }),
            });
            await flushPromises();

            expect(activeTab(wrapper)).toContain('HTTP');
        });
    });

    describe('dropped items', () => {
        it('shows how many were dropped in the tab of their kind', () => {
            const wrapper = render(
                makeInsight({ dropped: { ...makeInsight().dropped, queries: 12 } }),
            );

            expect(
                wrapper
                    .find(
                        '[data-testid="insight-queries"] [data-testid="insight-dropped"]',
                    )
                    .text(),
            ).toContain('12');
        });
    });
});
