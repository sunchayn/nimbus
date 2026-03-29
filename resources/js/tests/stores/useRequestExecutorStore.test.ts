import type { PendingRequest } from '@/interfaces';
import { useRequestExecutorStore } from '@/stores/request/useRequestExecutorStore';
import { createMockPendingRequest } from '@/tests/_utils/test-factories';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

/*
 * Fixtures.
 */

const executeRequest = vi.fn();
const cancelCurrentRequest = vi.fn();

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
