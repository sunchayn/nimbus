import { authorizationConfig } from '@/config';
import { AuthorizationContract } from '@/interfaces/auth/authorization';
import { AuthorizationType } from '@/interfaces/generated';
import { useRequestStore } from '@/stores';
import { computed, readonly, watch } from 'vue';

/**
 * Default authorization states for each type
 */
const defaultAuthStates = {
    [AuthorizationType.None]: { type: AuthorizationType.None },
    [AuthorizationType.Bearer]: {
        type: AuthorizationType.Bearer,
        value: '',
    },
    [AuthorizationType.Basic]: {
        type: AuthorizationType.Basic,
        value: { username: '', password: '' },
    },
    [AuthorizationType.CurrentUser]: {
        type: AuthorizationType.CurrentUser,
    },
    [AuthorizationType.Impersonate]: {
        type: AuthorizationType.Impersonate,
        value: 0,
    },
} as const;

/**
 * Handles reactive authorization state for API requests.
 *
 * Centralizes authorization type selection, validation, and persistence to the request store.
 */
export function useRequestAuthorization() {
    /*
     * Dependencies.
     */

    const requestStore = useRequestStore();

    /*
     * State.
     */

    const authorizationStates = new Map<AuthorizationType, AuthorizationContract>();

    // Initialize with default states
    Object.entries(defaultAuthStates).forEach(([type, state]) => {
        authorizationStates.set(type as AuthorizationType, state as AuthorizationContract);
    });

    const authorization = computed<AuthorizationContract>(() => {
        return (
            requestStore.pendingRequestData?.authorization ?? {
                type: AuthorizationType.CurrentUser,
            }
        );
    });

    const selectedType = computed<AuthorizationType>({
        get: () => authorization.value.type,
        set: newValue => {
            if (newValue !== authorization.value.type) {
                updateAuthorizationType(newValue);
            }
        },
    });

    /*
     * Actions.
     */

    /**
     * Updates the authorization type and restores previous values.
     *
     * Switches between authorization types while preserving user input
     * for each type using the state manager.
     */
    const updateAuthorizationType = (newValue: AuthorizationType): void => {
        // Save current state before switching
        authorizationStates.set(authorization.value.type, {
            ...authorization.value,
        });

        // Switch to new type and restore its previous state
        const savedState = authorizationStates.get(newValue);
        const restoredAuth = (
            savedState ? { ...savedState } : defaultAuthStates[newValue]
        ) as AuthorizationContract;

        // Only update if actually different to prevent unnecessary store commits
        if (
            restoredAuth.type !== authorization.value.type ||
            JSON.stringify(restoredAuth.value) !==
            JSON.stringify(authorization.value.value)
        ) {
            requestStore.updateAuthorization(restoredAuth);
        }
    };

    const updateCurrentAuthorizationValue = (
        newValue: string | number | { username: string; password: string },
    ) => {
        const currentAuth = requestStore.pendingRequestData?.authorization ?? {
            type: AuthorizationType.CurrentUser,
        };

        // Prevent redundant updates
        if (JSON.stringify(newValue) === JSON.stringify(currentAuth.value)) {
            return;
        }

        requestStore.updateAuthorization({
            ...currentAuth,
            value: newValue,
        } as AuthorizationContract);
    };

    /**
     * Saves current authorization state to the request store.
     *
     * Persists the current authorization configuration to the request store
     * for execution and updates the local state cache.
     */
    const saveAuthorizationToStore = (): void => {
        // Update local state cache
        authorizationStates.set(authorization.value.type, {
            ...authorization.value,
        });
    };

    /*
     * Watchers.
     */

    watch(authorization, saveAuthorizationToStore, { deep: true });

    return {
        // State (readonly for external consumers)
        authorization: readonly(authorization),
        selectedType,

        // Constants
        types: {
            special: authorizationConfig.TYPES.SPECIAL,
            traditional: authorizationConfig.TYPES.TRADITIONAL,
        },

        // Actions
        updateAuthorizationType,
        updateCurrentAuthorizationValue,
        saveAuthorizationToStore,
    };
}
