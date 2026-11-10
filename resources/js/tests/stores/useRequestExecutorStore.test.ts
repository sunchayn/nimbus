import type { PendingRequest } from '@/interfaces';
import { useRequestExecutorStore } from '@/stores/request/useRequestExecutorStore';
import { useTabsStore } from '@/stores/request/useTabsStore';
import { createMockPendingRequest } from '@/tests/_utils/testFactories';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

/*
 * Fixtures.
 */

const executeRequest = vi.fn();

const signRequestUrl = vi.fn();
const signedMiddleware = 'Illuminate\\Routing\\Middleware\\ValidateSignature';

vi.mock('@/utils/request/signRequestUrl', () => ({
    signRequestUrl: (...args: unknown[]) => signRequestUrl(...args),
}));

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

        it('sends the signed copy of the request when the sign option is on', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            const signed = { ...request, endpoint: 'signed?signature=abc' };
            signRequestUrl.mockResolvedValue(signed);
            executeRequest.mockResolvedValue({
                duration: 2000,
                response: { status: 200 },
            });

            // Act

            await store.executeRequestWithTiming({
                ...request,
                signUrl: true,
                routeDefinition: { metadata: { middleware: [signedMiddleware] } },
            } as unknown as PendingRequest);

            // Assert

            expect(signRequestUrl).toHaveBeenCalled();
            expect(executeRequest.mock.calls[0][0]).toBe(signed);
        });

        it('does not sign a route that is not signed even when the option is on', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            executeRequest.mockResolvedValue({
                duration: 2000,
                response: { status: 200 },
            });

            // Act

            await store.executeRequestWithTiming({
                ...request,
                signUrl: true,
                routeDefinition: { metadata: { middleware: [] } },
            } as unknown as PendingRequest);

            // Assert

            expect(signRequestUrl).not.toHaveBeenCalled();
        });

        it('does not sign when the sign option is off', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            executeRequest.mockResolvedValue({
                duration: 2000,
                response: { status: 200 },
            });

            // Act

            await store.executeRequestWithTiming({ ...request });

            // Assert

            expect(signRequestUrl).not.toHaveBeenCalled();
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

        it('aborts the in-flight request when the active tab changes', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            const tabsStore = useTabsStore();
            let signal: AbortSignal | undefined;

            tabsStore.activeTabId = 'tab-a';

            executeRequest.mockImplementation(
                (_request: PendingRequest, options: { signal: AbortSignal }) => {
                    signal = options.signal;

                    return Promise.resolve(null);
                },
            );

            // Act

            const execution = store.executeRequestWithTiming({ ...request });
            tabsStore.activeTabId = 'tab-b';
            await nextTick();
            await execution;

            // Assert

            expect(signal?.aborted).toBe(true);
        });

        it('discards a late response when its tab is no longer active', async () => {
            // Arrange

            const store = useRequestExecutorStore();
            const tabsStore = useTabsStore();
            let resolveRequest: (value: unknown) => void = () => undefined;

            tabsStore.activeTabId = 'tab-a';

            executeRequest.mockImplementation(
                () =>
                    new Promise(resolve => {
                        resolveRequest = resolve;
                    }),
            );

            // Act

            const execution = store.executeRequestWithTiming({ ...request });
            tabsStore.activeTabId = 'tab-b';
            resolveRequest({ duration: 2000, response: { status: 200 } });
            await execution;

            // Assert

            expect(mockRequestsHistoryStore.addLog).not.toHaveBeenCalled();
        });

        it('does nothing when canceling without a request in flight', () => {
            // Arrange

            const store = useRequestExecutorStore();

            // Act and Assert

            expect(() => store.cancelCurrentRequest()).not.toThrow();
        });
    });
});
