import type { PendingRequest } from '@/interfaces';
import { useRequestExecutorStore } from '@/stores/request/useRequestExecutorStore';
import { createMockPendingRequest } from '@/tests/_utils/testFactories';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

/*
 * Fixtures.
 */

const executeRequest = vi.fn();

vi.mock('@/utils/request/sendRelayRequest', () => ({
    sendRelayRequest: (...args: unknown[]) => executeRequest(...args),
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

const requestUtilsMocks = vi.hoisted(() => ({
    createRequestTimer: vi.fn(() => ({
        stop: vi.fn(() => 1500),
    })),
    generateSuccessRequestLog: vi.fn(() => ({ type: 'success' })),
    generateErrorRequestLog: vi.fn(() => ({ type: 'error' })),
}));

vi.mock('@/utils/request', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        ...requestUtilsMocks,
    };
});

const request: PendingRequest = createMockPendingRequest();

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
                store.canExecute({
                    ...request,
                    endpoint: '   ',
                } as PendingRequest),
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

        it('aborts the in-flight request when canceled', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            let signal: AbortSignal | undefined;

            executeRequest.mockImplementation(
                (_request: PendingRequest, options: { signal: AbortSignal }) => {
                    signal = options.signal;

                    return Promise.resolve(null);
                },
            );

            // Act

            const execution = store.executeRequestWithTiming({ ...request });
            store.cancelCurrentRequest();
            await execution;

            // Assert

            expect(signal?.aborted).toBe(true);
        });

        it('does nothing when canceling without a request in flight', () => {
            // Arrange

            const store = useRequestExecutorStore();

            // Act and Assert

            expect(() => store.cancelCurrentRequest()).not.toThrow();
        });
    });
});
