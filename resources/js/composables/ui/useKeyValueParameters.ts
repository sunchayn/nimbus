import { keyValueParametersConfig } from '@/config';
import type { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { useCounter, watchDebounced } from '@vueuse/core';
import {
    type ComputedRef,
    type Ref,
    computed,
    onBeforeMount,
    reactive,
    ref,
    watch,
} from 'vue';

export interface UseKeyValueParametersResult<T extends ParameterContract> {
    parameters: Ref<T[]>;
    deletingAll: ComputedRef<boolean>;
    areAllParametersDisabled: ComputedRef<boolean>;
    addNewEmptyParameter: () => void;
    toggleAllParametersEnabledState: () => void;
    triggerParameterDeletion: (index: number) => void;
    deleteAllParameters: () => void;
    updateParametersFromParentModel: () => void;
    isParameterMarkedForDeletion: (id: number) => boolean;
    clearAllDeletionStates: () => void;
}

/**
 * Manages key-value parameter state with unidirectional data flow.
 *
 * @param modelValue - The current parameters from the parent (read-only)
 * @param onUpdate - Callback to notify parent of parameter changes
 */
export function useKeyValueParameters<T extends ParameterContract>(
    modelValue: Ref<T[]>,
    onUpdate: (parameters: T[]) => void,
): UseKeyValueParametersResult<T> {
    /*
     * Dependencies.
     */

    const { count: nextParameterId, inc: incrementParametersId } = useCounter();

    /*
     * State.
     */

    const parameters: Ref<T[]> = ref([]);

    interface DeletionState {
        deleting: boolean;
        timeoutId?: number;
    }

    const deletionStatesForParameters = reactive(new Map<number, DeletionState>());
    const bulkDeletionState: Ref<DeletionState> = ref({ deleting: false });

    /*
     * Utilities.
     */

    const createParameterSkeleton = (id: number): ParameterContract => ({
        type: ParameterType.Text,
        id,
        key: '',
        value: '',
        enabled: true,
    });

    /**
     * Initiates deletion confirmation for a parameter.
     * Returns true if this is the confirmation click (second click).
     */
    const initiateParameterDeletion = (identifier: number): boolean => {
        const state = deletionStatesForParameters.get(identifier);

        if (state?.deleting) {
            clearParameterDeletionState(identifier);

            return true;
        }

        setParameterDeletionState(identifier);

        return false;
    };

    /**
     * Checks if a parameter is marked for deletion.
     */
    const isParameterMarkedForDeletion = (parameterId: number): boolean => {
        return deletionStatesForParameters.get(parameterId)?.deleting ?? false;
    };

    /**
     * Clears deletion state for a parameter.
     */
    const clearParameterDeletionState = (parameterId: number): void => {
        const state = deletionStatesForParameters.get(parameterId);

        if (state?.timeoutId) {
            clearTimeout(state.timeoutId);
        }

        deletionStatesForParameters.delete(parameterId);
    };

    /**
     * Sets deletion state with automatic timeout.
     */
    const setParameterDeletionState = (parameterId: number): void => {
        const timeoutId = window.setTimeout(() => {
            deletionStatesForParameters.delete(parameterId);
        }, keyValueParametersConfig.DELETION_CONFIRMATION_TIMEOUT);

        deletionStatesForParameters.set(parameterId, {
            deleting: true,
            timeoutId,
        });
    };

    /**
     * Initiates bulk deletion confirmation.
     * Returns true if this is the confirmation click (second click).
     */
    const initiateBulkDeletion = (): boolean => {
        if (bulkDeletionState.value.deleting) {
            clearBulkDeletionState();

            return true;
        }

        markForBulkDeletion();

        return false;
    };

    /**
     * Checks if bulk deletion is marked for confirmation.
     */
    const isBulkDeletionMarked = (): boolean => {
        return bulkDeletionState.value.deleting;
    };

    /**
     * Clears bulk deletion state.
     */
    const clearBulkDeletionState = (): void => {
        if (bulkDeletionState.value.timeoutId) {
            clearTimeout(bulkDeletionState.value.timeoutId);
        }

        bulkDeletionState.value = { deleting: false };
    };

    /**
     * Sets bulk deletion state with automatic timeout.
     */
    const markForBulkDeletion = (): void => {
        const timeoutId = window.setTimeout(() => {
            bulkDeletionState.value = { deleting: false };
        }, keyValueParametersConfig.DELETION_CONFIRMATION_TIMEOUT);

        bulkDeletionState.value = { deleting: true, timeoutId };
    };

    /**
     * Clears all deletion states.
     */
    const clearAllDeletionStates = (): void => {
        deletionStatesForParameters.forEach((_, id) => clearParameterDeletionState(id));
        clearBulkDeletionState();
    };

    /**
     * Reconciliation logic to update internal parameters from the parent modelValue.
     *
     * This replaces the current parameters with the ones from the modelValue,
     * but tries to preserve existing IDs for keys that haven't changed to maintain reactivity/focus.
     */
    const updateParametersFromParentModel = (): void => {
        const incoming = modelValue.value ?? [];

        // Map current parameters by id for reconciliation
        const currentById = new Map(
            parameters.value.map(parameter => [parameter.id, parameter]),
        );

        const nextParameters: T[] = incoming.map(external => {
            const existing = currentById.get(external.id);

            if (existing) {
                // Create a new object instead of mutating the existing one
                // This prevents shared references between history and active state
                return {
                    ...existing,
                    id: external.id,
                    key: external.key,
                    value: external.value,
                    type: external.type,
                    enabled: external.enabled,
                } as T;
            }

            incrementParametersId();

            return { id: nextParameterId.value, ...external } as T;
        }) as T[];

        // If internal state is empty, we must ensure at least one skeleton
        if (nextParameters.length === 0) {
            incrementParametersId();

            nextParameters.push(createParameterSkeleton(nextParameterId.value) as T);
        }

        // Only update if the resulting content is different from current internal state
        // to avoid triggering redundant observers.
        if (JSON.stringify(parameters.value) !== JSON.stringify(incoming)) {
            parameters.value = nextParameters;
        }
    };

    /**
     * Notifies parent of parameter changes with deep cloned data.
     * This prevents shared references and ensures unidirectional data flow.
     */
    const notifyParentOfChanges = (): void => {
        // Deep clone to prevent shared references
        const clonedParameters = parameters.value.map(p => ({
            id: p.id,
            type: p.type,
            key: p.key,
            value: p.value,
            enabled: p.enabled,
        }));

        onUpdate(clonedParameters as T[]);
    };

    /*
     * Computed.
     */

    const areAllParametersDisabled = computed(() =>
        parameters.value.every(parameter => !parameter.enabled),
    );

    const deletingAll = computed(() => isBulkDeletionMarked());

    /*
     * Actions.
     */

    /**
     * Adds a new empty parameter to the list for user input.
     */
    const addNewEmptyParameter = (): void => {
        incrementParametersId();

        const newParameter = createParameterSkeleton(nextParameterId.value);

        parameters.value.push(newParameter as T);
    };

    /**
     * Toggles all parameters between enabled/disabled states.
     */
    const toggleAllParametersEnabledState = () => {
        const shouldEnableAll = areAllParametersDisabled.value;

        parameters.value.forEach(
            (parameter: ParameterContract) => (parameter.enabled = shouldEnableAll),
        );
    };

    /**
     * Handles two-step deletion confirmation for individual parameters.
     *
     * Prevents accidental deletions by requiring a second click within the configured timeout.
     * First click marks for deletion, second click removes immediately.
     */
    const triggerParameterDeletion = (index: number): void => {
        const parameter = parameters.value[index];

        if (!parameter) {
            return;
        }

        const shouldDelete = initiateParameterDeletion(index);

        if (!shouldDelete) {
            return;
        }

        parameters.value.splice(index, 1);
    };

    /**
     * Handles two-step deletion confirmation for all parameters.
     *
     * Prevents accidental bulk deletion by requiring confirmation within the configured timeout.
     */
    const deleteAllParameters = (): void => {
        const shouldDelete = initiateBulkDeletion();

        if (!shouldDelete) {
            return;
        }

        parameters.value = [];
    };

    /*
     * Watchers.
     */

    // Watch for internal changes to notify parent
    watchDebounced(
        parameters,
        () => {
            notifyParentOfChanges();
        },
        {
            deep: true,
            debounce: keyValueParametersConfig.SYNC_DEBOUNCE_DELAY,
        },
    );

    // Watch for external changes to sync from parent.
    watch(
        modelValue,
        () => {
            updateParametersFromParentModel();
        },
        { deep: true },
    );

    /*
     * Lifecycle.
     */

    // Initialize parameters from parent model
    onBeforeMount(() => {
        updateParametersFromParentModel();

        if (parameters.value.length === 0) {
            addNewEmptyParameter();
        }
    });

    return {
        // State
        parameters,
        deletingAll,
        areAllParametersDisabled,

        // Actions
        addNewEmptyParameter,
        toggleAllParametersEnabledState,
        triggerParameterDeletion,
        deleteAllParameters,
        updateParametersFromParentModel,

        // Utilities
        isParameterMarkedForDeletion,
        clearAllDeletionStates,
    };
}
