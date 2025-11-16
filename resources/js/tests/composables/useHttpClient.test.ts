import { useHttpClient } from '@/composables/request/useHttpClient';
import { PendingRequest, RequestBodyTypeEnum } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { RelayProxyResponse } from '@/interfaces/http';
import axios, { AxiosError } from 'axios';
import { describe, expect, it, Mocked, vi } from 'vitest';

// Mock axios
vi.mock('axios');
const mockedAxios = axios as Mocked<typeof axios>;

// Mock the config store
const mockConfigStore = {
    apiUrl: 'https://api.example.com',
    appBasePath: '/nimbus',
};

vi.mock('@/stores', () => ({
    useConfigStore: () => mockConfigStore,
}));

const defaultPendingRequest = {
    endpoint: 'api/users',
    method: 'POST' as const,
    queryParameters: [],
    authorization: {},
    headers: [],
    body: {},
    payloadType: RequestBodyTypeEnum.JSON,
    schema: {
        shape: {
            'x-required': false,
            'x-name': 'root',
        },
        extractionErrors: null,
    },
    supportedRoutes: [],
    routeDefinition: {
        endpoint: 'foobar',
        method: 'get',
        shortEndpoint: 'foobar',
        schema: {
            shape: {
                'x-required': false,
                'x-name': 'root',
            },
            extractionErrors: null,
        },
    },
};

