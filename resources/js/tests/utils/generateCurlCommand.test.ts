import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterType } from '@/interfaces/ui';
import { generateCurlCommand } from '@/utils/request';
import { describe, expect, it } from 'vitest';

const requestBase: PendingRequest = {
    method: 'POST',
    endpoint: 'users',
    headers: [
        {
            key: 'Authorization',
            value: 'Bearer token',
            enabled: true,
            type: ParameterType.Text,
        },
        {
            key: 'Accept',
            value: 'application/json',
            enabled: true,
            type: ParameterType.Text,
        },
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
    queryParameters: [
        {
            key: 'page',
            value: '1',
            enabled: true,
            type: ParameterType.Text,
        },
    ],
    authorization: {
        type: AuthorizationType.Bearer,
        value: 'token',
    },
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
        // Act

        const { command, hasSpecialAuth } = generateCurlCommand(
            requestBase,
            'https://api.example.com',
            val => val as unknown as string,
        );

        // Assert

        expect(command).toContain('curl');
        expect(command).toContain('-X POST');
        expect(command).toContain('"https://api.example.com/users?page=1"');
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(command).toContain('\'{"name":"Jane"}\'');
        expect(hasSpecialAuth).toBe(false);
    });

    it('builds curl command with an explicit GET method when a body is sent', () => {
        // Arrange

        const getRequestBase = JSON.parse(JSON.stringify(requestBase));

        getRequestBase.method = 'GET';

        getRequestBase.body.GET = getRequestBase.body.POST;

        // Act

        const { command, hasSpecialAuth } = generateCurlCommand(
            getRequestBase,
            'https://api.example.com',
            val => val as unknown as string,
        );

        // Assert

        expect(command).toContain('curl');
        expect(command).toContain('-X GET');
        expect(command).toContain('"https://api.example.com/users?page=1"');
        expect(command).toContain('\'{"name":"Jane"}\'');
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(hasSpecialAuth).toBe(false);
    });

    it('builds curl command with method, headers, and nested body [POST]', () => {
        // Arrange

        const getRequestBase = JSON.parse(JSON.stringify(requestBase));

        getRequestBase.body.POST = {
            [RequestBodyTypeEnum.JSON]: JSON.stringify({
                user: { firstName: 'Jane', lastName: 'Doe' },
                username: 'foobar',
            }),
        };

        // Act

        const { command, hasSpecialAuth } = generateCurlCommand(
            getRequestBase,
            'https://api.example.com',
            val => val as unknown as string,
        );

        // Assert

        expect(command).toContain('curl');
        expect(command).toContain('"https://api.example.com/users?page=1');
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(command).toContain(
            '\'{"user":{"firstName":"Jane","lastName":"Doe"},"username":"foobar"}\'',
        );
        expect(hasSpecialAuth).toBe(false);
    });

    it('keeps a nested JSON body out of the query string [GET]', () => {
        // Arrange

        const getRequestBase = JSON.parse(JSON.stringify(requestBase));

        getRequestBase.method = 'GET';

        getRequestBase.body.GET = {
            [RequestBodyTypeEnum.JSON]: JSON.stringify({
                user: { firstName: 'Jane', lastName: 'Doe' },
                username: 'foobar',
            }),
        };

        // Act

        const { command, hasSpecialAuth } = generateCurlCommand(
            getRequestBase,
            'https://api.example.com',
            val => val as unknown as string,
        );

        // Assert

        expect(command).toContain('curl');
        expect(command).toContain('"https://api.example.com/users?page=1"');
        expect(command).toContain(
            '\'{"user":{"firstName":"Jane","lastName":"Doe"},"username":"foobar"}\'',
        );
        expect(command).toContain('-H "Authorization: Bearer token"');
        expect(command).toContain('-H "Accept: application/json"');
        expect(hasSpecialAuth).toBe(false);
    });

    it('flags special authorization types', () => {
        // Arrange

        const request: PendingRequest = {
            ...requestBase,
            authorization: { type: AuthorizationType.Impersonate, value: 1 },
        } as unknown as PendingRequest;

        // Act

        const { hasSpecialAuth } = generateCurlCommand(
            request,
            'https://api.example.com',
            val => val as unknown as string,
        );

        // Assert

        expect(hasSpecialAuth).toBe(true);
    });

    it('uses data-urlencode for urlencoded bodies', () => {
        const formData = new FormData();
        formData.append('name', 'Ada Lovelace');

        const { command } = generateCurlCommand(
            {
                ...requestBase,
                payloadType: RequestBodyTypeEnum.URLENCODED,
                body: { POST: { [RequestBodyTypeEnum.URLENCODED]: formData } },
            },
            'https://a.test',
            value => String(value ?? ''),
        );

        expect(command).toContain("--data-urlencode 'name=Ada Lovelace'");
        expect(command).toContain('Content-Type: application/x-www-form-urlencoded');
    });

    describe('body types', () => {
        const resolver = (value: unknown) => String(value ?? '');
        const baseUrl = 'https://a.test';

        const generate = (
            payloadType: RequestBodyTypeEnum,
            body: FormData | string | null,
            method = 'POST',
        ) =>
            generateCurlCommand(
                {
                    ...requestBase,
                    method,
                    queryParameters: [],
                    payloadType,
                    body: { [method]: { [payloadType]: body } },
                },
                baseUrl,
                resolver,
            ).command;

        const withFile = () => {
            const formData = new FormData();
            formData.append('file', new File(['x'], 'a.png', { type: 'image/png' }));

            return formData;
        };

        it('sends form data with -F flags and files by name', () => {
            const formData = new FormData();
            formData.append('name', 'Ada');
            formData.append('avatar', new File(['x'], 'me.png'));

            const command = generate(RequestBodyTypeEnum.FORM_DATA, formData);

            expect(command).toContain("-F 'name=Ada'");
            expect(command).toContain("-F 'avatar=@me.png'");
            expect(command).toContain('"https://a.test/users"');
        });

        it('sends urlencoded fields with --data-urlencode', () => {
            const formData = new FormData();
            formData.append('name', 'Ada Lovelace');

            const command = generate(RequestBodyTypeEnum.URLENCODED, formData);

            expect(command).toContain("--data-urlencode 'name=Ada Lovelace'");
            expect(command).toContain('Content-Type: application/x-www-form-urlencoded');
        });

        it.each([
            [RequestBodyTypeEnum.JSON, '{"a":1}', 'application/json'],
            [RequestBodyTypeEnum.PLAIN_TEXT, 'hello', 'text/plain'],
            [RequestBodyTypeEnum.HTML, '<p>hi</p>', 'text/html'],
            [RequestBodyTypeEnum.XML, '<a/>', 'application/xml'],
        ])('sends a %s body with -d and its content type', (type, body, mimeType) => {
            const command = generate(type, body);

            expect(command).toContain(`-d '${body}'`);
            expect(command).toContain(`Content-Type: ${mimeType}`);
        });

        it('escapes single quotes in a text body', () => {
            const command = generate(RequestBodyTypeEnum.PLAIN_TEXT, "it's");

            expect(command).toContain(`-d 'it'\\''s'`);
        });

        it('sends a binary body with --data-binary and the file type', () => {
            const command = generate(RequestBodyTypeEnum.BINARY, withFile());

            expect(command).toContain("--data-binary '@a.png'");
            expect(command).toContain('Content-Type: image/png');
        });

        it('sends no body flag for an empty body', () => {
            const command = generate(RequestBodyTypeEnum.EMPTY, null);

            expect(command).not.toMatch(/ -(d|F|-data)/);
            expect(command).not.toContain('Content-Type');
        });

        it.each([
            [
                RequestBodyTypeEnum.FORM_DATA,
                () => {
                    const formData = new FormData();
                    formData.append('name', 'Ada');

                    return formData;
                },
            ],
            [
                RequestBodyTypeEnum.URLENCODED,
                () => {
                    const formData = new FormData();
                    formData.append('name', 'Ada');

                    return formData;
                },
            ],
            [RequestBodyTypeEnum.JSON, () => '{"name":"Ada"}'],
            [RequestBodyTypeEnum.PLAIN_TEXT, () => 'name=Ada'],
            [RequestBodyTypeEnum.HTML, () => '<p>name=Ada</p>'],
            [RequestBodyTypeEnum.XML, () => '<name>Ada</name>'],
            [RequestBodyTypeEnum.BINARY, withFile],
        ])('never puts a %s body in the query string [GET]', (type, makeBody) => {
            const command = generate(type, makeBody(), 'GET');

            expect(command).toContain('-X GET');
            expect(command).toContain('"https://a.test/users"');
            expect(command).not.toContain('?');
        });
    });
});
