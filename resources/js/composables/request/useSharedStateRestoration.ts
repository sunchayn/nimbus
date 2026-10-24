import { useAppToast } from '@/components/base/toast';
import type { RequestLog } from '@/interfaces/history/logs';
import type { ShareableLinkPayload, SharedState } from '@/interfaces/share';
import { useRequestsHistoryStore, useSharedStateStore, useTabsStore } from '@/stores';
import { createSynthesizedLog } from '@/utils/shareableRestore';
import { onMounted } from 'vue';

export interface UseSharedStateRestorationResult {
    restoreSharedState: () => void;
}

/**
 * Composable for handling shared link state restoration.
 *
 * Initializes and restores request/response state from shareable links.
 */
export const useSharedStateRestoration = (): UseSharedStateRestorationResult => {
    /*
     * Dependencies.
     */

    const toast = useAppToast();
    const sharedStateStore = useSharedStateStore();
    const tabsStore = useTabsStore();
    const historyStore = useRequestsHistoryStore();

    /*
     * Helpers.
     */

    /**
     * Shows a notification about the restoration result.
     */
    const showRestorationNotification = (sharedState: SharedState): void => {
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
    };

    /**
     * Clears the 'share' parameter from the URL.
     */
    const clearShareUrlParameter = (): void => {
        const url = new URL(window.location.href);
        url.searchParams.delete('share');
        window.history.replaceState({}, '', url.toString());
    };

    /**
     * Adds the shared response to history.
     */
    const addSharedResponseToHistory = (
        payload: ShareableLinkPayload,
        historyStore: ReturnType<typeof useRequestsHistoryStore>,
    ): void => {
        if (payload.requestLog) {
            const importedLog: RequestLog = {
                ...payload.requestLog,
                importedFromShare: true,
            };
            historyStore.addLog(importedLog);
        } else if (payload.response) {
            const synthesizedLog = createSynthesizedLog(payload);
            historyStore.addLog(synthesizedLog);
        }
    };

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

        if (sharedState.error) {
            showRestorationNotification(sharedState);
            clearShareUrlParameter();

            return;
        }

        if (!sharedState.payload) {
            return;
        }

        tabsStore.restoreFromSharedPayload(sharedState.payload);

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
};
