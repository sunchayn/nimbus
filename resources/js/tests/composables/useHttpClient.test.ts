import { useHttpClient } from '@/composables/request/useHttpClient';
import { AuthorizationType } from '@/interfaces/generated';
import { createMockRelayProxyResponse } from '@/tests/_utils/test-factories';
import axios from 'axios';
import type { Mocked } from 'vitest';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

// Mock axios
vi.mock('axios');
const mockedAxios = axios as Mocked<typeof axios>;

// Mock the config store
const mockConfigStore = {
    apiUrl: 'https://api.example.com',
    appBasePath: '/nimbus',
};

// Mock environment variables store
const mockEnvStore = {
    resolve: vi.fn(val => {
        if (typeof val !== 'string') {
            return '';
        }

        return val.replace('{{resource}}', 'users').replace('{{name}}', 'John');
    }),
};

vi.mock('@/stores', () => ({
    useConfigStore: () => mockConfigStore,
    useEnvironmentVariablesStore: () => mockEnvStore,
}));

import { ParameterType } from '@/interfaces';
import { RequestBodyTypeEnum, type PendingRequest } from '@/interfaces/http';

const createMockPendingRequest = (
    overrides: Partial<PendingRequest> = {},
): PendingRequest =>
    ({
        method: 'GET',
        endpoint: '/api/users',
        headers: [],
        body: {
            GET: {
                [RequestBodyTypeEnum.JSON]: null,
            },
        },
        queryParameters: [],
        payloadType: RequestBodyTypeEnum.JSON,
        authorization: { type: AuthorizationType.None },
        schema: { shape: {}, extractionErrors: null },
        supportedRoutes: [],
        routeDefinition: {
            method: 'GET',
            endpoint: '/api/users',
            shortEndpoint: '/api/users',
            schema: { shape: {}, extractionErrors: null },
        },
        transactionMode: false,
        ...overrides,
    }) as PendingRequest;

describe('useHttpClient', () => {
    /*
     * Initialization tests.
     */

    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Initialization', () => {
        it('should initialize with correct default state', () => {
            // Act

            const { isExecuting } = useHttpClient();

            // Assert

            expect(isExecuting.value).toBe(false);
        });
    });

    /*
     * Behavior tests.
     */

    describe('Request Building', () => {
        it('should build request URL correctly', () => {
            // Arrange

            const { buildUrlFromRequest } = useHttpClient();

            const request = createMockPendingRequest({
                endpoint: 'api/users',
                authorization: {
                    type: AuthorizationType.None,
                },
                queryParameters: [
                    {
                        key: 'page',
                        value: '1',
                        enabled: true,
                        type: ParameterType.Text,
                    },
                    {
                        key: 'limit',
                        value: '10',
                        enabled: true,
                        type: ParameterType.Text,
                    },
                ],
            });

            // Act

            const url = buildUrlFromRequest(request);

            // Assert

            expect(url).toBe('https://api.example.com/api/users?page=1&limit=10');
        });

        it('should handle endpoint with leading slashes', () => {
            // Arrange

            const { buildUrlFromRequest } = useHttpClient();

            const request = createMockPendingRequest({
                endpoint: '//api/users',
                authorization: {
                    type: AuthorizationType.None,
                },
            });

            // Act

            const url = buildUrlFromRequest(request);

            // Assert

            expect(url).toBe('https://api.example.com/api/users');
        });
    });

    describe('Request Execution', () => {
        it('should execute request and return correctly parsed response', async () => {
            // Arrange

            const request = createMockPendingRequest({
                method: 'POST',
                body: {
                    POST: {
                        [RequestBodyTypeEnum.JSON]: JSON.stringify({ name: 'John' }),
                    },
                },
                headers: [],
            });

            const mockRelayResponse = createMockRelayProxyResponse({
                statusCode: 200,
                statusText: 'OK',
                body: '{"success": true}',
                timestamp: 1234567890,
                duration: 120,
            });

            mockedAxios.post.mockResolvedValue({
                data: JSON.stringify(mockRelayResponse),
            });

            const { executeRequest } = useHttpClient();

            // Act

            const result = await executeRequest(request);

            // Assert

            expect(result?.response.statusCode).toBe(200);
            expect(result?.response.body).toBe('{"success": true}');
            expect(result?.duration).toBe(120);
        });

        it('should handle request cancellation', async () => {
            // Arrange

            const { executeRequest } = useHttpClient();
            const request = createMockPendingRequest();
            const cancelError = { code: 'ERR_CANCELED', message: 'Canceled' };

            mockedAxios.post.mockRejectedValue(cancelError);

            // Act

            const result = await executeRequest(request);

            // Assert

            expect(result).toBeNull();
        });

        it('should resolve environment variables before sending', async () => {
            // Arrange

            const request = createMockPendingRequest({
                endpoint: '/api/{{resource}}',
                method: 'POST',
                body: {
                    POST: {
                        [RequestBodyTypeEnum.JSON]: '{"name": "{{name}}"}',
                    },
                },
            });

            mockedAxios.post.mockResolvedValue({
                data: JSON.stringify(createMockRelayProxyResponse({ statusCode: 200 })),
            });

            const { executeRequest } = useHttpClient();

            // Act

            await executeRequest(request);

            // Assert

            const formData = mockedAxios.post.mock.calls[0][1] as FormData;

            // Notice: buildUrlFromRequest calls resolve() internally via buildRequestUrl
            // and executeRequest calls resolve() for the body.
            expect(formData.get('endpoint')).toBe('https://api.example.com/api/users');
            expect(formData.get('body')).toBe('{"name": "John"}');
        });
    });
});
