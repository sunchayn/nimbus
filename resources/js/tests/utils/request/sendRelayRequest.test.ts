import { AuthorizationType } from '@/interfaces/generated';
import { createMockRelayProxyResponse } from '@/tests/_utils/testFactories';
import { sendRelayRequest } from '@/utils/request/sendRelayRequest';
import axios from 'axios';
import type { Mocked } from 'vitest';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

// Mock axios
vi.mock('axios');
const mockedAxios = axios as Mocked<typeof axios>;

const options = {
    apiUrl: 'https://api.example.com',
    appBasePath: '/nimbus',
    resolve: (val: string | number | boolean | null | undefined): string =>
        String(val ?? '')
            .replace('{{resource}}', 'users')
            .replace('{{name}}', 'John'),
};

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

describe('sendRelayRequest', () => {
    beforeEach(() => {
        vi.clearAllMocks();
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

            // Act

            const result = await sendRelayRequest(request, options);

            // Assert

            expect(result?.response.statusCode).toBe(200);
            expect(result?.response.body).toBe('{"success": true}');
            expect(result?.duration).toBe(120);
        });

        it('should handle request cancellation', async () => {
            // Arrange

            const request = createMockPendingRequest();
            const cancelError = { code: 'ERR_CANCELED', message: 'Canceled' };

            mockedAxios.post.mockRejectedValue(cancelError);

            // Act

            const result = await sendRelayRequest(request, options);

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

            // Act

            await sendRelayRequest(request, options);

            // Assert

            const formData = mockedAxios.post.mock.calls[0][1] as FormData;

            expect(formData.get('endpoint')).toBe('https://api.example.com/api/users');
            expect(formData.get('body')).toBe('{"name": "John"}');
        });
    });
});
