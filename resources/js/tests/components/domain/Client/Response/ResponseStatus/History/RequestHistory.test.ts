import RequestHistory from '@/components/domain/Client/Response/ResponseStatus/History/RequestHistory.vue';
import type { RequestLog } from '@/interfaces/history/logs';
import type { Request, Response } from '@/interfaces/http';
import { createMockRequestLog } from '@/tests/_utils/testFactories';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

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

vi.mock('@/components/base/dropdown-menu', () => ({
    AppDropdownMenu: {
        name: 'AppDropdownMenu',
        template: '<div><slot /></div>',
        props: ['open'],
    },
    AppDropdownMenuTrigger: {
        name: 'AppDropdownMenuTrigger',
        template: '<div><slot /></div>',
    },
    AppDropdownMenuContent: {
        name: 'AppDropdownMenuContent',
        template: '<div data-testid="dropdown-content"><slot /></div>',
    },
    AppDropdownMenuSeparator: {
        name: 'AppDropdownMenuSeparator',
        template: '<hr />',
    },
}));

vi.mock(
    '@/components/domain/Client/Response/ResponseStatus/History/HistoryItem.vue',
    () => ({
        default: {
            name: 'HistoryItem',
            template:
                '<div data-testid="history-item" @click="$emit(\'select\', index)">History Item {{ index }}</div>',
            props: ['log', 'index'],
            emits: ['select'],
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
const createWrapper = (options = {}): VueWrapper => {
    return mount(RequestHistory, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('RequestHistory', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        mockRequestsHistoryStore.lastLog = null;
        mockRequestsHistoryStore.allLogs = [];
        vi.useFakeTimers();
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

            const searchInput = wrapper.get('[data-testid="history-search-input"]');
            await searchInput.setValue('users');

            // Assert

            const items = wrapper.findAll('[data-testid="history-item"]');
            expect(items).toHaveLength(1);
            expect(items[0].text()).toContain('History Item 0');
        });

        it('restores request when a history item is selected', async () => {
            // Arrange

            const log = createLog('/test', 1000);
            mockRequestsHistoryStore.allLogs = [log];
            mockRequestsHistoryStore.lastLog = log;

            const wrapper = createWrapper();
            await nextTick();

            // Act

            const item = wrapper.get('[data-testid="history-item"]');
            await item.trigger('click');

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

            const clearButton = wrapper.get('[data-testid="clear-history-button"]');

            // Act - First click

            await clearButton.trigger('click');

            // Assert

            expect(mockRequestsHistoryStore.clearLogs).not.toHaveBeenCalled();
            expect(clearButton.classes()).toContain('text-rose-500');

            // Act - Second click

            await clearButton.trigger('click');

            // Assert

            expect(mockRequestsHistoryStore.clearLogs).toHaveBeenCalled();
        });
    });
});
