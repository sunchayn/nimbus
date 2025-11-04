import { AuthorizationContract, RouteDefinition } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
import { useRequestStore } from '@/stores/request/useRequestStore';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

// Mock the child stores
const mockBuilderStore: {
    [key: string]: any; // eslint-disable-line @typescript-eslint/no-explicit-any
} = reactive({
    hasActiveRequest: false,
    pendingRequestData: null,
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
    let store: ReturnType<typeof useRequestStore>;

    beforeEach(() => {
        store = useRequestStore();
        // Reset builder/executor mocks and state between tests
        mockBuilderStore.pendingRequestData = null;
        mockBuilderStore.initializeRequest.mockReset();
        mockBuilderStore.updateRequestMethod.mockReset();
        mockBuilderStore.updateRequestEndpoint.mockReset();
        mockBuilderStore.updateRequestHeaders.mockReset();
        mockBuilderStore.updateRequestBody.mockReset();
        mockBuilderStore.updateQueryParameters.mockReset();
        mockBuilderStore.updateAuthorization.mockReset();
        mockBuilderStore.getRequestUrl.mockReset();
        mockExecutorStore.cancelCurrentRequest.mockReset();
        mockExecutorStore.executeRequestWithTiming.mockReset();
    });

    describe('initial state', () => {
        it('should initialize with correct default state', () => {
            expect(store.hasActiveRequest).toBe(false);
            expect(store.pendingRequestData).toBeNull();
            expect(store.canExecute).toBe(true); // The dummy mock has `vi.fn(() => true)`
            expect(store.isProcessing).toBe(false);
        });
    });

    describe('computed properties', () => {
        it('should delegate hasActiveRequest to builder store', () => {
            mockBuilderStore.hasActiveRequest = true;
            expect(store.hasActiveRequest).toBe(true);

            mockBuilderStore.hasActiveRequest = false;
            expect(store.hasActiveRequest).toBe(false);
        });

        it('should delegate pendingRequestData to builder store', () => {
            const mockRequestData = {
                method: 'GET',
                endpoint: '/api/users',
                headers: [],
                body: {},
                payloadType: RequestBodyTypeEnum.EMPTY,
                schema: { shape: {}, extractionErrors: null },
                queryParameters: [],
                authorization: { type: AuthorizationType.CurrentUser },
                supportedRoutes: [],
                routeDefinition: null,
                isProcessing: false,
                durationInMs: 0,
            };

            mockBuilderStore.pendingRequestData = mockRequestData;

            expect(store.pendingRequestData).toStrictEqual(mockRequestData);
        });

        it('should compute canExecute based on executor store', () => {
            mockExecutorStore.canExecute = vi.fn(() => true);
            expect(store.canExecute).toBe(true);

            mockExecutorStore.canExecute = vi.fn(() => false);
            expect(store.canExecute).toBe(false);

            expect(mockExecutorStore.canExecute).toHaveBeenCalledWith(
                mockBuilderStore.pendingRequestData,
            );
        });
    });

    describe('initializeRequest', () => {
        it('should initialize request and reset execution', () => {
            const mockRoute: RouteDefinition = {
                method: 'GET',
                endpoint: '/api/users',
                shortEndpoint: '/api/users',
                schema: {
                    shape: {
                        'x-name': 'root',
                        'x-required': false,
                    },
                    extractionErrors: null,
                },
            };

            const mockSupportedRoutes = [mockRoute];

            store.initializeRequest(mockRoute, mockSupportedRoutes);

            expect(mockBuilderStore.initializeRequest).toHaveBeenCalledWith(
                mockRoute,
                mockSupportedRoutes,
            );
        });

        it('should no-op when route method and endpoint are unchanged', () => {
            const sameRoute: RouteDefinition = {
                method: 'GET',
                endpoint: '/api/users',
                shortEndpoint: '/api/users',
                schema: {
                    shape: {
                        'x-name': 'root',
                        'x-required': false,
                    },
                    extractionErrors: null,
                },
            };

            mockBuilderStore.pendingRequestData = {
                method: 'GET',
                endpoint: '/api/users',
            };

            const supported = [sameRoute];

            store.initializeRequest(sameRoute, supported);

            expect(mockExecutorStore.cancelCurrentRequest).not.toHaveBeenCalled();
            expect(mockBuilderStore.initializeRequest).not.toHaveBeenCalled();
        });

        it('should reinitialize when method changes but endpoint stays the same', () => {
            const route: RouteDefinition = {
                method: 'POST',
                endpoint: '/api/users',
                shortEndpoint: '/api/users',
                schema: {
                    shape: {
                        'x-name': 'root',
                        'x-required': false,
                    },
                    extractionErrors: null,
                },
            };

            mockBuilderStore.pendingRequestData = {
                method: 'GET',
                endpoint: '/api/users',
            };

            const supported = [route];

            store.initializeRequest(route, supported);

            expect(mockExecutorStore.cancelCurrentRequest).toHaveBeenCalled();
            expect(mockBuilderStore.initializeRequest).toHaveBeenCalledWith(
                route,
                supported,
            );
        });

        it('should reinitialize when endpoint changes but method stays the same', () => {
            const route: RouteDefinition = {
                method: 'GET',
                endpoint: '/api/accounts',
                shortEndpoint: '/api/accounts',
                schema: {
                    shape: {
                        'x-name': 'root',
                        'x-required': false,
                    },
                    extractionErrors: null,
                },
            };

            mockBuilderStore.pendingRequestData = {
                method: 'GET',
                endpoint: '/api/users',
            };

            const supported = [route];

            store.initializeRequest(route, supported);

            expect(mockExecutorStore.cancelCurrentRequest).toHaveBeenCalled();
            expect(mockBuilderStore.initializeRequest).toHaveBeenCalledWith(
                route,
                supported,
            );
        });
    });

    describe('request building actions', () => {
        it('should delegate updateRequestMethod to builder store', () => {
            store.updateRequestMethod('POST');
            expect(mockBuilderStore.updateRequestMethod).toHaveBeenCalledWith('POST');
        });

        it('should delegate updateRequestEndpoint to builder store', () => {
            store.updateRequestEndpoint('/api/posts');
            expect(mockBuilderStore.updateRequestEndpoint).toHaveBeenCalledWith(
                '/api/posts',
            );
        });

        it('should delegate updateRequestHeaders to builder store', () => {
            const headers = [
                {
                    key: 'Content-Type',
                    value: 'application/json',
                    enabled: true,
                },
            ];
            store.updateRequestHeaders(headers);
            expect(mockBuilderStore.updateRequestHeaders).toHaveBeenCalledWith(headers);
        });

        it('should delegate updateRequestBody to builder store', () => {
            const body: PendingRequest['body'] = {
                POST: { json: JSON.stringify({ name: 'test' }) },
            };

            store.updateRequestBody(body);

            expect(mockBuilderStore.updateRequestBody).toHaveBeenCalledWith(body);
        });

        it('should delegate updateQueryParameters to builder store', () => {
            const params = [{ key: 'page', value: '1' }];
            store.updateQueryParameters(params);
            expect(mockBuilderStore.updateQueryParameters).toHaveBeenCalledWith(params);
        });

        it('should delegate updateAuthorization to builder store', () => {
            const auth: AuthorizationContract = {
                type: AuthorizationType.Bearer,
                value: 'abc123',
            };
            store.updateAuthorization(auth);
            expect(mockBuilderStore.updateAuthorization).toHaveBeenCalledWith(auth);
        });
    });

    describe('request execution actions', () => {
        it('should execute current request when pendingRequestData exists', () => {
            const mockRequestData = {
                method: 'GET',
                endpoint: '/api/users',
                headers: [],
                body: {},
                payloadType: RequestBodyTypeEnum.EMPTY,
                schema: { shape: {}, extractionErrors: null },
                queryParameters: [],
                authorization: { type: AuthorizationType.CurrentUser },
                supportedRoutes: [],
                routeDefinition: null,
                isProcessing: false,
                durationInMs: 0,
            };

            mockBuilderStore.pendingRequestData = mockRequestData;

            store.executeCurrentRequest();

            expect(mockExecutorStore.executeRequestWithTiming).toHaveBeenCalledWith(
                mockRequestData,
            );
        });

        it('should not execute when pendingRequestData is null', () => {
            mockBuilderStore.pendingRequestData = null;

            const result = store.executeCurrentRequest();

            expect(result).toBeUndefined();
            expect(mockExecutorStore.executeRequestWithTiming).not.toHaveBeenCalled();
        });

        it('should delegate cancelCurrentRequest to executor store', () => {
            store.cancelCurrentRequest();
            expect(mockExecutorStore.cancelCurrentRequest).toHaveBeenCalled();
        });
    });

    describe('helper functions', () => {
        it('should delegate getRequestUrl to executor store', () => {
            const mockUrl = 'https://api.example.com/users';

            mockBuilderStore.getRequestUrl.mockReturnValue(mockUrl);

            // Dummy object as parameter as we are mocking the function result.
            const result = store.getRequestUrl({} as PendingRequest);

            expect(result).toBe(mockUrl);
            expect(mockBuilderStore.getRequestUrl).toHaveBeenCalled();
        });
    });

    describe('state delegation', () => {
        it('should delegate isProcessing to executor store', () => {
            mockExecutorStore.isProcessing = true;
            expect(store.isProcessing).toBe(true);

            mockExecutorStore.isProcessing = false;
            expect(store.isProcessing).toBe(false);
        });
    });

    describe('integration scenarios', () => {
        it('should handle complete request lifecycle', () => {
            const mockRoute: RouteDefinition = {
                method: 'POST',
                endpoint: '/api/users',
                shortEndpoint: '/api/users',
                schema: {
                    shape: {
                        'x-name': 'root',
                        'x-required': false,
                    },
                    extractionErrors: null,
                },
            };
            const mockSupportedRoutes = [mockRoute];

            // Initialize request
            store.initializeRequest(mockRoute, mockSupportedRoutes);
            expect(mockBuilderStore.initializeRequest).toHaveBeenCalled();

            // Update request data
            store.updateRequestMethod('PUT');
            store.updateRequestEndpoint('/api/users/1');
            expect(mockBuilderStore.updateRequestMethod).toHaveBeenCalledWith('PUT');
            expect(mockBuilderStore.updateRequestEndpoint).toHaveBeenCalledWith(
                '/api/users/1',
            );

            // Execute request
            mockBuilderStore.pendingRequestData = {
                method: 'PUT',
                endpoint: '/api/users/1',
            };

            store.executeCurrentRequest();

            expect(mockExecutorStore.executeRequestWithTiming).toHaveBeenCalled();
        });

        it('should handle request cancellation', () => {
            // Start a request
            mockBuilderStore.pendingRequestData = { method: 'GET' };
            store.executeCurrentRequest();

            // Cancel the request
            store.cancelCurrentRequest();
            expect(mockExecutorStore.cancelCurrentRequest).toHaveBeenCalled();
        });

        it('should handle multiple request updates', () => {
            const headers = [{ key: 'Authorization', value: 'Bearer token' }];
            const body: PendingRequest['body'] = {
                POST: { json: JSON.stringify({ name: 'test' }) },
            };
            const params = [{ key: 'page', value: '1' }];
            const auth: AuthorizationContract = {
                type: AuthorizationType.Bearer,
                value: 'abc123',
            };

            store.updateRequestHeaders(headers);
            store.updateRequestBody(body);
            store.updateQueryParameters(params);
            store.updateAuthorization(auth);

            expect(mockBuilderStore.updateRequestHeaders).toHaveBeenCalledWith(headers);
            expect(mockBuilderStore.updateRequestBody).toHaveBeenCalledWith(body);
            expect(mockBuilderStore.updateQueryParameters).toHaveBeenCalledWith(params);
            expect(mockBuilderStore.updateAuthorization).toHaveBeenCalledWith(auth);
        });
    });
});
