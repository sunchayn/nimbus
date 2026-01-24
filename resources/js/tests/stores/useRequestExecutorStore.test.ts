import type { PendingRequest } from '@/interfaces';
import { RequestBodyTypeEnum } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { useRequestExecutorStore } from '@/stores/request/useRequestExecutorStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

/*
 * Fixtures.
 */

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
    schema: { shape: {}, extractionErrors: null },
    queryParameters: [],
    authorization: { type: AuthorizationType.None },
    supportedRoutes: [],
    routeDefinition: {
        method: 'GET',
        endpoint: 'users',
        shortEndpoint: 'users',
        schema: { shape: {}, extractionErrors: null },
    },
    isProcessing: false,
    wasExecuted: false,
    durationInMs: 0,
};

describe('useRequestExecutorStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Initialization tests.
     */

    describe('Validation', () => {
        it('prevents execution when request invalid', async () => {
            // Arrange

            const store = useRequestExecutorStore();

            // Assert

            expect(store.canExecute(null)).toBe(false);
            expect(
                store.canExecute({ ...request, endpoint: '   ' } as PendingRequest),
            ).toBe(false);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Execution', () => {
        it('logs successful executions with generated log entry', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            executeRequest.mockResolvedValue({
                duration: 2000,
                response: { status: 200 },
            });

            // Act

            await store.executeRequestWithTiming({ ...request });

            // Assert

            expect(requestUtilsMocks.createRequestTimer).toHaveBeenCalled();
            expect(mockRequestsHistoryStore.addLog).toHaveBeenCalledWith({
                type: 'success',
            });
        });

        it('logs errors using error log factory', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            executeRequest.mockRejectedValue({ message: 'boom' });

            // Act

            await store.executeRequestWithTiming({ ...request });

            // Assert

            expect(requestUtilsMocks.generateErrorRequestLog).toHaveBeenCalled();
            expect(mockRequestsHistoryStore.addLog).toHaveBeenCalledWith({
                type: 'error',
            });
        });

        it('cancels current request via http client', () => {
            // Arrange

            const store = useRequestExecutorStore();

            // Act

            store.cancelCurrentRequest();

            // Assert

            expect(cancelCurrentRequest).toHaveBeenCalled();
        });
    });
});
