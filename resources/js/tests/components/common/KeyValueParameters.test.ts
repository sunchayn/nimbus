import KeyValueParameters from '@/components/common/KeyValueParameters/KeyValueParameters.vue';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { fireEvent, getByTestId } from '@testing-library/vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, nextTick, Ref, ref } from 'vue';

const parameters: Ref<
    Array<{
        id: string;
        key: string;
        value: string;
        enabled: boolean;
        type: string;
    }>
> = ref([
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
]);

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

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useValueGeneratorStore: () => ({
            openCommand,
            closeCommand,
        }),
    };
});

const renderComponent = (props: Record<string, unknown> = {}) =>
    renderWithProviders(KeyValueParameters, {
        props: {
            modelValue: [],
            ...props,
        },
    });

describe('KeyValueParameters', () => {
    beforeEach(() => {
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

        addNewEmptyParameter.mockClear();
        toggleAllParametersEnabledState.mockClear();
        triggerParameterDeletion.mockClear();
        deleteAllParameters.mockClear();
        isParameterMarkedForDeletion.mockReset();
        openCommand.mockClear();
        closeCommand.mockClear();
    });

    it('renders parameters and header controls', () => {
        renderComponent();

        expect(screen.getByTestId('kv-container')).toBeInTheDocument();
        expect(screen.getAllByTestId('parameter-row')).toHaveLength(2);
        expect(screen.getByTestId('add-button')).toBeInTheDocument();
        expect(screen.getByTestId('enable-all-button')).toBeInTheDocument();
        expect(screen.getByTestId('delete-all-button')).toBeInTheDocument();
    });

    it('shows type selector when freeFormTypes enabled', () => {
        renderComponent({ freeFormTypes: true });

        expect(screen.getAllByTestId('type-selector')).toHaveLength(2);
    });

    it('invokes composable actions for header buttons', async () => {
        renderComponent();

        await fireEvent.click(screen.getByTestId('add-button'));

        await fireEvent.click(screen.getByTestId('enable-all-button'));

        await fireEvent.click(screen.getByTestId('delete-all-button'));

        expect(addNewEmptyParameter).toHaveBeenCalled();
        expect(toggleAllParametersEnabledState).toHaveBeenCalled();
        expect(deleteAllParameters).toHaveBeenCalled();
    });

    it('updates enable button label based on disabled state', async () => {
        renderComponent();

        expect(screen.getByTestId('enable-all-button')).toHaveTextContent('Disable All');

        areAllDisabledRef.value = true;

        await nextTick();

        expect(screen.getByTestId('enable-all-button')).toHaveTextContent('Enable All');
    });

    it('displays generator button while value input focused and opens command', async () => {
        renderComponent();

        const valueInputs = screen.getAllByTestId('kv-value');

        await fireEvent.focus(valueInputs[0]);

        await nextTick();

        const generatorButton = screen.getByTestId('generator-button');

        await fireEvent.mouseDown(generatorButton);

        expect(openCommand).toHaveBeenCalledWith(valueInputs[0]);
    });

    it('keeps generator open when blur moves into generator palette', async () => {
        renderComponent();

        const valueInput = screen.getAllByTestId('kv-value')[0];

        await fireEvent.focus(valueInput);

        const relatedTarget = document.createElement('div');
        relatedTarget.setAttribute('data-ValueGenerator-focus-hook', '');

        await fireEvent.blur(valueInput, { relatedTarget });

        expect(closeCommand).not.toHaveBeenCalled();
    });

    it('marks delete button when parameter flagged for deletion', () => {
        isParameterMarkedForDeletion
            .mockReturnValueOnce(true) // <- First Parameter.
            .mockReturnValueOnce(false); // <- Second Parameter.

        renderComponent();

        const rows = screen.getAllByTestId('parameter-row');

        const firstDeleteIcon = getByTestId(rows[0], 'delete-button').querySelector(
            'svg',
        );
        const secondDeleteIcon = getByTestId(rows[1], 'delete-button').querySelector(
            'svg',
        );

        expect(firstDeleteIcon?.classList).toContain('text-rose-500');
        expect(secondDeleteIcon?.classList ?? '').not.toContain('text-rose-500');
    });
});
