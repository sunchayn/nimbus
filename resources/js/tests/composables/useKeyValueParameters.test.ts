import { useKeyValueParameters } from '@/composables/ui/useKeyValueParameters';
import type { ParameterContract } from '@/interfaces';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Ref } from 'vue';
import { ref } from 'vue';

/*
 * Fixtures.
 */

// Mock the config
vi.mock('@/config', () => ({
    keyValueParametersConfig: {
        DELETION_CONFIRMATION_TIMEOUT: 2000,
        SYNC_DEBOUNCE_DELAY: 0,
    },
}));

describe('useKeyValueParameters', () => {
    let modelValue: Ref<ParameterContract[]>;
    let onUpdateCallback: ReturnType<typeof vi.fn>;
    let composable: ReturnType<typeof useKeyValueParameters>;

    beforeEach(() => {
        modelValue = ref([]);
        onUpdateCallback = vi.fn();
        composable = useKeyValueParameters(modelValue, onUpdateCallback);
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('should initialize with empty parameters array', () => {
            // Assert

            expect(composable.parameters.value).toEqual([]);
            expect(composable.areAllParametersDisabled.value).toBe(true);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Parameter Management', () => {
        it('should add new empty parameter', () => {
            // Act

            composable.addNewEmptyParameter();

            // Assert

            expect(composable.parameters.value).toHaveLength(1);
            expect(composable.parameters.value[0]).toMatchObject({
                type: ParameterType.Text,
                key: '',
                value: '',
                enabled: true,
            });
        });

        it('should toggle all parameters enabled state', () => {
            // Arrange

            composable.addNewEmptyParameter();
            composable.addNewEmptyParameter();
            expect(composable.areAllParametersDisabled.value).toBe(false);

            // Act

            composable.toggleAllParametersEnabledState();

            // Assert

            expect(composable.areAllParametersDisabled.value).toBe(true);
        });

        it('should update parameters from parent modelValue', () => {
            // Arrange

            modelValue.value = [
                {
                    id: 1,
                    type: ParameterType.Text,
                    key: 'p1',
                    value: 'v1',
                    enabled: true,
                },
            ];

            // Act

            composable.updateParametersFromParentModel();

            // Assert

            expect(composable.parameters.value).toHaveLength(1);
            expect(composable.parameters.value[0].key).toBe('p1');
        });
    });

    describe('Deletion Management', () => {
        it('should delete parameter on second click', () => {
            // Arrange

            composable.addNewEmptyParameter();
            const index = 0;

            // Act - First click
            composable.triggerParameterDeletion(index);
            expect(composable.isParameterMarkedForDeletion(index)).toBe(true);

            // Act - Second click
            composable.triggerParameterDeletion(index);

            // Assert
            expect(composable.parameters.value).toHaveLength(0);
        });
    });
});
