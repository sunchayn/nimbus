import type { AuthorizationContract, PendingRequest } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { useRequestStore } from '@/stores/request/useRequestStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

/*
 * Fixtures.
 */

const mockBuilderStore = reactive({
    hasActiveRequest: false,
    pendingRequestData: null as PendingRequest | null,
    initializeRequest: vi.fn(),
    resetRequest: vi.fn(),
    updateRequestMethod: vi.fn(),
    updateRequestEndpoint: vi.fn(),
    updateRequestHeaders: vi.fn(),
    updateRequestBody: vi.fn(),
    updateQueryParameters: vi.fn(),
    updateAuthorization: vi.fn(),
    getRequestUrl: vi.fn(),
});

const mockExecutorStore = reactive({
    isProcessing: false,
    duration: 0,
    canExecute: vi.fn(() => true),
    executeRequestWithTiming: vi.fn(),
    cancelCurrentRequest: vi.fn(),
});

vi.mock('@/stores/request/useRequestBuilderStore', () => ({
    useRequestBuilderStore: () => mockBuilderStore,
}));

vi.mock('@/stores/request/useRequestExecutorStore', () => ({
    useRequestExecutorStore: () => mockExecutorStore,
}));

describe('useRequestStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        // Reset builder/executor mocks and state between tests
        mockBuilderStore.pendingRequestData = null;
        mockBuilderStore.hasActiveRequest = false;
        mockExecutorStore.isProcessing = false;
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('should initialize with correct default state', () => {
            // Act

            const store = useRequestStore();

            // Assert

            expect(store.hasActiveRequest).toBe(false);
            expect(store.pendingRequestData).toBeNull();
            expect(store.canExecute).toBe(true);
            expect(store.isProcessing).toBe(false);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Request Building', () => {
        it('should delegate updateRequestMethod to builder store', () => {
            // Arrange

            const store = useRequestStore();

            // Act

            store.updateRequestMethod('POST');

            // Assert

            expect(mockBuilderStore.updateRequestMethod).toHaveBeenCalledWith('POST');
        });

        it('should delegate updateRequestEndpoint to builder store', () => {
            // Arrange

            const store = useRequestStore();

            // Act

            store.updateRequestEndpoint('/api/posts');

            // Assert

            expect(mockBuilderStore.updateRequestEndpoint).toHaveBeenCalledWith(
                '/api/posts',
            );
        });

        it('should delegate updateAuthorization to builder store', () => {
            // Arrange

            const store = useRequestStore();
            const auth: AuthorizationContract = {
                type: AuthorizationType.Bearer,
                value: 'abc123',
            };

            // Act

            store.updateAuthorization(auth);

            // Assert

            expect(mockBuilderStore.updateAuthorization).toHaveBeenCalledWith(auth);
        });
    });

    describe('Request Execution', () => {
        it('should execute current request when pendingRequestData exists', () => {
            // Arrange

            const mockRequestData = {
                method: 'GET',
                endpoint: 'api/users',
            } as unknown as PendingRequest;
            mockBuilderStore.pendingRequestData = mockRequestData;
            const store = useRequestStore();

            // Act

            store.executeCurrentRequest();

            // Assert

            expect(mockExecutorStore.executeRequestWithTiming).toHaveBeenCalledWith(
                mockRequestData,
            );
        });

        it('should delegate cancelCurrentRequest to executor store', () => {
            // Arrange

            const store = useRequestStore();

            // Act

            store.cancelCurrentRequest();

            // Assert

            expect(mockExecutorStore.cancelCurrentRequest).toHaveBeenCalled();
        });
    });
});
