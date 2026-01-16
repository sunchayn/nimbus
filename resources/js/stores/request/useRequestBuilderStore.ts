import { AuthorizationContract } from '@/interfaces/auth/authorization';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, Request, RequestBodyTypeEnum } from '@/interfaces/http';
import { RouteDefinition } from '@/interfaces/routes/routes';
import { ParameterContract } from '@/interfaces/ui';
import { useConfigStore, useSettingsStore } from '@/stores';
import { buildRequestUrl, getDefaultPayloadTypeForRoute } from '@/utils/request';
import { defineStore } from 'pinia';
import { computed, Ref, ref } from 'vue';

/**
 * Store for managing request building and configuration.
 *
 * Handles all aspects of request construction including method,
 * endpoint, headers, body, query parameters, and authorization.
 */
export const useRequestBuilderStore = defineStore(
    '_requestBuilder',
    () => {
        /*
         * Stores.
         */

        const settingsStore = useSettingsStore();
        const configStore = useConfigStore();

        /*
         * State.
         */

        const pendingRequestData: Ref<PendingRequest | null> = ref<PendingRequest | null>(
            null,
        );

        /*
         * Computed.
         */

        const hasActiveRequest = computed(() => pendingRequestData.value !== null);

        /*
         * Request Building Actions.
         */

        /**
         * Switches to the route definition for the specified method on the current endpoint.
         *
         * Updates schema and payload type based on the route definition for the given method.
         * If no route definition exists for this method on this endpoint, defaults to empty payload.
         */
        const switchToRouteDefinitionOf = (
            method: string,
            requestData: PendingRequest,
        ) => {
            // Find route definition for this method within the same endpoint
            const targetRoute = requestData.supportedRoutes.find(
                (route: RouteDefinition) => route.method.toUpperCase() === method,
            );

            if (!targetRoute) {
                // For methods not defined for this endpoint, default to empty payload and schema
                requestData.payloadType = RequestBodyTypeEnum.EMPTY;
                requestData.schema = {
                    shape: {},
                    extractionErrors: null,
                };

                return;
            }

            // Use schema from the route definition for this method
            requestData.payloadType = getDefaultPayloadTypeForRoute(targetRoute);
            requestData.schema = targetRoute.schema;
        };

        const getAuthorizationForNewRequest = function (): AuthorizationContract {
            if (pendingRequestData.value !== null) {
                // Re-use the same authorization from last request if exists.
                return pendingRequestData.value.authorization;
            }

            // Otherwise, use default authorization from settings.

            if (
                settingsStore.preferences.defaultAuthorizationType ===
                AuthorizationType.CurrentUser
            ) {
                return {
                    type: AuthorizationType.CurrentUser,
                };
            }

            if (
                settingsStore.preferences.defaultAuthorizationType ===
                AuthorizationType.Impersonate
            ) {
                return {
                    type: AuthorizationType.Impersonate,
                    value: 1,
                };
            }

            if (
                settingsStore.preferences.defaultAuthorizationType ===
                AuthorizationType.Bearer
            ) {
                return {
                    type: AuthorizationType.Bearer,
                    value: '',
                };
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

            return {
                type: AuthorizationType.None,
            };
        };

        const getDefaultPayload = function (route: RouteDefinition): RequestBodyTypeEnum {
            if (settingsStore.preferences.defaultRequestBodyType === -1) {
                return getDefaultPayloadTypeForRoute(route);
            }

            return settingsStore.preferences.defaultRequestBodyType;
        };

        /**
         * Initializes new pending request with a default state.
         */
        const initializeRequest = (
            route: RouteDefinition,
            availableRoutesForEndpoint: RouteDefinition[],
        ) => {
            const currentHeaders = pendingRequestData.value?.headers ?? [];
            const currentQueryParameters =
                pendingRequestData.value?.queryParameters ?? [];

            pendingRequestData.value = {
                method: route.method,
                endpoint: route.endpoint,
                headers: currentHeaders,
                body: {},
                payloadType: getDefaultPayload(route),
                schema: route.schema,
                queryParameters: currentQueryParameters,
                authorization: getAuthorizationForNewRequest(),
                supportedRoutes: availableRoutesForEndpoint,
                routeDefinition: route,
                isProcessing: false,
                wasExecuted: false,
                durationInMs: 0,
            };
        };

        /**
         * Updates the HTTP method for the current request.
         *
         * Handles method changes by updating schema and payload type based on
         * available route definitions, falling back to empty payload for unsupported methods.
         */
        const updateRequestMethod = (method: string) => {
            if (!pendingRequestData.value) {
                return;
            }

            const normalizedMethod = method.toUpperCase();

            if (normalizedMethod === pendingRequestData.value.method) {
                return; // <- same method, no need to update.
            }

            pendingRequestData.value.method = normalizedMethod;

            // Switch to the route definition for this method if applicable.
            switchToRouteDefinitionOf(normalizedMethod, pendingRequestData.value);
        };

        /**
         * Updates the endpoint URL for the current request.
         */
        const updateRequestEndpoint = (endpoint: string) => {
            if (!pendingRequestData.value) {
                return;
            }

            pendingRequestData.value.endpoint = endpoint;
        };

        /**
         * Updates the headers array for the current request.
         */
        const updateRequestHeaders = (headers: Array<ParameterContract>) => {
            if (!pendingRequestData.value) {
                return;
            }

            pendingRequestData.value.headers = headers;
        };

        /**
         * Updates the request body for the current request.
         */
        const updateRequestBody = (body: PendingRequest['body']) => {
            if (!pendingRequestData.value) {
                return;
            }

            pendingRequestData.value.body = body;
        };

        /**
         * Updates the query parameters for the current request.
         */
        const updateQueryParameters = (parameters: ParameterContract[]) => {
            if (!pendingRequestData.value) {
                return;
            }

            pendingRequestData.value.queryParameters = parameters;
        };

        /**
         * Updates the authorization configuration for the current request.
         */
        const updateAuthorization = (authorization: AuthorizationContract) => {
            if (!pendingRequestData.value) {
                return;
            }

            pendingRequestData.value.authorization = authorization;
        };

        /**
         * Resets the current request to null state.
         */
        const resetRequest = () => {
            pendingRequestData.value = null;
        };

        /*
         * Helper functions.
         */

        /**
         * Builds complete request URL with query parameters.
         *
         * Constructs the full URL by combining base URL, endpoint, and
         * enabled query parameters for the current request.
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

        /**
         * Restores the request builder state from a historical request.
         */
        const restoreFromHistory = (historicalRequest: Request) => {
            if (!pendingRequestData.value) {
                return;
            }

            const method = historicalRequest.method.toUpperCase();
            const payloadType = historicalRequest.payloadType;

            // Try to find and sync the route definition
            const matchingRoute = pendingRequestData.value.supportedRoutes.find(
                route =>
                    route.method.toUpperCase() === method &&
                    route.endpoint === historicalRequest.endpoint,
            );

            pendingRequestData.value = {
                ...pendingRequestData.value,
                method,
                endpoint: historicalRequest.endpoint,
                headers: historicalRequest.headers.map(h => ({ ...h })),
                queryParameters: historicalRequest.queryParameters.map(p => ({ ...p })),
                payloadType,
                // Restore body into the correct slot with reactivity in mind
                body: {
                    ...pendingRequestData.value.body,
                    [method]: {
                        ...(pendingRequestData.value.body[method] ?? {}),
                        [payloadType]: historicalRequest.body,
                    },
                },
                // Restore authorization
                authorization: {
                    ...historicalRequest.authorization,
                },
                // Sync route definition and schema if matching route found
                ...(matchingRoute
                    ? {
                          routeDefinition: matchingRoute,
                          schema: matchingRoute.schema,
                      }
                    : {}),
                wasExecuted: true,
            };
        };

        return {
            // State
            pendingRequestData,

            // Computed
            hasActiveRequest,

            // Actions
            initializeRequest,
            updateRequestMethod,
            updateRequestEndpoint,
            updateRequestHeaders,
            updateRequestBody,
            updateQueryParameters,
            updateAuthorization,
            resetRequest,
            getRequestUrl,
            restoreFromHistory,
        };
    },
    {
        persist: true,
    },
);
