import ResponseStatus from '@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue';
import { STATUS } from '@/interfaces/http';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { fireEvent } from '@testing-library/vue';
import { beforeEach, describe, expect, it, MockedFunction, vi } from 'vitest';
import { nextTick, Reactive, reactive } from 'vue';

const mockRequestStore: Reactive<{
    pendingRequestData: object | null;
    cancelCurrentRequest: MockedFunction<any>; // eslint-disable-line @typescript-eslint/no-explicit-any
}> = reactive({
    pendingRequestData: null,
    cancelCurrentRequest: vi.fn(),
});

const mockRequestsHistoryStore: Reactive<{
    lastLog: object | null;
}> = reactive({
    lastLog: null,
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestStore: () => mockRequestStore,
        useRequestsHistoryStore: () => mockRequestsHistoryStore,
    };
});

describe('ResponseStatus', () => {
    beforeEach(() => {
        mockRequestStore.pendingRequestData = null;
        mockRequestsHistoryStore.lastLog = null;
        mockRequestStore.cancelCurrentRequest.mockClear();
    });

    it('shows pending status and cancel option while processing', async () => {
        mockRequestStore.pendingRequestData = { isProcessing: true, durationInMs: 1234 };

        renderWithProviders(ResponseStatus);

        expect(screen.queryByTestId('response-badge')).toBeNull();

        expect(screen.getByTestId('response-status-indicator')).toBeInTheDocument();

        expect(screen.getByRole('button', { name: /cancel/i })).toBeInTheDocument();
    });

    it('shows empty status when nothing processed yet', async () => {
        mockRequestStore.pendingRequestData = {
            isProcessing: false,
            durationInMs: 0,
            wasExecuted: false,
        };

        renderWithProviders(ResponseStatus);

        await nextTick();

        expect(screen.getByTestId('response-status-text')).toHaveTextContent(
            String(STATUS.EMPTY),
        );
    });

    it('derives status details from last successful log', async () => {
        mockRequestStore.pendingRequestData = {
            isProcessing: false,
            wasExecuted: true,
        };

        mockRequestsHistoryStore.lastLog = {
            durationInMs: 3000,
            response: {
                statusCode: 201,
                statusText: 'Created',
                sizeInBytes: 4096,
                timestamp: Math.floor(Date.now() / 1000),
            },
        };

        renderWithProviders(ResponseStatus);

        await nextTick();

        expect(screen.getByTestId('response-status-badge')).toHaveTextContent(
            '201 - Created',
        );

        expect(screen.getByTestId('response-status-size')).toHaveTextContent('4.1kB');

        expect(screen.getByTestId('response-status-duration')).toHaveTextContent('3.00s');
    });

    it('resets size to zero when request was not executed', async () => {
        mockRequestStore.pendingRequestData = {
            isProcessing: false,
            wasExecuted: false,
            durationInMs: 0,
        };

        mockRequestsHistoryStore.lastLog = {
            response: { sizeInBytes: 12345, timestamp: Math.floor(Date.now() / 1000) },
        };

        renderWithProviders(ResponseStatus);

        await nextTick();

        expect(screen.getByText(/0B/)).toBeInTheDocument();
    });

    it('shows relative timestamp when last log exists', async () => {
        mockRequestStore.pendingRequestData = {
            isProcessing: false,
            durationInMs: 0,
            wasExecuted: true,
        };

        mockRequestsHistoryStore.lastLog = {
            response: { timestamp: Math.floor(Date.now() / 1000) },
        };

        renderWithProviders(ResponseStatus);

        await nextTick();

        const timestamp = screen.getByText(
            (content, element) => element?.tagName === 'SMALL',
        );
        expect(timestamp.textContent?.length ?? 0).toBeGreaterThan(0);
    });

    it('cancels request when cancel button clicked', async () => {
        mockRequestStore.pendingRequestData = { isProcessing: true, durationInMs: 0 };

        renderWithProviders(ResponseStatus);

        await nextTick();

        await fireEvent.click(screen.getByRole('button', { name: /cancel/i }));

        expect(mockRequestStore.cancelCurrentRequest).toHaveBeenCalled();
    });
});
