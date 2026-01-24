import { authorizationConfig } from '@/config';
import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import { AuthorizationType, type AuthorizationTypeItem } from '@/interfaces/generated';
import { useRequestStore } from '@/stores';
import { type DeepReadonly, type Ref, readonly, ref, watch } from 'vue';

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
export function useRequestAuthorization(): {
    authorization: DeepReadonly<Ref<AuthorizationContract>>;
    selectedType: Ref<AuthorizationType>;
    types: {
        special: readonly AuthorizationTypeItem[];
        traditional: readonly AuthorizationTypeItem[];
    };
    updateAuthorizationType: (newValue: AuthorizationType) => void;
    updateCurrentAuthorizationValue: (
        newValue: string | number | { username: string; password: string },
    ) => void;
    saveAuthorizationToStore: () => void;
} {
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
        authorizationStates.set(type as AuthorizationType, state);
    });

    const authorization = ref<AuthorizationContract>(
        requestStore.pendingRequestData?.authorization ?? {
            type: AuthorizationType.CurrentUser,
        },
    );

    const selectedType = ref<AuthorizationType>(
        authorization.value?.type ?? authorizationConfig.DEFAULT_TYPE,
    );

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
        if (authorization.value) {
            authorizationStates.set(authorization.value.type, {
                ...authorization.value,
            });
        }

        // Switch to new type and restore its previous state
        const savedState = authorizationStates.get(newValue);
        const restoredAuth = savedState ? { ...savedState } : defaultAuthStates[newValue];

        authorization.value = restoredAuth;
        selectedType.value = newValue;
    };

    const updateCurrentAuthorizationValue = (
        newValue: string | number | { username: string; password: string },
    ) => {
        authorization.value.value = newValue;
    };

    /**
     * Saves current authorization state to the request store.
     *
     * Persists the current authorization configuration to the request store
     * for execution and updates the local state cache.
     */
    const saveAuthorizationToStore = (): void => {
        if (!authorization.value) {
            return;
        }

        // Update local state cache
        authorizationStates.set(authorization.value.type, {
            ...authorization.value,
        });

        // Save to request store
        if (requestStore.pendingRequestData) {
            requestStore.updateAuthorization(authorization.value);
        }
    };

    /*
     * Watchers.
     */

    watch(selectedType, updateAuthorizationType);

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
