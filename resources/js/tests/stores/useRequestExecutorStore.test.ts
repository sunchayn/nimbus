import { PendingRequest, RequestBodyTypeEnum } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { useRequestExecutorStore } from '@/stores/request/useRequestExecutorStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

const executeRequest = vi.fn();
const cancelCurrentRequest = vi.fn();

const requestUtilsMocks = vi.hoisted(() => ({
    createRequestTimer: vi.fn(() => ({
        stop: vi.fn(() => 1500),
    })),
    generateSuccessRequestLog: vi.fn(() => ({ type: 'success' })),
    generateErrorRequestLog: vi.fn(() => ({ type: 'error' })),
}));

vi.mock('@/composables/request/useHttpClient', () => ({
    useHttpClient: () => ({
        executeRequest,
        cancelCurrentRequest,
    }),
}));

const mockRequestsHistoryStore = reactive({
    addLog: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useRequestsHistoryStore: () => mockRequestsHistoryStore,
    };
});

vi.mock('@/utils/request', () => requestUtilsMocks);

const request: PendingRequest = {
    method: 'GET',
    endpoint: 'users',
    headers: [],
    body: {},
    payloadType: RequestBodyTypeEnum.EMPTY,
    schema: {
        shape: {
            'x-name': 'root',
            'x-required': false,
        },
        extractionErrors: null,
    },
    queryParameters: [],
    authorization: { type: AuthorizationType.None },
    supportedRoutes: [],
    routeDefinition: {
        method: 'GET',
        endpoint: 'users',
        shortEndpoint: 'users',
        schema: {
            shape: {
                'x-name': 'root',
                'x-required': false,
            },
            extractionErrors: null,
        },
    },
    isProcessing: false,
    wasExecuted: false,
    durationInMs: 0,
};

describe('useRequestExecutorStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        mockRequestsHistoryStore.addLog.mockClear();
        executeRequest.mockReset();
        cancelCurrentRequest.mockClear();
        requestUtilsMocks.createRequestTimer.mockClear();
        requestUtilsMocks.generateSuccessRequestLog.mockClear();
        requestUtilsMocks.generateErrorRequestLog.mockClear();
    });

    it('prevents execution when request invalid', async () => {
        const store = useRequestExecutorStore();

        expect(store.canExecute(null)).toBe(false);

        const invalid = { ...request, endpoint: '   ' };

        expect(store.canExecute(invalid as PendingRequest)).toBe(false);
    });

    it('logs successful executions with generated log entry', async () => {
        const store = useRequestExecutorStore();

        executeRequest.mockResolvedValue({
            duration: 2000,
            response: { status: 200 },
        });

        await store.executeRequestWithTiming({ ...request });

        expect(requestUtilsMocks.createRequestTimer).toHaveBeenCalled();
        expect(requestUtilsMocks.generateSuccessRequestLog).toHaveBeenCalledWith(
            expect.objectContaining({ method: 'GET' }),
            2000,
            { status: 200 },
        );
        expect(mockRequestsHistoryStore.addLog).toHaveBeenCalledWith({ type: 'success' });
    });

    it('skips logging when request is cancelled', async () => {
        const store = useRequestExecutorStore();

        executeRequest.mockResolvedValue(null);

        await store.executeRequestWithTiming({ ...request });

        expect(mockRequestsHistoryStore.addLog).not.toHaveBeenCalled();
    });

    it('logs errors using error log factory', async () => {
        const store = useRequestExecutorStore();

        executeRequest.mockRejectedValue({ message: 'boom' });

        await store.executeRequestWithTiming({ ...request });

        expect(requestUtilsMocks.generateErrorRequestLog).toHaveBeenCalled();

        expect(mockRequestsHistoryStore.addLog).toHaveBeenCalledWith({ type: 'error' });
    });

    it('cancels current request via http client', () => {
        const store = useRequestExecutorStore();

        store.cancelCurrentRequest();

        expect(cancelCurrentRequest).toHaveBeenCalled();
    });
});
