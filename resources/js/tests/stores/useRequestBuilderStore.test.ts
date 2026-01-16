import { AuthorizationContract, ParameterContract, ParameterType } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
import { RouteDefinition } from '@/interfaces/routes';
import { useRequestBuilderStore } from '@/stores/request/useRequestBuilderStore';
import { createPinia, setActivePinia } from 'pinia';
import { describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

const preferences = reactive({
    autoRefreshRoutes: true,
    maxHistoryLogs: 100,
    theme: 'system' as const,
    defaultRequestBodyType: -1 as RequestBodyTypeEnum | -1,
    defaultAuthorizationType: AuthorizationType.CurrentUser,
});

const apiUrl = 'https://api.example.com';

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useSettingsStore: () => ({
            preferences,
        }),
        useConfigStore: () => ({
            apiUrl,
        }),
    };
});

const baseRoute: RouteDefinition = {
    method: 'GET',
    endpoint: 'users',
    shortEndpoint: 'users',
    schema: {
        shape: {
            properties: {
                name: {
                    type: 'string',
                },
            },
        },
        extractionErrors: null,
    },
};

describe('useRequestBuilderStore', () => {
    const createStore = () => {
        setActivePinia(createPinia());

        return useRequestBuilderStore();
    };

    it('initializes pending request data with defaults', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        const pending = store.pendingRequestData as PendingRequest;

        expect(pending.method).toBe('GET');
        expect(pending.endpoint).toBe('users');
        expect(pending.payloadType).toBe(RequestBodyTypeEnum.JSON);
        expect(pending.schema).toMatchObject(baseRoute.schema);
        expect(pending.authorization).toEqual({
            type: AuthorizationType.CurrentUser,
        });
    });

    it('switches schema and payload when method changes', () => {
        const store = createStore();

        const postRoute: RouteDefinition = {
            ...baseRoute,
            method: 'POST',
            schema: {
                shape: {},
                extractionErrors: null,
            },
        };

        store.initializeRequest(baseRoute, [baseRoute, postRoute]);

        store.updateRequestMethod('POST');

        const pending = store.pendingRequestData as PendingRequest;

        expect(pending.method).toBe('POST');
        expect(pending.payloadType).toBe(RequestBodyTypeEnum.EMPTY);
        expect(pending.schema).toMatchObject(postRoute.schema);
    });

    it('falls back to empty payload when route definition missing', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        store.updateRequestMethod('DELETE');

        const pending = store.pendingRequestData as PendingRequest;

        expect(pending.payloadType).toBe(RequestBodyTypeEnum.EMPTY);
    });

    it('updates headers, body, query parameters, and authorization', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        const headers: ParameterContract[] = [
            {
                type: ParameterType.Text,
                key: 'Content-Type',
                value: 'application/json',
                enabled: true,
            },
        ];
        const body: PendingRequest['body'] = {
            GET: { [RequestBodyTypeEnum.JSON]: '{}' },
        };
        const params: ParameterContract[] = [
            { type: ParameterType.Text, key: 'page', value: '1', enabled: true },
        ];
        const auth: AuthorizationContract = {
            type: AuthorizationType.Bearer,
            value: 'token',
        };

        store.updateRequestHeaders(headers);
        store.updateRequestBody(body);
        store.updateQueryParameters(params);
        store.updateAuthorization(auth);

        const pending = store.pendingRequestData as PendingRequest;

        expect(pending.headers).toEqual(headers);
        expect(pending.body).toEqual(body);
        expect(pending.queryParameters).toEqual(params);
        expect(pending.authorization).toEqual(auth);
    });

    it('builds request url using config base path', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        const pending = store.pendingRequestData as PendingRequest;

        pending.queryParameters = [
            { type: ParameterType.Text, key: 'page', value: '1', enabled: true },
        ];

        expect(store.getRequestUrl(pending)).toBe('https://api.example.com/users?page=1');
    });

    it('resets pending request state', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        store.resetRequest();

        expect(store.pendingRequestData).toBeNull();
    });

    it('restores state from a historical request', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        const historicalRequest = {
            method: 'POST',
            endpoint: 'users',
            headers: [
                {
                    type: ParameterType.Text,
                    key: 'X-RequestHistory',
                    value: 'true',
                    enabled: true,
                },
            ],
            body: '{"restored": true}',
            queryParameters: [
                {
                    type: ParameterType.Text,
                    key: 'restored',
                    value: 'true',
                    enabled: true,
                },
            ],
            payloadType: RequestBodyTypeEnum.JSON,
            authorization: { type: AuthorizationType.None },
            routeDefinition: baseRoute,
        };

        // @ts-expect-error simplified for test
        store.restoreFromHistory(historicalRequest);

        const pending = store.pendingRequestData as PendingRequest;

        expect(pending.method).toBe('POST');
        expect(pending.endpoint).toBe('users');
        expect(pending.headers).toEqual(historicalRequest.headers);
        expect(pending.queryParameters).toEqual(historicalRequest.queryParameters);
        expect(pending.payloadType).toBe(RequestBodyTypeEnum.JSON);
        expect(pending.body.POST?.[RequestBodyTypeEnum.JSON]).toBe(
            historicalRequest.body,
        );
    });

    it('preserves existing headers when initializing a new request', () => {
        const store = createStore();

        // Initial request
        store.initializeRequest(baseRoute, [baseRoute]);
        const initialHeaders: ParameterContract[] = [
            { type: ParameterType.Text, key: 'X-Test', value: 'test', enabled: true },
        ];
        store.updateRequestHeaders(initialHeaders);

        // Initialize new request (endpoint switch)
        const nextRoute: RouteDefinition = { ...baseRoute, endpoint: 'posts' };
        store.initializeRequest(nextRoute, [nextRoute]);

        const pending = store.pendingRequestData as PendingRequest;
        expect(pending.endpoint).toBe('posts');
        expect(pending.headers).toEqual(initialHeaders);
    });

    it('deep clones parameters when restoring from history to prevent shared references', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        const historicalRequest = {
            method: 'GET',
            endpoint: 'users',
            headers: [
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'X-Custom',
                    value: 'header1',
                    enabled: true,
                },
                {
                    id: 2,
                    type: ParameterType.Text,
                    key: 'X-Disabled',
                    value: 'header2',
                    enabled: false,
                },
            ],
            body: null,
            queryParameters: [
                {
                    id: 3,
                    type: ParameterType.Text,
                    key: 'active',
                    value: 'yes',
                    enabled: true,
                },
                {
                    id: 4,
                    type: ParameterType.Text,
                    key: 'inactive',
                    value: 'no',
                    enabled: false,
                },
            ],
            payloadType: RequestBodyTypeEnum.EMPTY,
            authorization: { type: AuthorizationType.None },
            routeDefinition: baseRoute,
        };

        // @ts-expect-error simplified for test
        store.restoreFromHistory(historicalRequest);

        const pending = store.pendingRequestData as PendingRequest;

        // Verify values are restored correctly
        expect(pending.headers).toEqual(historicalRequest.headers);
        expect(pending.queryParameters).toEqual(historicalRequest.queryParameters);

        // Modify the restored parameters (simulating UI interaction)
        pending.headers[1].enabled = true;
        pending.queryParameters[1].enabled = true;

        // Verify the original historical request objects are NOT modified (no shared references)
        expect(historicalRequest.headers[1].enabled).toBe(false);
        expect(historicalRequest.queryParameters[1].enabled).toBe(false);
    });
});
