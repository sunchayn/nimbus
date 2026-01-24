import { useHttpClient } from '@/composables/request/useHttpClient';
import type { ErrorPlainResponse, PendingRequest } from '@/interfaces/http';
import { useRequestsHistoryStore } from '@/stores';
import {
    createRequestTimer,
    generateErrorRequestLog,
    generateSuccessRequestLog,
} from '@/utils/request';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

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
    const { executeRequest, cancelCurrentRequest } = useHttpClient();

    /*
     * State.
     */

    const isProcessing = ref(false);
    const duration = ref(0);

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
            // Clear the timer
            if (timer) {
                timer.stop();
            }

            requestData.isProcessing = false;
            requestData.wasExecuted = true;
        };

        try {
            // Update the request processing state
            requestData.isProcessing = true;
            requestData.durationInMs = 0;

            // Start timer to update duration in real-time
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

            // Create RequestLog for history using utility function
            const requestLog = generateSuccessRequestLog(
                requestData,
                result.duration,
                result.response!,
            );

            historyStore.addLog(requestLog);
        } catch (error) {
            // Create RequestLog for history with error using utility function
            const requestLog = generateErrorRequestLog(
                requestData,
                error as ErrorPlainResponse,
            );

            historyStore.addLog(requestLog);

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
