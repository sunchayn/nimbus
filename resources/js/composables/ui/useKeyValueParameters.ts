import { keyValueParametersConfig } from '@/config';
import { ExtendedParameter, ParametersExternalContract } from '@/interfaces/ui';
import { uniquePersistenceKey } from '@/utils/stores';
import { useCounter, useStorage, watchDebounced } from '@vueuse/core';
import { RemovableRef } from '@vueuse/shared';
import { computed, onBeforeMount, reactive, ref, Ref } from 'vue';

/**
 * Manages key-value parameter state.
 */
export function useKeyValueParameters(
    model: Ref<ParametersExternalContract[]>,
    persistenceKey?: string,
) {
    const { count: nextParameterId, inc: incrementParametersId } = useCounter();

    const parameters: RemovableRef<ExtendedParameter[]> | Ref<ExtendedParameter[]> =
        persistenceKey ? useStorage(uniquePersistenceKey(persistenceKey), []) : ref([]);

    const isUpdatingFromParentModel = ref(false);

    const createParameterSkeleton = (id: number): ExtendedParameter => ({
        type: 'text',
        id,
        key: '',
        value: '',
        enabled: true,
    });

    const convertExternalToInternal = (
        external: ParametersExternalContract,
        id: number,
    ): ExtendedParameter => ({
        type: external.type ?? 'text',
        id,
        key: external.key,
        value: String(external.value ?? ''),
        enabled: true,
    });

    /**
     * Converts internal UI parameter data back to external format.
     *
     * Removes UI-specific properties to provide clean data for external clients.
     */
    const convertInternalToExternal = (
        internal: ExtendedParameter,
    ): ParametersExternalContract => ({
        type: internal.type,
        key: internal.key,
        value: internal.value,
    });

    /**
     * Creates a Map for efficient parameter lookup by key.
     *
     * Used for O(1) duplicate detection instead of O(n²) nested loops.
     */
    const createParameterKeyMap = (
        parameters: ExtendedParameter[],
    ): Map<string, ExtendedParameter> => {
        return new Map(parameters.map(parameter => [parameter.key, parameter]));
    };

    /**
     * Finds duplicate parameters efficiently using Map lookup.
     *
     * Returns parameters from incoming array that have keys matching existing parameters.
     */
    const findDuplicateParameters = (
        existing: ExtendedParameter[],
        incoming: ExtendedParameter[],
    ): ExtendedParameter[] => {
        const existingKeyMap = createParameterKeyMap(existing);

        return incoming.filter(param => existingKeyMap.has(param.key));
    };

    /**
     * Removes parameters with duplicate keys from existing array.
     *
     * Filters out parameters whose keys exist in the duplicates array.
     */
    const removeDuplicateParameters = (
        existing: ExtendedParameter[],
        duplicates: ExtendedParameter[],
    ): ExtendedParameter[] => {
        const duplicateKeys = new Set(duplicates.map(param => param.key));

        return existing.filter(param => !duplicateKeys.has(param.key));
    };

    /*
     * Deletion state management.
     */

    interface DeletionState {
        deleting: boolean;
        timeoutId?: number;
    }

    const deletionStatesForParameters = reactive(new Map<number, DeletionState>());

    const bulkDeletionState: Ref<DeletionState> = ref({ deleting: false });

    /**
     * Initiates deletion confirmation for a parameter.
     * Returns true if this is the confirmation click (second click).
     */
    const initiateParameterDeletion = (parameterId: number): boolean => {
        const state = deletionStatesForParameters.get(parameterId);

        if (state?.deleting) {
            clearParameterDeletionState(parameterId);

            return true;
        }

        setParameterDeletionState(parameterId);

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
        const timeoutId: number = window.setTimeout(() => {
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

    /*
     * Computed.
     */

    const areAllParametersDisabled = computed(() =>
        parameters.value.every(parameter => !parameter.enabled),
    );

    const deletingAll = computed(() => isBulkDeletionMarked());

    // Sync changes back to parent model with debouncing to avoid excessive updates.
    // Skip syncing when we are applying updates that originate from the parent model.
    watchDebounced(
        parameters,
        () => {
            if (isUpdatingFromParentModel.value) {
                return;
            }

            syncParametersBackToModel();
        },
        {
            deep: true,
            debounce: keyValueParametersConfig.SYNC_DEBOUNCE_DELAY,
        },
    );

    // Initialize parameters from parent model
    onBeforeMount(() => {
        updateParametersFromParentModel();

        if (parameters.value.length === 0) {
            addNewEmptyParameter();
        }
    });

    /*
     * Actions.
     */

    /**
     * Expands minimal external parameters to full internal structure for command support.
     *
     * Bridges external client data with internal parameter state by adding UI-specific
     * properties like IDs, enabled state, and deletion tracking.
     */
    const expandExternalParameters = (
        externalParameters: ParametersExternalContract[],
    ): ExtendedParameter[] => {
        return externalParameters.map(
            (externalEntity: ParametersExternalContract): ExtendedParameter => {
                incrementParametersId();

                return convertExternalToInternal(externalEntity, nextParameterId.value);
            },
        );
    };

    /**
     * Merges new parameters with existing ones, removing duplicates
     */
    const mergeParametersWithoutDuplicates = (
        newParameters: ExtendedParameter[],
        existingParameters: ExtendedParameter[],
    ): ExtendedParameter[] => {
        const duplicates = findDuplicateParameters(existingParameters, newParameters);

        const cleanedExisting = removeDuplicateParameters(existingParameters, duplicates);

        return [...newParameters, ...cleanedExisting];
    };

    /**
     * Syncs parameters from parent model back to the component while preserving existing parameters.
     *
     * Parent parameters override existing ones with matching keys to prevent duplicates,
     * but we preserve user-added parameters that don't conflict.
     */
    const updateParametersFromParentModel = (): void => {
        if (!model.value?.length) {
            return;
        }

        // Suppress outbound sync while we incorporate parent-provided parameters to prevent
        // a feedback loop where our update triggers a debounced write back to the parent.
        isUpdatingFromParentModel.value = true;

        const newParameters = expandExternalParameters(model.value);

        parameters.value = mergeParametersWithoutDuplicates(
            newParameters,
            parameters.value,
        );

        // Clear the suppression after the debounce window to ensure no stale writes occur.
        window.setTimeout(() => {
            isUpdatingFromParentModel.value = false;
        }, keyValueParametersConfig.SYNC_DEBOUNCE_DELAY + 10);
    };

    /**
     * Filters out empty keys and disabled items before syncing to the parent model.
     *
     * Ensures parent components receive only valid, enabled key-value pairs without UI-specific state.
     */
    const syncParametersBackToModel = (): void => {
        model.value = parameters.value
            .filter(parameter => parameter.key !== '' && parameter.enabled)
            .map(convertInternalToExternal);
    };

    /**
     * Adds a new empty parameter to the list for user input.
     */
    const addNewEmptyParameter = (): void => {
        incrementParametersId();

        const newParameter = createParameterSkeleton(nextParameterId.value);

        parameters.value.push(newParameter);
    };

    /**
     * Toggles all parameters between enabled/disabled states.
     */
    const toggleAllParametersEnabledState = () => {
        const shouldEnableAll = areAllParametersDisabled.value;

        parameters.value.forEach(
            (parameter: ExtendedParameter) => (parameter.enabled = shouldEnableAll),
        );
    };

    /**
     * Handles two-step deletion confirmation for individual parameters.
     *
     * Prevents accidental deletions by requiring a second click within the configured timeout.
     * First click marks for deletion, second click removes immediately.
     */
    const triggerParameterDeletion = (
        parameters: ExtendedParameter[],
        index: number,
    ): void => {
        const parameter = parameters[index];

        if (!parameter) {
            return;
        }

        const shouldDelete = initiateParameterDeletion(parameter.id);

        if (!shouldDelete) {
            return;
        }

        parameters.splice(index, 1);
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

    /**
     * Checks if a parameter is marked for deletion
     */
    const checkParameterDeletion = (id: number): boolean => {
        return isParameterMarkedForDeletion(id);
    };

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
        isParameterMarkedForDeletion: checkParameterDeletion,
        clearAllDeletionStates,
    };
}
