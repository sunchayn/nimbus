import { useTabsStore } from '@/stores';
import { defineStore } from 'pinia';
import { computed } from 'vue';
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

    const tabsStore = useTabsStore();
    const executorStore = useRequestExecutorStore();

    /*
     * Computed.
     */

    const hasActiveRequest = computed(() => tabsStore.hasActiveRequest);
    const pendingRequestData = computed(() => tabsStore.pendingRequestData);
    const canExecute = computed(() =>
        executorStore.canExecute(tabsStore.pendingRequestData),
    );

    /*
     * Actions.
     */

    /**
     * Initializes a new request (forward to builder store) and resets the execution state.
     */
    const initializeRequest = (
        route: Parameters<typeof tabsStore.openTab>[0],
        supportedRoutes: Parameters<typeof tabsStore.openTab>[1],
    ) => {
        if (
            route.endpoint === pendingRequestData.value?.endpoint &&
            route.method === pendingRequestData.value?.method
        ) {
            return;
        }

        // Cancel ongoing request.
        executorStore.cancelCurrentRequest();

        tabsStore.openTab(route, supportedRoutes);
    };

    return {
        // State from builder store
        isProcessing: computed(() => executorStore.isProcessing),

        // Computed
        hasActiveRequest,
        pendingRequestData,
        canExecute,

        // Request Building Actions (delegated to tabs store)
        updateRequestMethod: tabsStore.updateRequestMethod,
        updateRequestEndpoint: tabsStore.updateRequestEndpoint,
        updateRequestHeaders: tabsStore.updateRequestHeaders,
        updateRequestBody: tabsStore.updateRequestBody,
        updateQueryParameters: tabsStore.updateQueryParameters,
        updateAuthorization: tabsStore.updateAuthorization,
        updateTransactionMode: tabsStore.updateTransactionMode,
        getRequestUrl: tabsStore.getRequestUrl,
        resetRequest: tabsStore.resetRequest,
        restoreFromHistory: tabsStore.restoreFromHistory,

        // Request Execution Actions (delegated to executor store)
        executeCurrentRequest: () => {
            if (!tabsStore.pendingRequestData) {
                return;
            }

            return executorStore.executeRequestWithTiming(tabsStore.pendingRequestData);
        },
        cancelCurrentRequest: executorStore.cancelCurrentRequest,

        // Combined actions
        initializeRequest,
    };
});
