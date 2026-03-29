import KeyValueParameters from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { Ref } from 'vue';
import { computed, nextTick, ref } from 'vue';

/*
 * Fixtures.
 */

const parameters: Ref<
    Array<{
        id: string;
        key: string;
        value: string;
        enabled: boolean;
        type: string;
    }>
> = ref([]);

const deletingAll = ref(false);
const areAllDisabledRef = ref(false);

const addNewEmptyParameter = vi.fn();
const toggleAllParametersEnabledState = vi.fn();
const triggerParameterDeletion = vi.fn();
const deleteAllParameters = vi.fn();
const isParameterMarkedForDeletion = vi.fn(() => false);

const openCommand = vi.fn();
const closeCommand = vi.fn();

vi.mock('@/composables/ui/useKeyValueParameters', () => ({
    useKeyValueParameters: () => ({
        parameters,
        deletingAll,
        areAllParametersDisabled: computed(() => areAllDisabledRef.value),
        addNewEmptyParameter,
        toggleAllParametersEnabledState,
        triggerParameterDeletion,
        deleteAllParameters,
        isParameterMarkedForDeletion,
    }),
}));

vi.mock('@/composables/ui/useTabHorizontalScroll', () => ({
    useTabHorizontalScroll: () => ({
        scrollContainer: ref(null),
        showLeftMask: ref(false),
        showRightMask: ref(false),
        updateScrollMasks: vi.fn(),
        restoreScrollPosition: vi.fn(() => Promise.resolve()),
    }),
}));

const mockActiveVariables = ref([
    {
        key: 'resolvedKey',
        value: 'someValue',
        enabled: true,
    },
]);

vi.mock('@/stores/core/useEnvironmentVariablesStore', () => ({
    useEnvironmentVariablesStore: () => ({
        resolve: vi.fn(val => val),
        getSegments: vi.fn(val => {
            if (val === '{{resolvedKey}}') {
                return [
                    { isEnvVariable: true, status: 'resolved', text: '{{resolvedKey}}' },
                ];
            }
            if (val === '{{missingKey}}') {
                return [
                    { isEnvVariable: true, status: 'missing', text: '{{missingKey}}' },
                ];
            }

            return [{ isEnvVariable: false, text: val }];
        }),
    }),
}));

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useValueGeneratorStore: () => ({
            openCommand,
            closeCommand,
        }),
        useEnvironmentVariablesStore: () => {
            const variablesEntries: [string, string][] = mockActiveVariables.value
                .filter(v => v.enabled)
                .map(v => [v.key, v.value]);

            return {
                activeCollection: {
                    variables: mockActiveVariables.value,
                },
                variables: new Map(variablesEntries),
            };
        },
    };
});

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(KeyValueParameters, {
        props: {
            modelValue: [],
            // @ts-expect-error .props not found in object.
            ...(options.props || {}),
        },
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
        ...options,
    });
};

describe('KeyValueParameters', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        parameters.value = [
            {
                id: '1',
                key: 'test-key',
                value: 'test-value',
                enabled: true,
                type: 'text',
            },
            {
                id: '2',
                key: 'another-key',
                value: 'another-value',
                enabled: false,
                type: 'text',
            },
        ];
        deletingAll.value = false;
        areAllDisabledRef.value = false;

        vi.clearAllMocks();
        addNewEmptyParameter.mockClear();
        toggleAllParametersEnabledState.mockClear();
        triggerParameterDeletion.mockClear();
        deleteAllParameters.mockClear();
        isParameterMarkedForDeletion.mockReset();
        openCommand.mockClear();
        closeCommand.mockClear();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders parameters and header controls', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.find('[data-testid="kv-container"]').exists()).toBe(true);
            expect(wrapper.findAll('[data-testid="parameter-row"]')).toHaveLength(2);
            expect(wrapper.find('[data-testid="add-button"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="enable-all-button"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="delete-all-button"]').exists()).toBe(true);
        });

        it('shows type selector when freeFormTypes enabled', () => {
            // Arrange

            const wrapper = createWrapper({
                props: { freeFormTypes: true },
            });

            // Assert

            expect(wrapper.findAll('[data-testid="type-selector"]')).toHaveLength(2);
        });

        it('applies correct status classes based on environment variable resolution', async () => {
            // Arrange
            parameters.value = [
                {
                    id: '1',
                    key: 'key1',
                    value: '{{resolvedKey}}',
                    enabled: true,
                    type: 'text',
                },
                {
                    id: '2',
                    key: 'key2',
                    value: '{{missingKey}}',
                    enabled: true,
                    type: 'text',
                },
            ];

            const wrapper = createWrapper();
            await nextTick();

            // Assert
            const rows = wrapper.findAll('[data-testid="parameter-row"]');
            const row1Segment = rows[0].find('[data-segment-index]');
            const row2Segment = rows[1].find('[data-segment-index]');

            expect(row1Segment.classes()).toContain('text-primary'); // Resolved
            expect(row2Segment.classes()).toContain('text-destructive'); // Missing
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('invokes composable actions for header buttons', async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act

            await wrapper.find('[data-testid="add-button"]').trigger('click');
            await wrapper.find('[data-testid="enable-all-button"]').trigger('click');
            await wrapper.find('[data-testid="delete-all-button"]').trigger('click');

            // Assert

            expect(addNewEmptyParameter).toHaveBeenCalled();
            expect(toggleAllParametersEnabledState).toHaveBeenCalled();
            expect(deleteAllParameters).toHaveBeenCalled();
        });

        it('updates enable button label based on disabled state', async () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.find('[data-testid="enable-all-button"]').text()).toBe(
                'Disable All',
            );

            // Act

            areAllDisabledRef.value = true;
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="enable-all-button"]').text()).toBe(
                'Enable All',
            );
        });

        it('displays generator button while value input focused and opens command', async () => {
            // Arrange

            const wrapper = createWrapper();
            const valueInputs = wrapper.findAll('input');
            const valueInput = valueInputs[1]; // Index 1 is the value input

            // Act

            await valueInput.trigger('focus');
            await nextTick();

            const generatorButton = wrapper.find('[data-testid="generator-button"]');
            await generatorButton.trigger('mousedown');

            // Assert

            expect(openCommand).toHaveBeenCalledWith(valueInput.element);
        });

        it('keeps generator open when blur moves into generator palette', async () => {
            // Arrange

            const wrapper = createWrapper();
            const valueInput = wrapper.find('input[name="kv-value"]');

            // Act

            await valueInput.trigger('focus');

            const relatedTarget = document.createElement('div');
            relatedTarget.setAttribute('data-ValueGenerator-focus-hook', '');

            await valueInput.trigger('blur', { relatedTarget });

            // Assert

            expect(closeCommand).not.toHaveBeenCalled();
        });
    });

    /*
     * Edge Cases.
     */

    describe('Edge Cases', () => {
        it('marks delete button when parameter flagged for deletion', () => {
            // Arrange

            isParameterMarkedForDeletion
                .mockReturnValueOnce(true) // <- First Parameter.
                .mockReturnValueOnce(false); // <- Second Parameter.

            const wrapper = createWrapper();
            const rows = wrapper.findAll('[data-testid="parameter-row"]');

            // Assert

            const firstDeleteButton = rows[0].get('[data-testid="delete-button"]');
            const secondDeleteButton = rows[1].get('[data-testid="delete-button"]');

            expect(firstDeleteButton.find('svg').classes()).toContain('text-destructive');
            expect(secondDeleteButton.find('svg').classes()).not.toContain(
                'text-rose-500',
            );
        });
    });
});
