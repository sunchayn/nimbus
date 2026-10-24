import { authorizationConfig } from '@/config';
import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import { AuthorizationType, type AuthorizationTypeItem } from '@/interfaces/generated';
import { useRequestStore } from '@/stores';
import { type DeepReadonly, type Ref, computed, readonly, watch } from 'vue';

export interface UseRequestAuthorizationResult {
    authorization: DeepReadonly<Ref<AuthorizationContract>>;
    selectedType: Ref<AuthorizationType>;
    types: {
        special: readonly AuthorizationTypeItem[];
        traditional: readonly AuthorizationTypeItem[];
    };
    updateAuthorizationType: (newValue: AuthorizationType) => void;
    updateCurrentAuthorizationValue: (newValue: AuthorizationContract['value']) => void;
    saveAuthorizationToStore: () => void;
}

/**
 * Handles reactive authorization state for API requests.
 *
 * Centralizes authorization type selection, validation, and persistence to the request store.
 */
export const useRequestAuthorization = (): UseRequestAuthorizationResult => {
    /*
     * Dependencies.
     */

    const requestStore = useRequestStore();

    /*
     * Constants.
     */

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
            value: {
                username: '',
                password: '',
            },
        },
        [AuthorizationType.CurrentUser]: {
            type: AuthorizationType.CurrentUser,
        },
        [AuthorizationType.Impersonate]: {
            type: AuthorizationType.Impersonate,
            value: 0,
        },
    } as const;

    /*
     * State.
     */

    const authorizationStates = new Map<AuthorizationType, AuthorizationContract>();

    Object.entries(defaultAuthStates).forEach(([type, state]) => {
        authorizationStates.set(
            type as AuthorizationType,
            state as AuthorizationContract,
        );
    });

    /*
     * Computed.
     */

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
        authorizationStates.set(authorization.value.type, {
            ...authorization.value,
        });

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
        newValue: AuthorizationContract['value'],
    ) => {
        const currentAuth = requestStore.pendingRequestData?.authorization ?? {
            type: AuthorizationType.CurrentUser,
        };

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
        authorizationStates.set(authorization.value.type, {
            ...authorization.value,
        });
    };

    /*
     * Watchers.
     */

    watch(authorization, saveAuthorizationToStore, { deep: true });

    return {
        // State
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
};
