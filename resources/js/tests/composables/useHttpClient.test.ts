import { useHttpClient } from '@/composables/request/useHttpClient';
import { AuthorizationType } from '@/interfaces/generated';
import { ParameterType } from '@/interfaces/ui';
import {
    createMockPendingRequest,
    createMockRelayProxyResponse,
} from '@/tests/_utils/test-factories';
import axios, { AxiosError } from 'axios';
import type { Mocked } from 'vitest';
import { describe, expect, it, vi } from 'vitest';

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

vi.mock('@/stores', () => ({
    useConfigStore: () => mockConfigStore,
}));

describe('useHttpClient', () => {
    /*
     * Initialization tests.
     */

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

            const { buildRequestUrl } = useHttpClient();
            const request = createMockPendingRequest({
                endpoint: 'api/users',
                authorization: {
                    type: AuthorizationType.None,
                },
                queryParameters: [
                    { key: 'page', value: '1', enabled: true, type: ParameterType.Text },
                    {
                        key: 'limit',
                        value: '10',
                        enabled: true,
                        type: ParameterType.Text,
                    },
                ],
            });

            // Act

            const url = buildRequestUrl(request);

            // Assert

            expect(url).toBe('https://api.example.com/api/users?page=1&limit=10');
        });

        it('should handle endpoint with leading slashes', () => {
            // Arrange

            const { buildRequestUrl } = useHttpClient();
            const request = createMockPendingRequest({
                endpoint: '//api/users',
                authorization: {
                    type: AuthorizationType.None,
                },
            });

            // Act

            const url = buildRequestUrl(request);

            // Assert

            expect(url).toBe('https://api.example.com/api/users');
        });
    });

    describe('Request Execution', () => {
        it('should execute request and return correctly parsed response', async () => {
            // Arrange

            const request = createMockPendingRequest({
                endpoint: 'api/users',
                method: 'POST',
                body: {
                    POST: {
                        json: JSON.stringify({ name: 'John' }),
                    },
                },
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
            const cancelError = new AxiosError('Request cancelled');
            cancelError.code = 'ERR_CANCELED';

            mockedAxios.post.mockRejectedValue(cancelError);

            // Act

            const result = await executeRequest(request);

            // Assert

            expect(result).toBeNull();
        });
    });
});
