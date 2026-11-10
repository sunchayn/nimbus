import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterType } from '@/interfaces/ui';
import { CODE_LANGUAGES, generateCode } from '@/utils/request/codeGenerators';
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

const resolver = (value: string | number | boolean | null | undefined) =>
    String(value ?? '').replace('{{host}}', 'example.test');
const base = 'https://api.test';

const make = (overrides: Partial<PendingRequest> = {}): PendingRequest => ({
    ...requestBase,
    ...overrides,
});

describe('generateCode', () => {
    it('builds a fetch call with a JSON body and a masked bearer token', () => {
        const code = generateCode('fetch', requestBase, base, resolver);

        expect(code).toContain('fetch("https://api.test/users?page=1"');
        expect(code).toContain('method: "POST"');
        expect(code).toContain('JSON.stringify(');
        expect(code).toContain('"name": "Jane"');
        expect(code).toContain('"Authorization": "Bearer YOUR_TOKEN"');
        expect(code).not.toContain('Bearer token');
    });

    it('builds an Axios call', () => {
        const code = generateCode('axios', requestBase, base, resolver);

        expect(code).toContain('axios.request({');
        expect(code).toContain('method: "post"');
        expect(code).toContain('data: {');
    });

    it('builds a Laravel Http call', () => {
        const code = generateCode('laravel-http', requestBase, base, resolver);

        expect(code).toContain('use Illuminate\\Support\\Facades\\Http;');
        expect(code).toContain('$response = Http::withHeaders([');
        expect(code).toContain("    ->post('https://api.test/users?page=1', [");
        expect(code).toContain("'name' => 'Jane',");
        expect(code).not.toContain('Http::\n');
    });

    it('builds a Guzzle call', () => {
        const code = generateCode('guzzle', requestBase, base, resolver);

        expect(code).toContain(
            "$client->request('POST', 'https://api.test/users?page=1'",
        );
        expect(code).toContain("'json' => [");
    });

    it('builds a Pest test with the observed status', () => {
        const code = generateCode('pest', requestBase, base, resolver, 201);

        expect(code).toContain("->postJson('/users?page=1', [");
        expect(code).toContain('assertStatus(201)');
    });

    it('builds a PHPUnit test class', () => {
        const code = generateCode('phpunit', requestBase, base, resolver);

        expect(code).toContain('extends TestCase');
        expect(code).toContain('public function test_post_users_page_1(): void');
        expect(code).toContain('assertStatus(200)');
    });

    it('masks secret headers, query parameters and body fields', () => {
        const code = generateCode(
            'fetch',
            make({
                headers: [
                    {
                        key: 'X-Api-Key',
                        value: 'abc',
                        enabled: true,
                        type: ParameterType.Text,
                    },
                ],
                queryParameters: [
                    {
                        key: 'access_token',
                        value: 'xyz',
                        enabled: true,
                        type: ParameterType.Text,
                    },
                ],
                body: {
                    POST: {
                        [RequestBodyTypeEnum.JSON]:
                            '{"password":"hunter2","name":"Jane"}',
                    },
                },
                authorization: { type: AuthorizationType.None, value: '' },
            }),
            base,
            resolver,
        );

        expect(code).not.toContain('abc');
        expect(code).not.toContain('xyz');
        expect(code).not.toContain('hunter2');
        expect(code).toContain('YOUR_X_API_KEY');
        expect(code).toContain('YOUR_ACCESS_TOKEN');
        expect(code).toContain('YOUR_PASSWORD');
        expect(code).toContain('"name": "Jane"');
    });

    it('resolves variables in the endpoint', () => {
        const code = generateCode(
            'fetch',
            make({ endpoint: '{{host}}/users' }),
            base,
            resolver,
        );

        expect(code).toContain('example.test');
    });

    it('moves a body to the query for a GET request', () => {
        const code = generateCode(
            'guzzle',
            make({
                method: 'GET',
                queryParameters: [],
                body: { GET: { [RequestBodyTypeEnum.JSON]: '{"q":"a"}' } },
            }),
            base,
            resolver,
        );

        expect(code).toContain('/users?q=a');
        expect(code).not.toContain("'json'");
    });

    it('sends urlencoded fields as form parameters', () => {
        const form = new FormData();
        form.append('a', '1');

        const request = make({
            payloadType: RequestBodyTypeEnum.URLENCODED,
            body: { POST: { [RequestBodyTypeEnum.URLENCODED]: form } },
        });

        expect(generateCode('guzzle', request, base, resolver)).toContain(
            "'form_params' => [",
        );
        expect(generateCode('laravel-http', request, base, resolver)).toContain(
            '->asForm()',
        );
        expect(generateCode('fetch', request, base, resolver)).toContain(
            'new URLSearchParams(',
        );
    });

    it('sends multipart fields with a file placeholder', () => {
        const form = new FormData();
        form.append('title', 'Doc');
        form.append('file', new File(['x'], 'a.txt'));

        const request = make({
            payloadType: RequestBodyTypeEnum.FORM_DATA,
            body: { POST: { [RequestBodyTypeEnum.FORM_DATA]: form } },
        });

        expect(generateCode('guzzle', request, base, resolver)).toContain(
            "'multipart' => [",
        );
        expect(generateCode('guzzle', request, base, resolver)).toContain(
            "fopen('/path/to/a.txt', 'r')",
        );
        expect(generateCode('fetch', request, base, resolver)).toContain(
            'formData.append("title", "Doc");',
        );
    });

    it('sends a raw text body as written', () => {
        const request = make({
            payloadType: RequestBodyTypeEnum.PLAIN_TEXT,
            body: { POST: { [RequestBodyTypeEnum.PLAIN_TEXT]: 'hello' } },
        });

        expect(generateCode('fetch', request, base, resolver)).toContain('body: "hello"');
        expect(generateCode('laravel-http', request, base, resolver)).toContain(
            "->withBody('hello')",
        );
    });

    it('omits the body when there is none', () => {
        const code = generateCode('fetch', make({ body: {} }), base, resolver);

        expect(code).not.toContain('body:');
    });

    it('lists every language', () => {
        expect(CODE_LANGUAGES.map(language => language.value)).toEqual([
            'fetch',
            'axios',
            'laravel-http',
            'guzzle',
            'pest',
            'phpunit',
        ]);
    });
});
