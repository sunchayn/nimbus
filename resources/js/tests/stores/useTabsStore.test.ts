import { ParameterType } from '@/interfaces';
import { AuthorizationType } from '@/interfaces/generated';
import type { RequestLog } from '@/interfaces/history/logs';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { RouteDefinition } from '@/interfaces/routes';
import { useTabsStore } from '@/stores/request/useTabsStore';
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
        useEnvironmentVariablesStore: () => ({
            activeCollection: {
                variables: [],
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

const secondRoute: RouteDefinition = {
    method: 'POST',
    endpoint: 'posts',
    shortEndpoint: 'posts',
    schema: { shape: {}, extractionErrors: null },
};

describe('useTabsStoreUnitTest', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        activeApplication = 'app-1';
        headers = [{ header: 'X-App-1', type: 'raw', value: 'value-1' }];
    });

    /*
     * Tab Management.
     */

    describe('Tab Management', () => {
        it('opens a new tab and sets it as active', () => {
            // Arrange

            const store = useTabsStore();

            // Act

            store.openTab(baseRoute, [baseRoute]);

            // Assert

            expect(store.tabs).toHaveLength(1);
            expect(store.tabs[0].id).toMatch(/^[0-9a-f-]{36}$/);
            expect(store.activeTabId).toBe(store.tabs[0].id);
            expect(store.activeTab?.title).toBe('users');
        });

        it('does not create duplicate tabs for the same route', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);

            // Act

            store.openTab(baseRoute, [baseRoute]);

            // Assert

            expect(store.tabs).toHaveLength(1);
        });

        it('activates an existing tab when opened', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);
            const firstTabId = store.tabs[0].id;
            store.openTab(secondRoute, [secondRoute]);
            store.setActiveTab(firstTabId);

            // Act

            store.openTab(secondRoute, [secondRoute]);

            // Assert

            expect(store.activeTabId).toBe(store.tabs[1].id);
        });

        it('closes a tab and updates active tab if necessary', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);
            store.openTab(secondRoute, [secondRoute]);
            const secondTabId = store.tabs[1].id;

            // Act

            store.closeTab(secondTabId);

            // Assert

            expect(store.tabs).toHaveLength(1);
            expect(store.activeTabId).toBe(store.tabs[0].id);
        });

        it('reorders tabs using moveTab', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);
            const firstId = store.tabs[0].id;
            store.openTab(secondRoute, [secondRoute]);
            const secondId = store.tabs[1].id;

            // Act

            store.moveTab(0, 1);

            // Assert

            expect(store.tabs[0].id).toBe(secondId);
            expect(store.tabs[1].id).toBe(firstId);
        });
    });

    /*
     * Response Management.
     */

    describe('Response Management', () => {
        it('updates active tab response', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);
            const mockLog = {
                id: 'log-1',
                method: 'GET',
                endpoint: 'users',
            } as unknown as RequestLog;

            // Act

            store.updateActiveTabResponse(mockLog);

            // Assert

            expect(store.activeResponse).toEqual(mockLog);
            expect(store.tabs[0].response).toEqual(mockLog);
        });

        it('isolates responses per tab', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);
            const id1 = store.tabs[0].id;
            store.openTab(secondRoute, [secondRoute]);
            const id2 = store.tabs[1].id;

            const log1 = {
                id: 'log-1',
                endpoint: 'users',
            } as unknown as RequestLog;
            const log2 = {
                id: 'log-2',
                endpoint: 'posts',
            } as unknown as RequestLog;

            // Act

            store.setActiveTab(id1);
            store.updateActiveTabResponse(log1);
            store.setActiveTab(id2);
            store.updateActiveTabResponse(log2);

            // Assert

            expect(store.tabs.find(t => t.id === id1)?.response).toEqual(log1);
            expect(store.tabs.find(t => t.id === id2)?.response).toEqual(log2);
            expect(store.activeResponse).toEqual(log2);
        });
    });

    /*
     * Request Building.
     */

    describe('Request Building', () => {
        it('updates request data for the active tab', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);

            // Act

            store.updateRequestEndpoint('new-endpoint');

            // Assert

            expect(store.activeRequest?.endpoint).toEqual('new-endpoint');
        });

        it('synchronizes global headers when switching apps across tabs', () => {
            // Arrange

            const store = useTabsStore();
            store.openTab(baseRoute, [baseRoute]);

            // Simulate initial sync
            store.activeApplication = 'app-1';
            store.lastSyncedGlobalHeaders = [
                {
                    key: 'X-App-1',
                    value: 'value-1',
                    type: ParameterType.Text,
                    enabled: true,
                },
            ];

            // Mock app switch
            activeApplication = 'app-2';
            headers = [{ header: 'X-App-2', type: 'raw', value: 'value-2' }];

            // Act

            store.syncGlobalHeadersWhenApplicable();

            // Assert

            expect(store.activeRequest?.headers[0].key).toBe('X-App-2');
            expect(store.activeApplication).toBe('app-2');
        });
    });

    /*
     * Restoration.
     */

    describe('Restoration', () => {
        it('restores from a shared payload into a new tab', () => {
            // Arrange

            const store = useTabsStore();
            const payload = {
                method: 'GET',
                endpoint: 'shared-route',
                headers: [{ key: 'X-Shared', value: 'val' }],
                body: {},
                payloadType: RequestBodyTypeEnum.JSON,
                queryParameters: [],
                authorization: { type: AuthorizationType.None },
            };

            // Act

            store.restoreFromSharedPayload(payload);

            // Assert

            expect(store.tabs).toHaveLength(1);
            expect(store.activeTabId).toMatch(/^[0-9a-f-]{36}$/);
            expect(store.activeRequest?.endpoint).toEqual('shared-route');
        });
    });
});
