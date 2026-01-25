import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import type { RequestLog } from '@/interfaces/history/logs';
import type { Request, RequestBodyTypeEnum } from '@/interfaces/http';
import type { Response } from '@/interfaces/http/response';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import type { ShareableLinkPayload, SharedState } from '@/interfaces/share';
import type { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui';
import { useRequestsHistoryStore, useSharedStateStore } from '@/stores';
import { useRequestBuilderStore } from '@/stores/request/useRequestBuilderStore';
import { onMounted } from 'vue';
import { toast } from 'vue-sonner';

export interface UseSharedStateRestorationResult {
    restoreSharedState: () => void;
}

/**
 * Composable for handling shared link state restoration.
 *
 * Initializes and restores request/response state from shareable links.
 */
export function useSharedStateRestoration(): UseSharedStateRestorationResult {
    /*
     * Dependencies.
     */

    const sharedStateStore = useSharedStateStore();
    const requestBuilderStore = useRequestBuilderStore();
    const historyStore = useRequestsHistoryStore();

    /*
     * Helpers.
     */

    /**
     * Maps header objects to ParameterContract format.
     */
    function mapHeadersToParameterContract(
        headers: Array<{ key: string; value: string | number | boolean | null }>,
    ): ParameterContract[] {
        return headers.map(header => ({
            key: header.key,
            value: String(header.value ?? ''),
            type: ParameterType.Text,
            enabled: true,
        }));
    }

    /**
     * Maps query parameter objects to ParameterContract format.
     */
    function mapQueryParametersToParameterContract(
        queryParameters: Array<{ key: string; value: string; type?: string }>,
    ): ParameterContract[] {
        return queryParameters.map(param => ({
            key: param.key,
            value: param.value,
            type: param.type === 'file' ? ParameterType.File : ParameterType.Text,
            enabled: true,
        }));
    }

    /**
     * Builds a route definition from payload data.
     */
    function buildRouteDefinition(payload: ShareableLinkPayload): RouteDefinition {
        return {
            endpoint: payload.endpoint,
            method: payload.method,
            schema: { shape: {}, extractionErrors: null },
            shortEndpoint: payload.endpoint,
        };
    }

    /**
     * Builds a Request object from the shared payload.
     */
    function buildRequestFromPayload(payload: ShareableLinkPayload): Request {
        return {
            method: payload.method,
            endpoint: payload.endpoint,
            headers: mapHeadersToParameterContract(payload.headers),
            body: null,
            queryParameters: mapQueryParametersToParameterContract(
                payload.queryParameters,
            ),
            payloadType: payload.payloadType as RequestBodyTypeEnum,
            authorization: payload.authorization as AuthorizationContract,
            routeDefinition: buildRouteDefinition(payload),
        };
    }

    /**
     * Builds a Response object from the shared payload response data.
     */
    function buildResponseFromPayload(
        responseData: NonNullable<ShareableLinkPayload['response']>,
    ): Response {
        return {
            status: responseData.status,
            statusCode: responseData.statusCode,
            statusText: responseData.statusText,
            body: responseData.body,
            sizeInBytes: responseData.sizeInBytes,
            headers: responseData.headers.map(header => ({
                key: header.key,
                value: header.value,
            })),
            cookies: responseData.cookies,
            timestamp: responseData.timestamp,
        };
    }

    /**
     * Creates a synthesized RequestLog from payload data.
     */
    function createSynthesizedLog(payload: ShareableLinkPayload): RequestLog {
        if (!payload.response) {
            throw new Error('Cannot create synthesized log without response data');
        }

        return {
            durationInMs: payload.response.durationInMs,
            isProcessing: false,
            request: buildRequestFromPayload(payload),
            response: buildResponseFromPayload(payload.response),
            importedFromShare: true,
        };
    }

    /**
     * Shows a notification about the restoration result.
     */
    function showRestorationNotification(sharedState: SharedState): void {
        if (sharedState.error) {
            toast.error('Failed to Restore Shareable Link', {
                description: sharedState.error,
                duration: 8000,
            });
        } else if (!sharedState.routeExists) {
            toast.warning('Route Not Found', {
                description:
                    'The shared route does not exist in any application. Request details are still displayed.',
                duration: 6000,
            });
        } else {
            toast.success('Shared Request Restored', {
                description:
                    'Request and response have been imported from the shareable link.',
            });
        }
    }

    /**
     * Clears the 'share' parameter from the URL.
     */
    function clearShareUrlParameter(): void {
        const url = new URL(window.location.href);
        url.searchParams.delete('share');
        window.history.replaceState({}, '', url.toString());
    }

    /**
     * Adds the shared response to history.
     */
    function addSharedResponseToHistory(
        payload: ShareableLinkPayload,
        historyStore: ReturnType<typeof useRequestsHistoryStore>,
    ): void {
        if (payload.requestLog) {
            // Use the full request log if available
            const importedLog: RequestLog = {
                ...payload.requestLog,
                importedFromShare: true,
            };
            historyStore.addLog(importedLog);
        } else if (payload.response) {
            // Create a synthesized log from the response
            const synthesizedLog = createSynthesizedLog(payload);
            historyStore.addLog(synthesizedLog);
        }
    }

    /*
     * Actions.
     */

    const restoreSharedState = () => {
        const sharedState = window.Nimbus?.sharedState as SharedState | null | undefined;

        if (!sharedState) {
            return;
        }

        sharedStateStore.sharedState = sharedState;
        sharedStateStore.isRestoredFromShare = true;

        // If there's an error, show notification and skip restoration
        if (sharedState.error) {
            showRestorationNotification(sharedState);
            clearShareUrlParameter();

            return;
        }

        // If there's no payload, nothing to restore
        if (!sharedState.payload) {
            return;
        }

        requestBuilderStore.restoreFromSharedPayload({
            method: sharedState.payload.method,
            endpoint: sharedState.payload.endpoint,
            headers: sharedState.payload.headers,
            queryParameters: sharedState.payload.queryParameters,
            body: sharedState.payload.body,
            payloadType: sharedState.payload.payloadType,
            authorization: sharedState.payload.authorization,
            durationInMs: sharedState.payload.response?.durationInMs,
            wasExecuted: !!sharedState.payload.response,
        });

        addSharedResponseToHistory(sharedState.payload, historyStore);

        showRestorationNotification(sharedState);

        clearShareUrlParameter();
    };

    /*
     * Lifecycle.
     */

    onMounted(() => {
        restoreSharedState();
    });

    return {
        // Actions
        restoreSharedState,
    };
}
