import ValueGenerator from '@/components/common/ValueGenerator/ValueGenerator.vue';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { fireEvent } from '@testing-library/vue';
import { Mock } from '@vitest/spy';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

const restoreScrollPosition = vi.fn(() => Promise.resolve());

const mockStore = reactive<{
    isCommandOpen: boolean;
    currentInputRef: HTMLElement | null;
    generateValue: Mock;
    closeCommand: Mock;
    openCommand: Mock;
    commandState: { recentGenerators: string[] };
    recentGenerators: string[];
    restoreCommandState: Mock;
}>({
    isCommandOpen: false,
    currentInputRef: null,
    generateValue: vi.fn(),
    closeCommand: vi.fn(),
    openCommand: vi.fn(),
    commandState: { recentGenerators: [] },
    recentGenerators: [],
    restoreCommandState: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useValueGeneratorStore: () => mockStore,
    };
});

vi.mock('@/composables/ui/useTabHorizontalScroll', () => ({
    useTabHorizontalScroll: () => ({
        restoreScrollPosition,
    }),
}));

vi.mock('@/components/common/ValueGenerator/ValueGeneratorGeneratorList.vue', () => ({
    default: {
        name: 'ValueGeneratorGeneratorList',
        emits: ['generator-selected'],
        template:
            '<button data-testid="trigger-generator" @click="$emit(\'generator-selected\', \'email\')">Generate</button>',
    },
}));

describe('ValueGenerator', () => {
    beforeEach(() => {
        mockStore.isCommandOpen = false;
        mockStore.currentInputRef = null;
        mockStore.generateValue.mockReset();
        mockStore.closeCommand.mockReset();
        mockStore.restoreCommandState.mockReset();
    });

    it('does not render overlay when command is closed', () => {
        renderWithProviders(ValueGenerator);

        expect(screen.queryByTestId('value-generator-overlay')).toBeNull();
    });

    it('opens overlay and focuses command input when store toggles', async () => {
        renderWithProviders(ValueGenerator);

        mockStore.isCommandOpen = true;

        await nextTick();

        const overlay = await screen.findByTestId('value-generator-overlay');

        expect(overlay).toBeInTheDocument();
        expect(restoreScrollPosition).toHaveBeenCalled();
        expect(screen.getByPlaceholderText('Search generators...')).toHaveFocus();
    });

    it('closes when backdrop is clicked', async () => {
        renderWithProviders(ValueGenerator);

        mockStore.isCommandOpen = true;

        await nextTick();

        const overlay = await screen.findByTestId('value-generator-overlay');

        await fireEvent.click(overlay);

        expect(mockStore.closeCommand).toHaveBeenCalled();
    });

    it('propagates generated values to inputs and emits event', async () => {
        const onValueGenerated = vi.fn();
        const input = document.createElement('input');

        mockStore.generateValue.mockReturnValue('generated-value');
        mockStore.currentInputRef = input;

        renderWithProviders(ValueGenerator, {
            props: { onValueGenerated },
        });

        mockStore.isCommandOpen = true;

        await nextTick();

        const trigger = await screen.findByTestId('trigger-generator');

        await fireEvent.click(trigger);

        expect(mockStore.generateValue).toHaveBeenCalledWith('email');
        expect(input.value).toBe('generated-value');
        expect(onValueGenerated).toHaveBeenCalledWith('generated-value');
        expect(mockStore.closeCommand).toHaveBeenCalled();
    });

    it('closes command when escape is pressed inside command container', async () => {
        renderWithProviders(ValueGenerator);

        mockStore.isCommandOpen = true;

        await nextTick();

        const focusHookContainer = await screen.findByTestId(
            'value-generator-focus-hook',
        );

        await fireEvent.keyDown(focusHookContainer, { key: 'Escape' });

        expect(mockStore.closeCommand).toHaveBeenCalled();
    });
});
