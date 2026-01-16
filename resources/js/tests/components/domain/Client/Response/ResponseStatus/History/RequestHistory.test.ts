import RequestHistory from '@/components/domain/Client/Response/ResponseStatus/History/RequestHistory.vue';
import { AuthorizationType } from '@/interfaces/generated';
import { RequestLog } from '@/interfaces/history/logs';
import { Request, RequestBodyTypeEnum, STATUS } from '@/interfaces/http';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { fireEvent } from '@testing-library/vue';
import { beforeEach, describe, expect, it, Mock, vi } from 'vitest';
import { nextTick, Reactive, reactive } from 'vue';

const mockRequestStore: Reactive<{
    restoreFromHistory: Mock<(request: Request) => void>;
}> = reactive({
    restoreFromHistory: vi.fn(),
});

const mockRequestsHistoryStore: Reactive<{
    lastLog: RequestLog | null;
    allLogs: RequestLog[];
    setActiveLog: Mock<(index: number) => void>;
    clearLogs: Mock<() => void>;
}> = reactive({
    lastLog: null,
    allLogs: [],
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

describe('RequestHistory', () => {
    beforeEach(() => {
        mockRequestsHistoryStore.lastLog = null;
        mockRequestsHistoryStore.allLogs = [];
        mockRequestsHistoryStore.setActiveLog.mockClear();
        mockRequestsHistoryStore.clearLogs.mockClear();
        mockRequestStore.restoreFromHistory.mockClear();
        vi.useFakeTimers();
    });

    const createLog = (endpoint: string, timestamp: number): RequestLog => ({
        durationInMs: 100,
        isProcessing: false,
        request: {
            method: 'GET',
            endpoint,
            headers: [],
            queryParameters: [],
            body: null,
            payloadType: RequestBodyTypeEnum.EMPTY,
            authorization: { type: AuthorizationType.None },
            routeDefinition: {
                method: 'GET',
                endpoint,
                shortEndpoint: endpoint,
                schema: { shape: {}, extractionErrors: null },
            },
        },
        response: {
            status: STATUS.SUCCESS,
            statusCode: 200,
            statusText: 'OK',
            timestamp,
            body: '{}',
            headers: [],
            cookies: [],
            sizeInBytes: 10,
        },
    });

    it('renders nothing when history is empty', () => {
        renderWithProviders(RequestHistory);
        expect(screen.queryByTestId('response-history-trigger')).not.toBeInTheDocument();
    });

    it('renders history trigger when logs exist', async () => {
        const log = createLog('/test', 1000);
        mockRequestsHistoryStore.allLogs = [log];
        mockRequestsHistoryStore.lastLog = log;

        renderWithProviders(RequestHistory);
        await nextTick();

        expect(screen.getByTestId('response-history-trigger')).toBeInTheDocument();
    });

    it('filters logs based on search query', async () => {
        const log1 = createLog('/users', 1000);
        const log2 = createLog('/posts', 2000);
        mockRequestsHistoryStore.allLogs = [log1, log2];
        mockRequestsHistoryStore.lastLog = log2;

        renderWithProviders(RequestHistory);
        await nextTick();

        const searchInput = screen.getByTestId('history-search-input');
        await fireEvent.update(searchInput, 'users');

        const items = screen.getAllByTestId('history-item');
        expect(items).toHaveLength(1);
        expect(items[0].textContent).toContain('History Item 0');
    });

    it('restores request when a history item is selected', async () => {
        const log = createLog('/test', 1000);
        mockRequestsHistoryStore.allLogs = [log];
        mockRequestsHistoryStore.lastLog = log;

        renderWithProviders(RequestHistory);
        await nextTick();

        const item = screen.getByTestId('history-item');
        await fireEvent.click(item);

        expect(mockRequestsHistoryStore.setActiveLog).toHaveBeenCalledWith(0);
        expect(mockRequestStore.restoreFromHistory).toHaveBeenCalledWith(log.request);
    });

    it('requires double click to clear history (confirmation logic)', async () => {
        const log = createLog('/test', 1000);
        mockRequestsHistoryStore.allLogs = [log];
        mockRequestsHistoryStore.lastLog = log;

        renderWithProviders(RequestHistory);
        await nextTick();

        const clearButton = screen.getByTestId('clear-history-button');

        // First click
        await fireEvent.click(clearButton);
        expect(mockRequestsHistoryStore.clearLogs).not.toHaveBeenCalled();
        expect(clearButton.className).toContain('text-rose-500');

        // Second click
        await fireEvent.click(clearButton);
        expect(mockRequestsHistoryStore.clearLogs).toHaveBeenCalled();
    });

    it('resets clear history confirmation after timeout', async () => {
        const log = createLog('/test', 1000);
        mockRequestsHistoryStore.allLogs = [log];
        mockRequestsHistoryStore.lastLog = log;

        renderWithProviders(RequestHistory);
        await nextTick();

        const clearButton = screen.getByTestId('clear-history-button');

        await fireEvent.click(clearButton);
        expect(clearButton.className).toContain('text-rose-500');

        vi.advanceTimersByTime(1100);
        await nextTick();

        expect(clearButton.className).not.toContain('text-rose-500');

        await fireEvent.click(clearButton); // Should still be first click after reset
        expect(mockRequestsHistoryStore.clearLogs).not.toHaveBeenCalled();
    });

    it('displays logs in reverse order and only if they have a response', async () => {
        const log1 = createLog('/test1', 1000);
        const log2 = createLog('/test2', 2000);
        delete log2.response;

        const log3 = createLog('/test3', 3000);

        mockRequestsHistoryStore.allLogs = [log1, log2, log3];
        mockRequestsHistoryStore.lastLog = log3;

        renderWithProviders(RequestHistory);
        await nextTick();

        const items = screen.getAllByTestId('history-item');
        expect(items).toHaveLength(2);

        // Reversed order: log3 (index 2) then log1 (index 0)
        expect(items[0].textContent).toContain('History Item 2');
        expect(items[1].textContent).toContain('History Item 0');
    });
});
