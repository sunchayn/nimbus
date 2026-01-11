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
        shape: {},
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
            shape: {},
            extractionErrors: null,
        },
    },
    isProcessing: false,
    wasExecuted: false,
    durationInMs: 0,
};

describe('generateCurlCommand', () => {
    it('builds curl command with method, headers, and body [POST]', () => {
        const { command, hasSpecialAuth } = generateCurlCommand(
            requestBase,
            'https://api.example.com',
        );

        expect(command).toContain('curl');
        expect(command).toContain('-X POST');
        expect(command).toContain('"https://api.example.com/users?page=1"');
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(command).toContain('\'{"name":"Jane"}\'');
        expect(hasSpecialAuth).toBe(false);
    });

    it('builds curl command with method, headers, and body [GET]', () => {
        const getRequestBase = Object.assign({}, requestBase);

        getRequestBase.method = 'GET';

        getRequestBase.body.GET = getRequestBase.body.POST;

        const { command, hasSpecialAuth } = generateCurlCommand(
            getRequestBase,
            'https://api.example.com',
        );

        expect(command).toContain('curl');
        expect(command).toContain('"https://api.example.com/users?page=1&name=Jane"');
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(hasSpecialAuth).toBe(false);
    });

    it('builds curl command with method, headers, and nested body [POST]', () => {
        const getRequestBase = Object.assign({}, requestBase);

        getRequestBase.body.POST = {
            [RequestBodyTypeEnum.JSON]: JSON.stringify({
                user: { firstName: 'Jane', lastName: 'Doe' },
                username: 'foobar',
            }),
        };

        const { command, hasSpecialAuth } = generateCurlCommand(
            getRequestBase,
            'https://api.example.com',
        );

        expect(command).toContain('curl');
        expect(command).toContain('"https://api.example.com/users?page=1');
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(command).toContain(
            '\'{"user":{"firstName":"Jane","lastName":"Doe"},"username":"foobar"}\'',
        );
        expect(hasSpecialAuth).toBe(false);
    });

    it('builds curl command with method, headers, and nested body [GET]', () => {
        const getRequestBase = Object.assign({}, requestBase);

        getRequestBase.method = 'GET';

        getRequestBase.body.GET = {
            [RequestBodyTypeEnum.JSON]: JSON.stringify({
                user: { firstName: 'Jane', lastName: 'Doe' },
                username: 'foobar',
            }),
        };

        const { command, hasSpecialAuth } = generateCurlCommand(
            getRequestBase,
            'https://api.example.com',
        );

        expect(command).toContain('curl');
        expect(command).toContain(
            '"https://api.example.com/users?page=1&user%5BfirstName%5D=Jane&user%5BlastName%5D=Doe&username=foobar"',
        );
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
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
