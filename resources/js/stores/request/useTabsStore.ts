import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import type { RequestLog } from '@/interfaces/history/logs';
import {
    RequestBodyTypeEnum,
    type GeneratorType,
    type PendingRequest,
    type SourceGlobalHeaders,
} from '@/interfaces/http';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import type { ShareableLinkPayload } from '@/interfaces/share';
import type { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui';
import type { Tab } from '@/interfaces/ui/tabs';
import {
    useConfigStore,
    useEnvironmentVariablesStore,
    useSettingsStore,
    useValueGeneratorStore,
} from '@/stores';
import { buildRequestUrl, getDefaultPayloadTypeForRoute } from '@/utils/request';
import {
    cloneParameters,
    createDefaultAuthorization,
    createRequestFromShareableLinkPayload,
    restoreResponseBody,
} from '@/utils/request/pendingRequestBuilders';
import { generateValueFromType } from '@/utils/value-generator/generateValueFromType';
import { defineStore } from 'pinia';
import { v4 as uuidv4 } from 'uuid';
import { computed, ref, shallowRef } from 'vue';

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
        const environmentVariablesStore = useEnvironmentVariablesStore();

        /*
         * State.
         */

        const tabs = ref<Tab[]>([]);
        const activeTabId = shallowRef<string | null>(null);

        const activeApplication = shallowRef<string | null>(null);
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
         * Internal Helpers.
         */

        const generateGlobalHeaders = (): ParameterContract[] => {
            return configStore.headers.map(
                (globalHeader: SourceGlobalHeaders): ParameterContract => {
                    const value =
                        globalHeader.type === 'generator'
                            ? generateValueFromType(
                                  globalHeader.value as GeneratorType,
                                  valueGeneratorStore.generateValue,
                              )
                            : String(globalHeader.value);

                    return {
                        type: ParameterType.Text,
                        key: globalHeader.header,
                        value,
                        enabled: true,
                    };
                },
            );
        };

        function useCurrentApplicationGlobalHeaders() {
            if (!activeRequest.value) {
                return;
            }

            const previousGlobalHeaderKeys = lastSyncedGlobalHeaders.value.map(
                header => header.key,
            );

            const filteredHeaders = activeRequest.value.headers.filter(
                header => !previousGlobalHeaderKeys.includes(header.key),
            );

            const newGlobalHeaders = generateGlobalHeaders();

            activeRequest.value.headers = [...newGlobalHeaders, ...filteredHeaders];
            lastSyncedGlobalHeaders.value = newGlobalHeaders;
        }

        const getAuthorizationForNewRequest = (): AuthorizationContract =>
            activeRequest.value?.authorization ??
            createDefaultAuthorization(
                settingsStore.preferences.defaultAuthorizationType,
            );

        const getDefaultPayload = (route: RouteDefinition): RequestBodyTypeEnum => {
            if (settingsStore.preferences.defaultRequestBodyType === -1) {
                return getDefaultPayloadTypeForRoute(route);
            }

            return settingsStore.preferences.defaultRequestBodyType;
        };

        function syncGlobalHeadersForRequest(request: PendingRequest) {
            const newGlobalHeaders = generateGlobalHeaders();

            const newGlobalHeaderKeys = newGlobalHeaders.map(header => header.key);
            const filteredHeaders = request.headers.filter(
                header => !newGlobalHeaderKeys.includes(header.key),
            );

            request.headers = [...newGlobalHeaders, ...filteredHeaders];
            lastSyncedGlobalHeaders.value = newGlobalHeaders;
        }

        function createPendingRequest(
            route: RouteDefinition,
            availableRoutesForEndpoint: RouteDefinition[],
        ): PendingRequest {
            const request: PendingRequest = {
                method: route.method,
                endpoint: route.endpoint,
                headers: activeRequest.value?.headers
                    ? cloneParameters(activeRequest.value.headers)
                    : [],
                body: {},
                payloadType: getDefaultPayload(route),
                schema: route.schema,
                queryParameters: [],
                authorization:
                    activeRequest.value?.authorization ?? getAuthorizationForNewRequest(),
                supportedRoutes: availableRoutesForEndpoint,
                routeDefinition: route,
                isProcessing: false,
                wasExecuted: false,
                durationInMs: 0,
                transactionMode: activeRequest.value?.transactionMode ?? false,
            };

            syncGlobalHeadersForRequest(request);

            return request;
        }

        /*
         * Actions.
         */

        /**
         * Opens a tab for the specified route.
         * If the tab already exists, it is activated.
         */
        function openTab(
            route: RouteDefinition,
            availableRoutesForEndpoint: RouteDefinition[],
        ) {
            const existingTab = tabs.value.find(
                tab =>
                    tab.method.toUpperCase() === route.method.toUpperCase() &&
                    tab.request.endpoint === route.endpoint,
            );

            if (existingTab) {
                activeTabId.value = existingTab.id;

                return;
            }

            const id = uuidv4();
            const newTab: Tab = {
                id,
                title: route.shortEndpoint || route.endpoint,
                method: route.method,
                request: createPendingRequest(route, availableRoutesForEndpoint),
                response: null,
            };

            tabs.value.push(newTab);
            activeTabId.value = id;
        }

        /**
         * Closes a tab by its ID.
         */
        function closeTab(id: string) {
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
        }

        /**
         * Activates a tab by its ID.
         */
        function setActiveTab(id: string) {
            if (tabs.value.some(tab => tab.id === id)) {
                activeTabId.value = id;
            }
        }

        /**
         * Reorders tabs by moving a tab from one index to another.
         */
        function moveTab(fromIndex: number, toIndex: number) {
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
        }

        /**
         * Updates the response log for the active tab.
         */
        function updateActiveTabResponse(log: RequestLog) {
            if (activeTab.value) {
                activeTab.value.response = log;
            }
        }

        /*
         * Request Building Actions (work on active tab).
         */

        function updateRequestMethod(method: string) {
            if (!activeRequest.value) {
                return;
            }

            const normalizedMethod = method.toUpperCase();

            if (normalizedMethod === activeRequest.value.method) {
                return;
            }

            activeRequest.value.method = normalizedMethod;

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
        }

        function updateRequestEndpoint(endpoint: string) {
            if (activeRequest.value) {
                activeRequest.value.endpoint = endpoint;
            }
        }

        function updateRequestHeaders(headers: ParameterContract[]) {
            if (activeRequest.value) {
                activeRequest.value.headers = headers;
            }
        }

        function updateRequestBody(body: PendingRequest['body']) {
            if (activeRequest.value) {
                activeRequest.value.body = body;
            }
        }

        function updateQueryParameters(parameters: ParameterContract[]) {
            if (activeRequest.value) {
                activeRequest.value.queryParameters = parameters;
            }
        }

        function updateAuthorization(authorization: AuthorizationContract) {
            if (activeRequest.value) {
                activeRequest.value.authorization = authorization;
            }
        }

        function updateTransactionMode(transactionMode: boolean) {
            if (activeRequest.value) {
                activeRequest.value.transactionMode = transactionMode;
            }
        }

        const resetRequest = () => {
            if (activeTabId.value) {
                closeTab(activeTabId.value);
            }
        };

        const restoreFromHistory = (historicalRequest: RequestLog) => {
            const tab = activeTab.value;

            if (!tab || !activeRequest.value) {
                return;
            }

            const method = historicalRequest.request.method.toUpperCase();
            const payloadType = historicalRequest.request.payloadType;

            const matchingRoute = activeRequest.value.supportedRoutes.find(
                (route: RouteDefinition) =>
                    route.method.toUpperCase() === method &&
                    route.endpoint === historicalRequest.request.endpoint,
            );

            tab.request = {
                ...activeRequest.value,
                method,
                endpoint: historicalRequest.request.endpoint,
                headers: cloneParameters(historicalRequest.request.headers),
                queryParameters: cloneParameters(
                    historicalRequest.request.queryParameters,
                ),
                payloadType,
                // Restore body into the correct slot with reactivity in mind
                body: restoreResponseBody(
                    activeRequest.value.body,
                    method,
                    payloadType,
                    historicalRequest.request.body,
                ),
                authorization: {
                    ...historicalRequest.request.authorization,
                },
                ...(matchingRoute
                    ? {
                          routeDefinition: matchingRoute,
                          schema: matchingRoute.schema,
                      }
                    : {}),
                wasExecuted: true,
                transactionMode: activeRequest.value.transactionMode ?? false,
            };

            tab.response = historicalRequest;
        };

        const restoreFromSharedPayload = (payload: ShareableLinkPayload) => {
            const newRequest = createRequestFromShareableLinkPayload(payload);

            const id = uuidv4();
            const newTab: Tab = {
                id,
                title: payload.endpoint,
                method: payload.method,
                request: newRequest,
                response:
                    newRequest.wasExecuted && payload.response
                        ? {
                              durationInMs: payload.response.durationInMs,
                              isProcessing: false,
                              request: {
                                  ...newRequest,
                                  headers: cloneParameters(newRequest.headers),
                                  queryParameters: cloneParameters(
                                      newRequest.queryParameters,
                                  ),
                                  body: newRequest.body
                                      ? (newRequest.body[newRequest.method]?.[
                                            newRequest.payloadType
                                        ] ?? null)
                                      : null,
                              },
                              response: payload.response,
                              importedFromShare: true,
                          }
                        : null,
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
                environmentVariablesStore.resolve(request.endpoint),
                request.queryParameters.filter(
                    (parameter: ParameterContract) => parameter.enabled,
                ),
                environmentVariablesStore.resolve,
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
            pick: ['tabs', 'activeTabId', 'activeApplication', 'lastSyncedGlobalHeaders'],
            afterHydrate: context => {
                context.store.syncGlobalHeadersWhenApplicable();
            },
        },
    },
);
