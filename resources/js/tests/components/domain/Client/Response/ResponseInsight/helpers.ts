import ResponseInsight from '@/components/domain/Client/Response/ResponseInsight/ResponseInsight.vue';
import type {
    Insight,
    InsightExceptionInfo,
    InsightHttpEntry,
    InsightQuery,
} from '@/interfaces/http';
import { buildTimeline, type Timeline } from '@/utils/insight';
import { flushPromises, mount, type VueWrapper } from '@vue/test-utils';
import { vi, type Mock } from 'vitest';

export const makeInsight = (overrides: Partial<Insight> = {}): Insight => ({
    queries: [],
    logs: [],
    gates: [],
    cache: [],
    jobs: [],
    mail: [],
    notifications: [],
    http: [],
    events: [],
    exception: null,
    peak_memory: 0,
    duration_ms: 50,
    dropped: {
        queries: 0,
        logs: 0,
        cache: 0,
        jobs: 0,
        mail: 0,
        notifications: 0,
        http: 0,
        events: 0,
    },
    truncated: false,
    sandbox: {},
    ...overrides,
});

let nextOffset = 0;

/** Gives the queries made after this call start offsets from zero again. */
export const resetOffsets = (): void => {
    nextOffset = 0;
};

export const makeQuery = (
    sql: string,
    time_ms = 1,
    bindings: unknown[] = [],
): InsightQuery => ({
    sql,
    bindings,
    time_ms,
    connection: 'sqlite',
    offset_ms: nextOffset++,
});

export const makeHttp = (
    overrides: Partial<InsightHttpEntry> = {},
): InsightHttpEntry => ({
    method: 'GET',
    url: 'https://example.com/api',
    status: 200,
    duration_ms: 10,
    offset_ms: 5,
    ...overrides,
});

export const makeException = (
    overrides: Partial<InsightExceptionInfo> = {},
): InsightExceptionInfo => ({
    class: 'RuntimeException',
    message: 'Boom',
    file: '/app/Foo.php',
    line: 10,
    trace: [],
    ...overrides,
});

// Five identical queries make one N+1 group, and one 150 ms query makes a slow group.
export const mixedQueries = (): InsightQuery[] => [
    ...Array.from({ length: 5 }, (_, id) =>
        makeQuery('select * from users where id = ?', 1, [id]),
    ),
    makeQuery('select * from orders', 150),
    makeQuery('select * from posts', 1),
];

/** Mounts the whole panel, for behavior that crosses tabs. */
export const render = (insight: Insight): VueWrapper =>
    mount(ResponseInsight, {
        props: { insight },
        attachTo: document.body,
        global: {
            stubs: { AppScrollArea: { template: '<div><slot /></div>' } },
        },
    });

export const tabLabels = (wrapper: VueWrapper): string[] =>
    wrapper.findAll('[role="tab"]').map(tab => tab.text());

export const activeTab = (wrapper: VueWrapper): string =>
    wrapper.find('[role="tab"][data-state="active"]').text();

export const openTab = async (wrapper: VueWrapper, label: string): Promise<void> => {
    const tab = wrapper
        .findAll('[role="tab"]')
        .find(item => item.text().startsWith(label));

    await tab?.trigger('mousedown');
    await tab?.trigger('click');
    await flushPromises();
};

export const timelineOf = (insight: Insight): Timeline => {
    const timeline = buildTimeline(insight);

    if (!timeline) {
        throw new Error('The insight has no duration, so there is no timeline.');
    }

    return timeline;
};

/**
 * jsdom has no scrolling, and the panel scrolls the active tab and a clicked query into view.
 * Returns the mock of `scrollIntoView`, to tell whether something was scrolled to.
 */
export const stubScrolling = (): Mock => {
    const scrollIntoView = vi.fn();

    Element.prototype.scrollTo = vi.fn();
    Element.prototype.scrollIntoView = scrollIntoView;

    return scrollIntoView;
};
