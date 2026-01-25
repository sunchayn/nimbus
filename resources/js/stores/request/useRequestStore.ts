import { defineStore } from 'pinia';
import { computed } from 'vue';
import { useRequestBuilderStore } from './useRequestBuilderStore';
import { useRequestExecutorStore } from './useRequestExecutorStore';

/**
 * Unified request store combining builder and executor functionality.
 *
 * Provides a single interface for all request-related operations while
 * maintaining separation of concerns between building and execution.
 */
export const useRequestStore = defineStore('request', () => {
    /*
     * Stores & dependencies.
     */

    const builderStore = useRequestBuilderStore();
    const executorStore = useRequestExecutorStore();

    /*
     * Computed.
     */

    const hasActiveRequest = computed(() => builderStore.hasActiveRequest);
    const pendingRequestData = computed(() => builderStore.pendingRequestData);
    const canExecute = computed(() =>
        executorStore.canExecute(builderStore.pendingRequestData),
    );

    /*
     * Actions.
     */

    /**
     * Initializes a new request (forward to builder store) and resets the execution state.
     */
    const initializeRequest = (
        route: Parameters<typeof builderStore.initializeRequest>[0],
        supportedRoutes: Parameters<typeof builderStore.initializeRequest>[1],
    ) => {
        if (
            route.endpoint === pendingRequestData.value?.endpoint &&
            route.method === pendingRequestData.value?.method
        ) {
            return;
        }

        // Cancel ongoing request.
        executorStore.cancelCurrentRequest();

        builderStore.initializeRequest(route, supportedRoutes);
    };

    return {
        // State from builder store
        isProcessing: computed(() => executorStore.isProcessing),

        // Computed
        hasActiveRequest,
        pendingRequestData,
        canExecute,

        // Request Building Actions (delegated to builder store)
        updateRequestMethod: builderStore.updateRequestMethod,
        updateRequestEndpoint: builderStore.updateRequestEndpoint,
        updateRequestHeaders: builderStore.updateRequestHeaders,
        updateRequestBody: builderStore.updateRequestBody,
        updateQueryParameters: builderStore.updateQueryParameters,
        updateAuthorization: builderStore.updateAuthorization,
        updateTransactionMode: builderStore.updateTransactionMode,
        getRequestUrl: builderStore.getRequestUrl,
        restoreFromHistory: builderStore.restoreFromHistory,

        // Request Execution Actions (delegated to executor store)
        executeCurrentRequest: () => {
            if (!builderStore.pendingRequestData) {
                return;
            }

            return executorStore.executeRequestWithTiming(
                builderStore.pendingRequestData,
            );
        },
        cancelCurrentRequest: executorStore.cancelCurrentRequest,

        // Combined actions
        initializeRequest,
    };
});
