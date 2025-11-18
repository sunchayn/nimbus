import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
import { generateCurlCommand } from '@/utils/request';
import { describe, expect, it } from 'vitest';

const requestBase: PendingRequest = {
    method: 'POST',
    endpoint: 'users',
    headers: [
        { key: 'Authorization', value: 'Bearer token' },
        { key: 'Accept', value: 'application/json' },
    ],
    body: {
        POST: {
            [RequestBodyTypeEnum.JSON]: JSON.stringify({ name: 'Jane' }),
        },
    },
    payloadType: RequestBodyTypeEnum.JSON,
    schema: {
        shape: {
            'x-name': 'root',
            'x-required': false,
        },
        extractionErrors: null,
    },
    queryParameters: [{ key: 'page', value: '1' }],
    authorization: { type: AuthorizationType.Bearer, value: 'token' },
    supportedRoutes: [],
    routeDefinition: {
        method: 'POST',
        endpoint: 'users',
        shortEndpoint: 'users',
        schema: {
            shape: {
                'x-name': 'root',
                'x-required': false,
            },
            extractionErrors: null,
        },
    },
    isProcessing: false,
    wasExecuted: false,
    durationInMs: 0,
};

describe('generateCurlCommand', () => {
    it('builds curl command with method, headers, and body', () => {
        const { command, hasSpecialAuth } = generateCurlCommand(
            requestBase,
            'https://api.example.com',
        );

        expect(command).toContain('curl');
        expect(command).toContain('-X POST');
        expect(command).toContain('"https://api.example.com/users?page=1"');
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(command).toContain('{"name":"Jane"}');
        expect(hasSpecialAuth).toBe(false);
    });

    it('flags special authorization types', () => {
        const request: PendingRequest = {
            ...requestBase,
            authorization: { type: AuthorizationType.Impersonate, value: 1 },
        } as PendingRequest;

        const { hasSpecialAuth } = generateCurlCommand(
            request,
            'https://api.example.com',
        );

        expect(hasSpecialAuth).toBe(true);
    });
});