describe('useHttpClient', () => {
    it('should initialize with correct default state', () => {
        const { isExecuting } = useHttpClient();

        expect(isExecuting.value).toBe(false);
    });

    it('should build request URL correctly', () => {
        const { buildRequestUrl } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            endpoint: 'api/users',
            authorization: {
                type: AuthorizationType.None,
            },
            queryParameters: [
                { key: 'page', value: '1' },
                { key: 'limit', value: '10' },
                { key: ' ', value: 'empty key' },
            ],
        };

        const url = buildRequestUrl(request);

        expect(url).toBe('https://api.example.com/api/users?page=1&limit=10');
    });

    it('should handle endpoint with leading slashes', () => {
        const { buildRequestUrl } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            endpoint: '//api/users',
            authorization: {
                type: AuthorizationType.None,
            },
        };

        const url = buildRequestUrl(request);

        expect(url).toBe('https://api.example.com/api/users');
    });

    it('should create relay payload correctly', async () => {
        const request: PendingRequest = {
            ...defaultPendingRequest,
            endpoint: 'api/users',
            authorization: { type: AuthorizationType.Bearer, value: 'abc123' },
            body: {
                POST: {
                    json: JSON.stringify({
                        name: 'John',
                        email: 'john@example.com',
                    }),
                },
            },
        };

        // We need to access the internal function, so we'll test through executeRequest
        const { executeRequest } = useHttpClient();

        // Mock axios response
        const mockRelayResponse: RelayProxyResponse = {
            statusCode: 200,
            statusText: 'OK',
            headers: [
                {
                    key: 'content-type',
                    value: 'application/json',
                },
            ],
            body: '{"success": true}',
            cookies: [],
            timestamp: 1234567890,
            duration: 120,
        };

        mockedAxios.post.mockResolvedValue({
            data: JSON.stringify(mockRelayResponse),
        });

        const result = await executeRequest(request);

        expect(result).not.toBeNull();

        expect(result?.response).toEqual({
            status: 'Success',
            statusCode: 200,
            statusText: 'OK',
            body: '{"success": true}',
            sizeInBytes: 17, // <- coming from body length
            headers: [
                {
                    key: 'content-type',
                    value: 'application/json',
                },
            ],
            cookies: [],
            timestamp: 1234567890,
        });

        expect(result?.duration).toEqual(120);

        expect(mockedAxios.post).toHaveBeenCalledWith(
            '/nimbus/api/relay',
            expect.any(FormData),
            expect.objectContaining({
                headers: { 'Content-Type': 'multipart/form-data' },
                signal: expect.any(AbortSignal),
            }),
        );
    });

    it('should create relay payload correctly with more data', async () => {
        const { executeRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            endpoint: 'api/users',
            authorization: { type: AuthorizationType.Bearer, value: 'abc123' },
        };

        const mockRelayResponse: RelayProxyResponse = {
            statusCode: 200,
            statusText: 'OK',
            headers: [
                {
                    key: 'content-type',
                    value: 'application/json',
                },
                {
                    key: 'content-length',
                    value: '20',
                },
            ],
            body: '{"users": []}',
            cookies: [
                {
                    key: 'session',
                    value: { raw: 'session=abc123', decrypted: 'abc123' },
                },
            ],
            timestamp: 1234567890,
            duration: 150,
        };

        mockedAxios.post.mockResolvedValue({
            data: JSON.stringify(mockRelayResponse),
        });

        const result = await executeRequest(request);

        expect(result).not.toBeNull();

        expect(result?.response).toEqual({
            status: 'Success',
            statusCode: 200,
            statusText: 'OK',
            body: '{"users": []}',
            sizeInBytes: 20, // <- coming from the header.
            headers: [
                {
                    key: 'content-type',
                    value: 'application/json',
                },
                {
                    key: 'content-length',
                    value: '20',
                },
            ],
            cookies: [
                {
                    key: 'session',
                    value: { raw: 'session=abc123', decrypted: 'abc123' },
                },
            ],
            timestamp: 1234567890,
        });

        expect(result?.duration).toEqual(150);

        expect(mockedAxios.post).toHaveBeenCalledWith(
            '/nimbus/api/relay',
            expect.any(FormData),
            expect.objectContaining({
                headers: { 'Content-Type': 'multipart/form-data' },
                signal: expect.any(AbortSignal),
            }),
        );
    });

    it('should handle request cancellation', async () => {
        const { executeRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        const cancelError = new AxiosError('Request cancelled');
        cancelError.code = 'ERR_CANCELED';

        mockedAxios.post.mockRejectedValue(cancelError);

        const result = await executeRequest(request);

        expect(result).toBeNull();
    });

    it('should handle HTTP error responses', async () => {
        const { executeRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        const httpError = {
            message: 'Request failed with status code 404',
            response: {
                status: 404,
                data: { error: 'Not Found' },
            },
        };

        mockedAxios.post.mockRejectedValue(httpError);

        await expect(executeRequest(request)).rejects.toEqual({
            message: 'Request failed with status code 404',
            status: 404,
            body: { error: 'Not Found' },
        });
    });

    it('should handle network errors', async () => {
        const { executeRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        const networkError = {
            message: 'Network Error',
        };

        mockedAxios.post.mockRejectedValue(networkError);

        await expect(executeRequest(request)).rejects.toEqual({
            message: 'Network Error',
        });
    });

    it('should prevent concurrent requests', async () => {
        const { executeRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        // Mock a slow response
        mockedAxios.post.mockImplementation(
            () =>
                new Promise(resolve =>
                    setTimeout(
                        () =>
                            resolve({
                                data: JSON.stringify({
                                    headers: [],
                                    cookies: [],
                                    body: 'foobar',
                                }),
                            }),
                        100,
                    ),
                ),
        );

        // Start first request
        const firstRequest = executeRequest(request);

        // Try to start second request immediately
        await expect(executeRequest(request)).rejects.toThrow(
            'Request already in progress',
        );

        // Wait for the first request to complete
        await firstRequest;
    });

    it('should handle request timeout', async () => {
        vi.mock('@/config', () => ({
            httpClientConfig: {
                TIMEOUT: 1000,
            },
        }));

        const { executeRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        // Mock a very slow response
        mockedAxios.post.mockImplementation(
            () =>
                new Promise(resolve =>
                    setTimeout(
                        () =>
                            resolve({
                                data: JSON.stringify({
                                    headers: [],
                                    cookies: [],
                                    body: 'foobar',
                                }),
                            }),
                        2000,
                    ),
                ),
        );

        await expect(executeRequest(request)).rejects.toThrow('Request timeout');
    });

    it('should cancel request correctly', () => {
        const { executeRequest, cancelCurrentRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        // Mock a slow response
        mockedAxios.post.mockImplementation(
            () => new Promise(resolve => setTimeout(() => resolve({ data: '{}' }), 1000)),
        );

        // Start request
        executeRequest(request);

        // Cancel request
        cancelCurrentRequest();

        // The abort controller should be called
        expect(mockedAxios.post).toHaveBeenCalledWith(
            expect.any(String),
            expect.any(FormData),
            expect.objectContaining({
                signal: expect.any(AbortSignal),
            }),
        );
    });

    it('should clean up state after request completion', async () => {
        const { executeRequest, isExecuting } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        mockedAxios.post.mockResolvedValue({
            data: JSON.stringify({
                statusCode: 200,
                statusText: 'OK',
                headers: [],
                body: '{}',
                cookies: [],
                timestamp: Date.now(),
                duration: 100,
            }),
        });

        await executeRequest(request);

        expect(isExecuting.value).toBe(false);
    });

    it('should clean up state after request failure', async () => {
        const { executeRequest, isExecuting } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            authorization: { type: AuthorizationType.None },
        };

        mockedAxios.post.mockRejectedValue(new Error('Network error'));

        try {
            await executeRequest(request);
        } catch (error) {
            // Expected to throw
        }

        expect(isExecuting.value).toBe(false);
    });

    it('extracts correct body payload based on request method', async () => {
        const { executeRequest } = useHttpClient();

        const request: PendingRequest = {
            ...defaultPendingRequest,
            method: 'POST',
            authorization: {
                type: AuthorizationType.None,
            },
            body: {
                POST: {
                    json: JSON.stringify({ name: 'John' }),
                },
                PUT: {
                    json: JSON.stringify({ name: 'Jane' }),
                },
            },
        };

        mockedAxios.post.mockResolvedValue({
            data: JSON.stringify({
                statusCode: 200,
                statusText: 'OK',
                headers: [],
                body: '{}',
                cookies: [],
                timestamp: Date.now(),
                duration: 100,
            }),
        });

        await executeRequest(request);

        expect(mockedAxios.post).toHaveBeenCalled();
        const formDataCall = mockedAxios.post.mock.calls[0][1] as FormData;
        expect(formDataCall).toBeInstanceOf(FormData);
    });
});
