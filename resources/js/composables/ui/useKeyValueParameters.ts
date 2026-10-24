import { useConfirmationAction } from '@/composables/ui/useConfirmationAction';
import { keyValueParametersConfig } from '@/config';
import type { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { useCounter } from '@vueuse/core';
import { type ComputedRef, type Ref, computed, onBeforeMount, ref, watch } from 'vue';

export interface UseKeyValueParametersResult<T extends ParameterContract> {
    parameters: Ref<T[]>;
    deletingAll: ComputedRef<boolean>;
    areAllParametersDisabled: ComputedRef<boolean>;
    addNewEmptyParameter: () => void;
    toggleAllParametersEnabledState: () => void;
    triggerParameterDeletion: (index: number) => void;
    deleteAllParameters: () => void;
    isParameterMarkedForDeletion: (id: number) => boolean;
    clearAllDeletionStates: () => void;
}

/**
 * Manages key-value parameter state with unidirectional data flow.
 *
 * @param modelValue - The current parameters from the parent (read-only)
 * @param onUpdate - Callback to notify parent of parameter changes
 */
export const useKeyValueParameters = <T extends ParameterContract>(
    modelValue: Ref<T[]>,
    onUpdate: (parameters: T[]) => void,
): UseKeyValueParametersResult<T> => {
    /*
     * Dependencies.
     */

    const { count: nextParameterId, inc: incrementParametersId } = useCounter();

    /*
     * State.
     */

    const parameters: Ref<T[]> = ref([]);

    const {
        isConfirming,
        trigger: triggerConfirmation,
        cancelAll: clearAllDeletionStates,
    } = useConfirmationAction({
        duration: keyValueParametersConfig.DELETION_CONFIRMATION_TIMEOUT,
    });

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

    const BULK_DELETION_KEY = 'all';

    const isParameterMarkedForDeletion = (parameterId: number): boolean =>
        isConfirming(parameterId);

    const hasSameContent = (a: T[], b: T[]): boolean =>
        a.length === b.length &&
        a.every(
            (row, index) =>
                row.key === b[index].key &&
                row.value === b[index].value &&
                row.type === b[index].type &&
                row.enabled === b[index].enabled,
        );

    /**
     * Reconciliation logic to update internal parameters from the parent modelValue.
     *
     * This replaces the current parameters with the ones from the modelValue,
     * but tries to preserve existing IDs for keys that haven't changed to maintain reactivity/focus.
     */
    const updateParametersFromParentModel = (): void => {
        const incoming = modelValue.value ?? [];

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

        // Skip the update when only ids differ, otherwise our own echo would loop back as a change.
        if (!hasSameContent(parameters.value, nextParameters)) {
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

    const deletingAll = computed(() => isConfirming(BULK_DELETION_KEY));

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

        triggerConfirmation(() => parameters.value.splice(index, 1), index);
    };

    /**
     * Handles two-step deletion confirmation for all parameters.
     *
     * Prevents accidental bulk deletion by requiring confirmation within the configured timeout.
     */
    const deleteAllParameters = (): void => {
        triggerConfirmation(() => {
            parameters.value = [];
        }, BULK_DELETION_KEY);
    };

    /*
     * Watchers.
     */

    // Not debounced, because an edit pending during a tab switch would be lost.
    watch(parameters, notifyParentOfChanges, { deep: true });

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

        // Utilities
        isParameterMarkedForDeletion,
        clearAllDeletionStates,
    };
};
