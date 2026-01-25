import type { AuthorizationContract, ParameterContract } from '@/interfaces';
import { ParameterType } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { GeneratorType, RequestBodyTypeEnum } from '@/interfaces/http';
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
let activeApplication: string | null = 'app-1';
let headers: Array<{
    header: string;
    type: 'raw' | 'generator';
    value: string | number;
}> = [{ header: 'X-App-1', type: 'raw', value: 'value-1' }];

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useSettingsStore: () => ({ preferences }),
        useConfigStore: () => ({
            get apiUrl() {
                return apiUrl;
            },
            get activeApplication() {
                return activeApplication;
            },
            get headers() {
                return headers;
            },
        }),
        useValueGeneratorStore: () => ({
            generateValue: (type: string) => `generated-${type}`,
        }),
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
        activeApplication = 'app-1';
        headers = [{ header: 'X-App-1', type: 'raw', value: 'value-1' }];
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

            const testHeaders: ParameterContract[] = [
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

            store.updateRequestHeaders(testHeaders);
            store.updateRequestBody(body);
            store.updateQueryParameters(params);
            store.updateAuthorization(auth);

            // Assert

            const pending = store.pendingRequestData as PendingRequest;
            expect(pending.headers).toEqual(testHeaders);
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

    /*
     * Synchronization tests.
     */

    describe('Synchronization', () => {
        it('re-syncs global headers when switching applications', () => {
            // Arrange

            const store = useRequestBuilderStore();
            store.initializeRequest(baseRoute, [baseRoute]);

            // Setup initial state: app-1 global headers + one custom header
            store.activeApplication = 'app-1';
            store.lastSyncedGlobalHeaders = [
                {
                    type: ParameterType.Text,
                    key: 'X-App-1',
                    value: 'value-1',
                    enabled: true,
                },
            ];
            store.pendingRequestData!.headers = [
                ...store.lastSyncedGlobalHeaders,
                {
                    type: ParameterType.Text,
                    key: 'X-Custom',
                    value: 'custom',
                    enabled: true,
                },
            ];

            // Mock new app config
            activeApplication = 'app-2';
            headers = [
                { header: 'X-App-2', type: 'raw', value: 'value-2' },
                { header: 'X-Generated', type: 'generator', value: GeneratorType.Uuid },
            ];

            // Act

            store.syncGlobalHeadersWhenApplicable();

            // Assert

            expect(store.pendingRequestData?.headers).toHaveLength(3);
            expect(store.pendingRequestData?.headers[0].key).toBe('X-App-2');
            expect(store.pendingRequestData?.headers[1].key).toBe('X-Generated');
            expect(store.pendingRequestData?.headers[1].value).toBe('generated-uuid');
            expect(store.pendingRequestData?.headers[2].key).toBe('X-Custom');

            // Verify old global header is gone
            const keys = store.pendingRequestData?.headers.map(h => h.key);
            expect(keys).not.toContain('X-App-1');

            // Verify lastSyncedGlobalHeaders is updated
            expect(store.lastSyncedGlobalHeaders).toHaveLength(2);
            expect(store.lastSyncedGlobalHeaders[0].key).toBe('X-App-2');

            // Verify activeApplication is updated
            expect(store.activeApplication).toBe('app-2');
        });
    });
});
