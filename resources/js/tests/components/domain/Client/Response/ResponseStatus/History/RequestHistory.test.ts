import RequestHistory from '@/components/domain/Client/Response/ResponseStatus/History/RequestHistory.vue';
import type { RequestLog } from '@/interfaces/history/logs';
import type { Request, Response } from '@/interfaces/http';
import { createMockRequestLog } from '@/tests/_utils/testFactories';
import type { VueWrapper } from '@vue/test-utils';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

const bodyItems = () => document.body.querySelectorAll('[data-testid="history-item"]');

/*
 * Fixtures.
 */

const mockRequestStore = reactive({
    restoreFromHistory: vi.fn(),
});

const mockRequestsHistoryStore = reactive({
    lastLog: null as RequestLog | null,
    allLogs: [] as RequestLog[],
    setActiveLog: vi.fn(),
    togglePin: vi.fn(),
    clearLogs: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
        useRequestsHistoryStore: () => mockRequestsHistoryStore,
    };
});

vi.mock(
    '@/components/domain/Client/Response/ResponseStatus/History/HistoryItem.vue',
    () => ({
        default: {
            name: 'HistoryItem',
            template: '<div>History Item {{ log.request.endpoint }}</div>',
            props: ['log'],
        },
    }),
);

const createLog = (endpoint: string, timestamp: number): RequestLog =>
    createMockRequestLog({
        request: {
            endpoint,
        } as Request,
        response: { timestamp } as unknown as Response,
    });

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
let mounted: VueWrapper | null = null;

const createWrapper = (options = {}): VueWrapper => {
    mounted = mount(RequestHistory, {
        attachTo: document.body,
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });

    return mounted;
};

describe('RequestHistory', () => {
    afterEach(() => {
        mounted?.unmount();
        mounted = null;
    });

    beforeEach(() => {
        setActivePinia(createPinia());
        mockRequestsHistoryStore.lastLog = null;
        mockRequestsHistoryStore.allLogs = [];
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders nothing when history is empty', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(
                wrapper.find('[data-testid="response-history-trigger"]').exists(),
            ).toBe(false);
        });

        it('renders history trigger when logs exist', async () => {
            // Arrange

            const log = createLog('/test', 1000);
            mockRequestsHistoryStore.allLogs = [log];
            mockRequestsHistoryStore.lastLog = log;

            const wrapper = createWrapper();

            // Act

            await nextTick();

            // Assert

            expect(
                wrapper.find('[data-testid="response-history-trigger"]').exists(),
            ).toBe(true);
        });
    });

    /*
     * State Transition tests.
     */

    const openMenu = async (wrapper: VueWrapper) => {
        await wrapper.get('[data-testid="response-history-trigger"]').trigger('click');
        await flushPromises();
    };

    describe('Pinning and status filters', () => {
        const logWithStatus = (endpoint: string, timestamp: number, statusCode: number) =>
            createMockRequestLog({
                request: { endpoint } as Request,
                response: { timestamp, statusCode } as unknown as Response,
            });

        it('filters by status class', async () => {
            // Arrange

            const ok = logWithStatus('/ok', 1000, 200);
            const failed = logWithStatus('/failed', 2000, 500);
            mockRequestsHistoryStore.allLogs = [ok, failed];
            mockRequestsHistoryStore.lastLog = failed;

            const wrapper = createWrapper();

            // Act

            await openMenu(wrapper);
            (
                document.body.querySelector(
                    '[data-testid="history-filter-5xx"]',
                ) as HTMLElement
            ).click();
            await nextTick();

            // Assert

            expect(bodyItems()).toHaveLength(1);
            expect(bodyItems()[0].textContent).toContain('/failed');
        });

        it('lists pinned logs first and toggles the pin', async () => {
            // Arrange

            const older = { ...logWithStatus('/older', 1000, 200), pinned: true };
            const newer = logWithStatus('/newer', 2000, 200);
            mockRequestsHistoryStore.allLogs = [older, newer];
            mockRequestsHistoryStore.lastLog = newer;

            const wrapper = createWrapper();

            // Act

            await openMenu(wrapper);

            // Assert

            expect(bodyItems()[0].textContent).toContain('/older');

            (
                document.body.querySelector(
                    '[data-testid="history-pin-toggle"]',
                ) as HTMLElement
            ).click();

            expect(mockRequestsHistoryStore.togglePin).toHaveBeenCalledWith(older);
        });
    });

    describe('Behavior', () => {
        it('filters logs based on search query', async () => {
            // Arrange

            const log1 = createLog('/users', 1000);
            const log2 = createLog('/posts', 2000);
            mockRequestsHistoryStore.allLogs = [log1, log2];
            mockRequestsHistoryStore.lastLog = log2;

            const wrapper = createWrapper();
            await nextTick();

            // Act

            await openMenu(wrapper);

            const searchInput = document.body.querySelector<HTMLInputElement>(
                '[data-testid="history-search-input"]',
            );
            searchInput!.value = 'users';
            searchInput!.dispatchEvent(new Event('input'));
            await flushPromises();

            // Assert

            expect(bodyItems()).toHaveLength(1);
            expect(bodyItems()[0].textContent).toContain('/users');
        });

        it('restores request when a history item is selected', async () => {
            // Arrange

            const log = createLog('/test', 1000);
            mockRequestsHistoryStore.allLogs = [log];
            mockRequestsHistoryStore.lastLog = log;

            const wrapper = createWrapper();
            await nextTick();

            // Act

            await openMenu(wrapper);
            (bodyItems()[0] as HTMLElement).click();
            await flushPromises();

            // Assert

            expect(mockRequestsHistoryStore.setActiveLog).toHaveBeenCalledWith(0);
            expect(mockRequestStore.restoreFromHistory).toHaveBeenCalledWith(log);
        });

        it('requires double click to clear history (confirmation logic)', async () => {
            // Arrange

            const log = createLog('/test', 1000);
            mockRequestsHistoryStore.allLogs = [log];
            mockRequestsHistoryStore.lastLog = log;

            const wrapper = createWrapper();
            await nextTick();

            await openMenu(wrapper);

            const clearButton = document.body.querySelector<HTMLElement>(
                '[data-testid="clear-history-button"]',
            );

            // Act - First click

            clearButton?.click();
            await flushPromises();

            // Assert

            expect(mockRequestsHistoryStore.clearLogs).not.toHaveBeenCalled();

            // Act - Second click

            clearButton?.click();
            await flushPromises();

            // Assert

            expect(mockRequestsHistoryStore.clearLogs).toHaveBeenCalled();
        });
    });
});
