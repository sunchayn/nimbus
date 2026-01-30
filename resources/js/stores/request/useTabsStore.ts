import type {AuthorizationContract} from '@/interfaces/auth/authorization';
import {AuthorizationType} from '@/interfaces/generated';
import type {RequestLog} from '@/interfaces/history/logs';
import type {GeneratorType, PendingRequest, SourceGlobalHeaders,} from '@/interfaces/http';
import {RequestBodyTypeEnum} from '@/interfaces/http';
import type {RouteDefinition} from '@/interfaces/routes/routes';
import type {ShareableLinkPayload} from '@/interfaces/share';
import type {ParameterContract} from '@/interfaces/ui';
import {ParameterType} from '@/interfaces/ui';
import type {Tab} from '@/interfaces/ui/tabs';
import {useConfigStore, useSettingsStore, useValueGeneratorStore} from '@/stores';
import {buildRequestUrl, getDefaultPayloadTypeForRoute} from '@/utils/request';
import {generateValueFromType} from '@/utils/value-generator/generateValueFromType';
import {defineStore} from 'pinia';
import {computed, ref} from 'vue';

/**
 * Store for managing application tabs and their associated request states.
 */
export const useTabsStore = defineStore(
    'tabs',
    () => {
        /*
         * Stores.
         */

        const settingsStore = useSettingsStore();
        const configStore = useConfigStore();
        const valueGeneratorStore = useValueGeneratorStore();

        /*
         * State.
         */

        const tabs = ref<Tab[]>([]);
        const activeTabId = ref<string | null>(null);

        const activeApplication = ref<string | null>(null);
        const lastSyncedGlobalHeaders = ref<ParameterContract[]>([]);

        /*
         * Computed.
         */

        const activeTab = computed(
            () => tabs.value.find(tab => tab.id === activeTabId.value) ?? null,
        );

        const activeRequest = computed(() => activeTab.value?.request ?? null);
        const activeResponse = computed(() => activeTab.value?.response ?? null);

        /**
         * Alias for activeRequest (backward compatibility during migration).
         */
        const pendingRequestData = computed(() => activeRequest.value);

        const hasActiveRequest = computed(() => activeRequest.value !== null);

        /*
         * Private Helpers.
         */

        const useCurrentApplicationGlobalHeaders = () => {
            if (!activeRequest.value) {
                return;
            }

            const previousGlobalHeaderKeys = lastSyncedGlobalHeaders.value.map(
                header => header.key,
            );

            const filteredHeaders = activeRequest.value.headers.filter(
                header => !previousGlobalHeaderKeys.includes(header.key),
            );

            const newGlobalHeaders = configStore.headers.map(
                (globalHeader: SourceGlobalHeaders): ParameterContract => ({
                    type: ParameterType.Text,
                    key: globalHeader.header,
                    value:
                        globalHeader.type === 'generator'
                            ? generateValueFromType(
                                  globalHeader.value as GeneratorType,
                                  valueGeneratorStore,
                              )
                            : String(globalHeader.value),
                    enabled: true,
                }),
            );

            activeRequest.value.headers = [...newGlobalHeaders, ...filteredHeaders];

            lastSyncedGlobalHeaders.value = newGlobalHeaders;
        };

        const getAuthorizationForNewRequest = (): AuthorizationContract => {
            if (activeRequest.value !== null) {
                return activeRequest.value.authorization;
            }

            if (
                settingsStore.preferences.defaultAuthorizationType ===
                AuthorizationType.CurrentUser
            ) {
                return { type: AuthorizationType.CurrentUser };
            }

            if (
                settingsStore.preferences.defaultAuthorizationType ===
                AuthorizationType.Impersonate
            ) {
                return { type: AuthorizationType.Impersonate, value: 1 };
            }

            if (
                settingsStore.preferences.defaultAuthorizationType ===
                AuthorizationType.Bearer
            ) {
                return { type: AuthorizationType.Bearer, value: '' };
            }

            if (
                settingsStore.preferences.defaultAuthorizationType ===
                AuthorizationType.Basic
            ) {
                return {
                    type: AuthorizationType.Basic,
                    value: { username: '', password: '' },
                };
            }

            return { type: AuthorizationType.None };
        };

        const getDefaultPayload = (route: RouteDefinition): RequestBodyTypeEnum => {
            if (settingsStore.preferences.defaultRequestBodyType === -1) {
                return getDefaultPayloadTypeForRoute(route);
            }

            return settingsStore.preferences.defaultRequestBodyType;
        };

        const createPendingRequest = (
            route: RouteDefinition,
            availableRoutesForEndpoint: RouteDefinition[],
        ): PendingRequest => {
            const request: PendingRequest = {
                method: route.method,
                endpoint: route.endpoint,
                headers: [],
                body: {},
                payloadType: getDefaultPayload(route),
                schema: route.schema,
                queryParameters: [],
                authorization: getAuthorizationForNewRequest(),
                supportedRoutes: availableRoutesForEndpoint,
                routeDefinition: route,
                isProcessing: false,
                wasExecuted: false,
                durationInMs: 0,
                transactionMode: false,
            };

            // Sync global headers for the new request
            syncGlobalHeadersForRequest(request);

            return request;
        };

        const syncGlobalHeadersForRequest = (request: PendingRequest) => {
            const newGlobalHeaders = configStore.headers.map(
                (globalHeader: SourceGlobalHeaders): ParameterContract => ({
                    type: ParameterType.Text,
                    key: globalHeader.header,
                    value:
                        globalHeader.type === 'generator'
                            ? generateValueFromType(
                                  globalHeader.value as GeneratorType,
                                  valueGeneratorStore,
                              )
                            : String(globalHeader.value),
                    enabled: true,
                }),
            );

            // Filter out old global headers if any (though for new request there shouldn't be)
            request.headers = [...newGlobalHeaders, ...request.headers];
        };

        /*
         * Actions.
         */

        /**
         * Opens a tab for the specified route.
         * If the tab already exists, it is activated.
         */
        const openTab = (
            route: RouteDefinition,
            availableRoutesForEndpoint: RouteDefinition[],
        ) => {
            const existingTab = tabs.value.find(
                tab =>
                    tab.method.toUpperCase() === route.method.toUpperCase() &&
                    tab.request.endpoint === route.endpoint,
            );

            if (existingTab) {
                activeTabId.value = existingTab.id;

                return;
            }

            const id = crypto.randomUUID();
            const newTab: Tab = {
                id,
                title: route.shortEndpoint || route.endpoint,
                method: route.method,
                request: createPendingRequest(route, availableRoutesForEndpoint),
                response: null,
            };

            tabs.value.push(newTab);
            activeTabId.value = id;
        };

        /**
         * Closes a tab by its ID.
         */
        const closeTab = (id: string) => {
            const index = tabs.value.findIndex(tab => tab.id === id);

            if (index === -1) {
                return;
            }

            tabs.value.splice(index, 1);

            if (activeTabId.value === id) {
                if (tabs.value.length > 0) {
                    const nextIndex = Math.min(index, tabs.value.length - 1);
                    activeTabId.value = tabs.value[nextIndex].id;
                } else {
                    activeTabId.value = null;
                }
            }
        };

        /**
         * Activates a tab by its ID.
         */
        const setActiveTab = (id: string) => {
            if (tabs.value.some(tab => tab.id === id)) {
                activeTabId.value = id;
            }
        };

        /**
         * Closes all tabs and resets state.
         */
        const closeAllTabs = () => {
            tabs.value = [];
            activeTabId.value = null;
        };

        /**
         * Reorders tabs by moving a tab from one index to another.
         */
        const moveTab = (fromIndex: number, toIndex: number) => {
            if (
                fromIndex < 0 ||
                fromIndex >= tabs.value.length ||
                toIndex < 0 ||
                toIndex >= tabs.value.length
            ) {
                return;
            }

            const element = tabs.value.splice(fromIndex, 1)[0];
            tabs.value.splice(toIndex, 0, element);
        };

        /**
         * Updates the response log for the active tab.
         */
        const updateActiveTabResponse = (log: RequestLog) => {
            if (activeTab.value) {
                activeTab.value.response = log;
            }
        };

        /*
         * Request Building Actions (work on active tab).
         */

        const updateRequestMethod = (method: string) => {
            if (!activeRequest.value) {
                return;
            }

            const normalizedMethod = method.toUpperCase();

            if (normalizedMethod === activeRequest.value.method) {
                return;
            }

            activeRequest.value.method = normalizedMethod;

            // Switch to the route definition for this method if applicable.
            const targetRoute = activeRequest.value.supportedRoutes.find(
                (route: RouteDefinition) =>
                    route.method.toUpperCase() === normalizedMethod,
            );

            if (!targetRoute) {
                activeRequest.value.payloadType = RequestBodyTypeEnum.EMPTY;
                activeRequest.value.schema = {
                    shape: {},
                    extractionErrors: null,
                };

                return;
            }

            activeRequest.value.payloadType = getDefaultPayloadTypeForRoute(targetRoute);
            activeRequest.value.schema = targetRoute.schema;
        };

        const updateRequestEndpoint = (endpoint: string) => {
            if (activeRequest.value) {
                activeRequest.value.endpoint = endpoint;
            }
        };

        const updateRequestHeaders = (headers: ParameterContract[]) => {
            if (activeRequest.value) {
                activeRequest.value.headers = headers;
            }
        };

        const updateRequestBody = (body: PendingRequest['body']) => {
            if (activeRequest.value) {
                activeRequest.value.body = body;
            }
        };

        const updateQueryParameters = (parameters: ParameterContract[]) => {
            if (activeRequest.value) {
                activeRequest.value.queryParameters = parameters;
            }
        };

        const updateAuthorization = (authorization: AuthorizationContract) => {
            if (activeRequest.value) {
                activeRequest.value.authorization = authorization;
            }
        };

        const updateTransactionMode = (transactionMode: boolean) => {
            if (activeRequest.value) {
                activeRequest.value.transactionMode = transactionMode;
            }
        };

        const resetRequest = () => {
            if (activeTabId.value) {
                closeTab(activeTabId.value);
            }
        };

        /**
         * Restores the request builder state from a historical request.
         */
        const restoreFromHistory = (historicalRequest: RequestLog) => {
            if (!activeRequest.value) {
                return;
            }

            const method = historicalRequest.request.method.toUpperCase();
            const payloadType = historicalRequest.request.payloadType;

            // Try to find and sync the route definition
            const matchingRoute = activeRequest.value.supportedRoutes.find(
                (route: RouteDefinition) =>
                    route.method.toUpperCase() === method &&
                    route.endpoint === historicalRequest.request.endpoint,
            );

            activeTab.value!.request = {
                ...activeRequest.value,
                method,
                endpoint: historicalRequest.request.endpoint,
                headers: historicalRequest.request.headers.map(
                    (h: ParameterContract) => ({
                        ...h,
                    }),
                ),
                queryParameters: historicalRequest.request.queryParameters.map(
                    (p: ParameterContract) => ({
                        ...p,
                    }),
                ),
                payloadType,
                // Restore body into the correct slot with reactivity in mind
                body: {
                    ...activeRequest.value.body,
                    [method]: {
                        ...(activeRequest.value.body[method] ?? {}),
                        [payloadType]: historicalRequest.request.body,
                    },
                },
                // Restore authorization
                authorization: {
                    ...historicalRequest.request.authorization,
                },
                // Sync route definition and schema if matching route found
                ...(matchingRoute
                    ? {
                        routeDefinition: matchingRoute,
                        schema: matchingRoute.schema,
                    }
                    : {}),
                wasExecuted: true,
                transactionMode: activeRequest.value.transactionMode ?? false,
            };

            activeTab.value!.response = historicalRequest;
        };

        /**
         * Restores request state from a shareable link payload.
         */
        const restoreFromSharedPayload = (payload: ShareableLinkPayload) => {
            const wasExecuted =
                payload.response !== undefined &&
                payload.response.durationInMs !== undefined;

            const newRequest: PendingRequest = {
                method: payload.method.toUpperCase(),
                endpoint: payload.endpoint,
                headers: payload.headers.map(
                    (header: {
                        key: string;
                        value: string | number | boolean | null;
                    }) => ({
                        key: header.key,
                        value: String(header.value ?? ''),
                        type: ParameterType.Text,
                        enabled: true,
                    }),
                ),
                body: payload.body,
                payloadType: payload.payloadType as RequestBodyTypeEnum,
                schema: {
                    shape: {},
                    extractionErrors: null,
                },
                queryParameters: payload.queryParameters.map(
                    (param: { key: string; value: string; type?: 'text' | 'file' }) => ({
                        key: param.key,
                        value: param.value,
                        type:
                            param.type === 'file'
                                ? ParameterType.File
                                : ParameterType.Text,
                        enabled: true,
                    }),
                ),
                authorization: {
                    type: payload.authorization.type as AuthorizationType,
                    value: payload.authorization.value,
                } as AuthorizationContract,
                supportedRoutes: [],
                routeDefinition: {
                    endpoint: payload.endpoint,
                    method: payload.method.toUpperCase(),
                    schema: {
                        shape: {},
                        extractionErrors: null,
                    },
                    shortEndpoint: payload.endpoint,
                },
                isProcessing: false,
                wasExecuted,
                durationInMs: payload.response?.durationInMs ?? 0,
                transactionMode: false,
            };

            const id = crypto.randomUUID();
            const newTab: Tab = {
                id,
                title: payload.endpoint,
                method: payload.method,
                request: newRequest,
                response: null,
            };

            tabs.value.push(newTab);
            activeTabId.value = id;
        };

        const syncGlobalHeadersWhenApplicable = () => {
            if (activeApplication.value === configStore.activeApplication) {
                return;
            }

            useCurrentApplicationGlobalHeaders();

            // Update the active application for the next time.
            // It will be preserved because we persist the store state.
            activeApplication.value = configStore.activeApplication;
        };

        /**
         * Builds complete request URL with query parameters.
         */
        const getRequestUrl = (request: PendingRequest): string => {
            return buildRequestUrl(
                configStore.apiUrl,
                request.endpoint,
                request.queryParameters.filter(
                    (parameter: ParameterContract) =>
                        parameter.enabled && parameter.key.trim() !== '',
                ),
            );
        };

        return {
            // State
            tabs,
            activeTabId,
            activeApplication,
            lastSyncedGlobalHeaders,

            pendingRequestData,

            // Computed
            activeTab,
            activeRequest,
            activeResponse,
            hasActiveRequest,

            // Actions
            openTab,
            closeTab,
            setActiveTab,
            closeAllTabs,
            moveTab,
            updateActiveTabResponse,
            updateRequestMethod,
            updateRequestEndpoint,
            updateRequestHeaders,
            updateRequestBody,
            updateQueryParameters,
            updateAuthorization,
            updateTransactionMode,
            resetRequest,
            restoreFromHistory,
            restoreFromSharedPayload,
            syncGlobalHeadersWhenApplicable,
            getRequestUrl,
        };
    },
    {
        persist: {
            afterHydrate: context => {
                context.store.syncGlobalHeadersWhenApplicable();
            },
        },
    },
);
