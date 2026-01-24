import type { GeneratorCommandState } from '@/interfaces/ui';
import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

/**
 * Store for managing generator command UI state.
 *
 * Handles the command palette state, including open/close state,
 * current input reference, and command opening methods.
 */
export const useGeneratorCommandStore = defineStore('_generatorCommand', () => {
    /*
     * State.
     */

    const commandState = ref<GeneratorCommandState>({
        searchQuery: '',
        selectedCategory: null,
        recentGenerators: [],
    });

    const isCommandOpen = ref(false);
    const currentInputRef = ref<HTMLElement | null>(null);

    // Command open method tracking using WeakMap for memory efficiency
    const commandOpenMethods = new WeakMap<
        HTMLElement,
        ValueGeneratorCommandOpenMethod
    >();

    /*
     * Computed.
     */

    const wasOpenedViaShiftShift = computed(() => {
        if (!currentInputRef.value) {
            return false;
        }

        return (
            commandOpenMethods.get(currentInputRef.value) ===
            ValueGeneratorCommandOpenMethod.SHIFT_SHIFT
        );
    });

    /*
     * Actions.
     */

    /**
     * Opens the generator command with optional input reference and method.
     */
    const openCommand = (
        inputRef?: HTMLElement,
        method: ValueGeneratorCommandOpenMethod = ValueGeneratorCommandOpenMethod.CLICK,
    ) => {
        isCommandOpen.value = true;

        if (inputRef) {
            currentInputRef.value = inputRef;
            commandOpenMethods.set(inputRef, method);
        }
    };

    /**
     * Closes the generator command and clears input reference.
     */
    const closeCommand = () => {
        isCommandOpen.value = false;
        currentInputRef.value = null;
    };

    /**
     * Sets the search query for filtering generators.
     */
    const setSearchQuery = (query: string) => {
        commandState.value.searchQuery = query;
    };

    /**
     * Sets the selected category for filtering generators.
     */
    const setSelectedCategory = (categoryId: string | null) => {
        commandState.value.selectedCategory = categoryId;
    };

    /**
     * Adds a generator to the recent generators list.
     */
    const addToRecentGenerators = (generatorId: string) => {
        const recent = commandState.value.recentGenerators.filter(
            id => id !== generatorId,
        );

        recent.unshift(generatorId);
        commandState.value.recentGenerators = recent.slice(0, 2);
    };

    /**
     * Restores command state to the provided command instance.
     *
     * Only restores search query if command doesn't already have one,
     * preventing overwriting user input.
     */
    const restoreCommandState = (commandInstance: {
        filterState: { search: string };
    }) => {
        const hasStoreQuery = commandState.value.searchQuery;
        const hasNoCommandQuery = !commandInstance.filterState.search;

        if (hasStoreQuery && hasNoCommandQuery) {
            commandInstance.filterState.search = commandState.value.searchQuery;
        }
    };

    return {
        // State
        commandState,
        isCommandOpen,
        currentInputRef,

        // Computed
        wasOpenedViaShiftShift,

        // Actions
        openCommand,
        closeCommand,
        setSearchQuery,
        setSelectedCategory,
        addToRecentGenerators,
        restoreCommandState,
    };
});
