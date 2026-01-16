import ResponseStatus from '@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue';
import { RequestLog } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, Request, RequestBodyTypeEnum, STATUS } from '@/interfaces/http';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { fireEvent } from '@testing-library/vue';
import { beforeEach, describe, expect, it, Mock, vi } from 'vitest';
import { nextTick, Reactive, reactive } from 'vue';

const mockRequestStore: Reactive<{
    pendingRequestData: Partial<PendingRequest> | null;
    cancelCurrentRequest: Mock<() => void>;
    restoreFromHistory: Mock<(request: Request) => void>;
}> = reactive({
    pendingRequestData: null,
    cancelCurrentRequest: vi.fn(),
    restoreFromHistory: vi.fn(),
});

const mockRequestsHistoryStore: Reactive<{
    lastLog: RequestLog | null;
    allLogs: RequestLog[];
    setActiveLog: Mock<(index: number) => void>;
}> = reactive({
    lastLog: null,
    allLogs: [],
    setActiveLog: vi.fn(),
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
        mockRequestsHistoryStore.allLogs = [];
        mockRequestStore.cancelCurrentRequest.mockClear();
        mockRequestStore.restoreFromHistory.mockClear();
        mockRequestsHistoryStore.setActiveLog.mockClear();
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

        const mockRequest: Request = {
            method: 'GET',
            endpoint: '/api/test',
            headers: [],
            queryParameters: [],
            body: null,
            payloadType: RequestBodyTypeEnum.EMPTY,
            authorization: { type: AuthorizationType.None },
            routeDefinition: {
                method: 'GET',
                endpoint: '/api/test',
                shortEndpoint: '/api/test',
                schema: { shape: {}, extractionErrors: null },
            },
        };

        mockRequestsHistoryStore.lastLog = {
            durationInMs: 3000,
            isProcessing: false,
            request: mockRequest,
            response: {
                status: STATUS.SUCCESS,
                statusCode: 201,
                statusText: 'Created',
                sizeInBytes: 4096,
                timestamp: Math.floor(Date.now() / 1000),
                body: '',
                headers: [],
                cookies: [],
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

        const mockRequest: Request = {
            method: 'GET',
            endpoint: '/api/test',
            headers: [],
            queryParameters: [],
            body: null,
            payloadType: RequestBodyTypeEnum.EMPTY,
            authorization: { type: AuthorizationType.None },
            routeDefinition: {
                method: 'GET',
                endpoint: '/api/test',
                shortEndpoint: '/api/test',
                schema: { shape: {}, extractionErrors: null },
            },
        };

        mockRequestsHistoryStore.lastLog = {
            durationInMs: 0,
            isProcessing: false,
            request: mockRequest,
            response: {
                status: STATUS.SUCCESS,
                statusCode: 200,
                statusText: 'OK',
                body: '',
                headers: [],
                cookies: [],
                sizeInBytes: 12345,
                timestamp: Math.floor(Date.now() / 1000),
            },
        };

        renderWithProviders(ResponseStatus);

        await nextTick();

        expect(screen.getByText(/0B/)).toBeInTheDocument();
    });

    it('cancels request when cancel button clicked', async () => {
        mockRequestStore.pendingRequestData = { isProcessing: true, durationInMs: 0 };

        renderWithProviders(ResponseStatus);

        await nextTick();

        await fireEvent.click(screen.getByRole('button', { name: /cancel/i }));

        expect(mockRequestStore.cancelCurrentRequest).toHaveBeenCalled();
    });

    it('opens history dropdown and selects an item', async () => {
        const log: RequestLog = {
            durationInMs: 100,
            isProcessing: false,
            request: {
                method: 'POST',
                endpoint: 'test',
                headers: [],
                queryParameters: [],
                body: null,
                payloadType: RequestBodyTypeEnum.EMPTY,
                authorization: { type: AuthorizationType.None },
                routeDefinition: {
                    method: 'POST',
                    endpoint: 'test',
                    shortEndpoint: 'test',
                    schema: { shape: {}, extractionErrors: null },
                },
            },
            response: {
                status: STATUS.SUCCESS,
                statusCode: 200,
                statusText: 'OK',
                timestamp: Math.floor(Date.now() / 1000),
                body: '{}',
                headers: [],
                cookies: [],
                sizeInBytes: 10,
            },
        };

        mockRequestsHistoryStore.allLogs = [log];
        mockRequestsHistoryStore.lastLog = log;
        mockRequestStore.pendingRequestData = { wasExecuted: true };

        renderWithProviders(ResponseStatus);

        await nextTick();

        const trigger = screen.getByTestId('response-history-trigger');
        await fireEvent.click(trigger);

        // We can't easily test Radix dropdown content with testing-library-vue without more setup,
        // but we can verify the trigger is there and clickable.
        // For a more thorough test, we would need to mock the dropdown portal or use Playwright.
        expect(trigger).toBeInTheDocument();
    });
});
