import { httpClientConfig } from '@/config';
import type { ErrorPlainResponse, PendingRequest } from '@/interfaces/http';
import {
    useConfigStore,
    useEnvironmentVariablesStore,
    useLookupValuesStore,
    useRequestsHistoryStore,
    useTabsStore,
} from '@/stores';
import {
    createRequestTimer,
    generateErrorRequestLog,
    generateSuccessRequestLog,
} from '@/utils/request';
import { sendRelayRequest } from '@/utils/request/sendRelayRequest';
import { signRequestUrl } from '@/utils/request/signRequestUrl';
import { isSignedRoute } from '@/utils/routes';
import { defineStore } from 'pinia';
import { computed, shallowRef, watch } from 'vue';

/**
 * Store for managing request execution and timing.
 *
 * Handles the execution lifecycle of requests including timing,
 * error handling, and history logging.
 */
export const useRequestExecutorStore = defineStore('_requestExecutor', () => {
    /*
     * Stores & dependencies.
     */

    const historyStore = useRequestsHistoryStore();
    const tabsStore = useTabsStore();
    const configStore = useConfigStore();
    const environmentVariablesStore = useEnvironmentVariablesStore();
    const lookupValuesStore = useLookupValuesStore();

    /*
     * State.
     */

    const isProcessing = shallowRef(false);
    const duration = shallowRef(0);

    let abortController: AbortController | null = null;

    /*
     * Computed.
     */

    // TODO [Refactor] Make this an action instead.
    const canExecute = computed(() => (requestData: PendingRequest | null) => {
        return (
            requestData !== null &&
            !requestData.isProcessing &&
            requestData.endpoint.trim() !== ''
        );
    });

    /*
     * Request Execution Actions.
     */

    const cancelCurrentRequest = () => {
        abortController?.abort();
    };

    // The response belongs to the tab that sent the request, so leaving that tab cancels it.
    watch(() => tabsStore.activeTabId, cancelCurrentRequest);

    const executeRequest = async (request: PendingRequest, sign: boolean) => {
        // Prevent concurrent requests to avoid race conditions.
        if (abortController) {
            throw new Error('Request already in progress');
        }

        abortController = new AbortController();

        try {
            const timeoutPromise = new Promise<never>((_, reject) =>
                setTimeout(
                    () => reject(new Error('Request timeout')),
                    httpClientConfig.TIMEOUT,
                ),
            );

            const apiUrl = environmentVariablesStore.baseUrl;
            const appBasePath = configStore.appBasePath;
            const resolve = environmentVariablesStore.createSendResolver();
            const signal = abortController.signal;

            return await Promise.race([
                (async () =>
                    sendRelayRequest(
                        sign
                            ? await signRequestUrl(request, {
                                  apiUrl,
                                  appBasePath,
                                  resolve,
                                  signal,
                              })
                            : request,
                        {
                            apiUrl,
                            environment: environmentVariablesStore.activeEnvironment.id,
                            appBasePath,
                            resolve,
                            signal,
                        },
                    ))(),
                timeoutPromise,
            ]);
        } finally {
            abortController = null;
        }
    };

    /**
     * Executes the provided request with timing and error handling.
     *
     * Manages the complete request lifecycle including timing measurement,
     * state updates, and history logging for both success and error cases.
     */
    const executeRequestWithTiming = async (requestData: PendingRequest) => {
        if (!canExecute.value(requestData)) {
            return;
        }

        let timer: ReturnType<typeof createRequestTimer> | null = null;

        const senderTabId = tabsStore.activeTabId;
        const senderTabIsActive = () => tabsStore.activeTabId === senderTabId;

        const cleanupAfterPendingRequest = () => {
            if (timer) {
                timer.stop();
            }

            requestData.isProcessing = false;
            requestData.wasExecuted = true;
        };

        try {
            requestData.isProcessing = true;
            requestData.durationInMs = 0;

            timer = createRequestTimer(elapsed => {
                if (requestData.isProcessing) {
                    requestData.durationInMs = elapsed;
                }
            });

            const result = await executeRequest(
                requestData,
                requestData.signUrl === true &&
                    isSignedRoute(requestData.routeDefinition),
            );

            if (result === null || !senderTabIsActive()) {
                // The request was canceled or its tab is no longer active.
                // We exit here, we shouldn't add this to the log.

                cleanupAfterPendingRequest();

                requestData.durationInMs = 0; // <- rest the timer.

                return;
            }

            const requestLog = generateSuccessRequestLog(
                requestData,
                result.duration,
                result.response,
            );

            historyStore.addLog(requestLog);
            tabsStore.updateActiveTabResponse(requestLog);
            lookupValuesStore.refreshAfter(requestData);
        } catch (error) {
            if (!senderTabIsActive()) {
                return;
            }

            const requestLog = generateErrorRequestLog(
                requestData,
                error as ErrorPlainResponse,
            );

            historyStore.addLog(requestLog);
            tabsStore.updateActiveTabResponse(requestLog);

            console.error('Request failed:', error);
        } finally {
            cleanupAfterPendingRequest();
        }
    };

    return {
        // State
        isProcessing,
        duration,

        // Computed
        canExecute,

        // Request Execution Actions
        executeRequestWithTiming,
        cancelCurrentRequest,
    };
});
