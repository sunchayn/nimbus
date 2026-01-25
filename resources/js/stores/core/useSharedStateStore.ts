/**
 * Store for managing shared link state restoration.
 *
 * Handles the initialization and restoration of request/response
 * state from shareable links.
 */

import type { SharedState } from '@/interfaces/share';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useSharedStateStore = defineStore('sharedState', () => {
    /*
     * State.
     */

    const sharedState = ref<SharedState | null>(null);
    const isRestoredFromShare = ref(false);

    /*
     * Computed.
     */

    const hasSharedState = computed(() => sharedState.value !== null);

    const wasImportedFromShare = computed(() => isRestoredFromShare.value);

    const routeExists = computed(() => sharedState.value?.routeExists ?? true);

    const sharedPayload = computed(() => sharedState.value?.payload);

    const sharedError = computed(() => sharedState.value?.error);

    /*
     * Actions.
     */

    /**
     * Initializes the shared state from window.Nimbus.sharedState.
     *
     * This is called on app initialization to check if the current
     * page load was from a shareable link.
     */
    const initializeFromWindow = () => {
        const windowSharedState = window.Nimbus?.sharedState as SharedState | undefined;

        if (windowSharedState) {
            sharedState.value = windowSharedState;
            isRestoredFromShare.value = true;
        }
    };

    /**
     * Marks the state as consumed/restored.
     *
     * Called after the request builder has been populated with shared state.
     */
    const markAsConsumed = () => {
        // Keep the state for UI indicators but prevent re-restoration
        isRestoredFromShare.value = true;
    };

    /**
     * Clears the shared state.
     *
     * Called when the user makes new changes that should clear
     * the "imported from share" indicator.
     */
    const clearSharedState = () => {
        sharedState.value = null;
        isRestoredFromShare.value = false;
    };

    return {
        // State
        sharedState,
        isRestoredFromShare,

        // Computed
        hasSharedState,
        wasImportedFromShare,
        routeExists,
        sharedPayload,
        sharedError,

        // Actions
        initializeFromWindow,
        markAsConsumed,
        clearSharedState,
    };
});
