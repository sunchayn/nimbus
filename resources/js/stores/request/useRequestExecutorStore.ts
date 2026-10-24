import { httpClientConfig } from '@/config';
import type { ErrorPlainResponse, PendingRequest } from '@/interfaces/http';
import {
    useConfigStore,
    useEnvironmentVariablesStore,
    useRequestsHistoryStore,
    useTabsStore,
} from '@/stores';
import {
    createRequestTimer,
    generateErrorRequestLog,
    generateSuccessRequestLog,
} from '@/utils/request';
import { sendRelayRequest } from '@/utils/request/sendRelayRequest';
import { defineStore } from 'pinia';
import { computed, shallowRef } from 'vue';

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

    const executeRequest = async (request: PendingRequest) => {
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

            return await Promise.race([
                sendRelayRequest(request, {
                    apiUrl: configStore.apiUrl,
                    appBasePath: configStore.appBasePath,
                    resolve: environmentVariablesStore.resolve,
                    signal: abortController.signal,
                }),
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

            const result = await executeRequest(requestData);

            if (result === null) {
                // The response request didn't finish. This means the request is canceled.
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
        } catch (error) {
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
