import type { AuthorizationContract, ParameterContract } from '@/interfaces';
import { ParameterType } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { RouteDefinition } from '@/interfaces/routes';
import { useRequestBuilderStore } from '@/stores/request/useRequestBuilderStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive } from 'vue';

/*
 * Fixtures.
 */

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
        useSettingsStore: () => ({ preferences }),
        useConfigStore: () => ({ apiUrl }),
    };
});

const baseRoute: RouteDefinition = {
    method: 'GET',
    endpoint: 'users',
    shortEndpoint: 'users',
    schema: { shape: {}, extractionErrors: null },
};

describe('useRequestBuilderStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('initializes pending request data with defaults', () => {
            // Arrange

            const store = useRequestBuilderStore();

            // Act

            store.initializeRequest(baseRoute, [baseRoute]);
            const pending = store.pendingRequestData as PendingRequest;

            // Assert

            expect(pending.method).toBe('GET');
            expect(pending.endpoint).toBe('users');
            expect(pending.authorization).toEqual({
                type: AuthorizationType.CurrentUser,
            });
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('updates headers, body, query parameters, and authorization', () => {
            // Arrange

            const store = useRequestBuilderStore();
            store.initializeRequest(baseRoute, [baseRoute]);

            const headers: ParameterContract[] = [
                { type: ParameterType.Text, key: 'X-Test', value: '123', enabled: true },
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

            // Act

            store.updateRequestHeaders(headers);
            store.updateRequestBody(body);
            store.updateQueryParameters(params);
            store.updateAuthorization(auth);

            // Assert

            const pending = store.pendingRequestData as PendingRequest;
            expect(pending.headers).toEqual(headers);
            expect(pending.body).toEqual(body);
            expect(pending.queryParameters).toEqual(params);
            expect(pending.authorization).toEqual(auth);
        });

        it('resets pending request state', () => {
            // Arrange

            const store = useRequestBuilderStore();
            store.initializeRequest(baseRoute, [baseRoute]);

            // Act

            store.resetRequest();

            // Assert

            expect(store.pendingRequestData).toBeNull();
        });
    });
});
