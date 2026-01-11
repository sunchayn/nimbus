import { AuthorizationContract } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, RequestBodyTypeEnum, RequestHeader } from '@/interfaces/http';
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

        const headers: RequestHeader[] = [
            { key: 'Content-Type', value: 'application/json' },
        ];
        const body: PendingRequest['body'] = {
            GET: { [RequestBodyTypeEnum.JSON]: '{}' },
        };
        const params = [{ key: 'page', value: '1' }];
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

        pending.queryParameters = [{ key: 'page', value: '1' }];

        expect(store.getRequestUrl(pending)).toBe('https://api.example.com/users?page=1');
    });

    it('resets pending request state', () => {
        const store = createStore();

        store.initializeRequest(baseRoute, [baseRoute]);

        store.resetRequest();

        expect(store.pendingRequestData).toBeNull();
    });
});
