import { useGeneratorSearch } from '@/composables/data/useGeneratorSearch';
import type { ValueGenerator } from '@/interfaces/ui';
import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import { useGeneratorCommandStore } from './useGeneratorCommandStore';
import { useValueGeneratorDefinitionsStore } from './useValueGeneratorDefinitionsStore';

/**
 * Unified store for value generator functionality.
 *
 * Combines generator definitions, command state, and search functionality
 * into a single cohesive interface while maintaining separation of concerns.
 */
export const useValueGeneratorStore = defineStore('valueGenerator', () => {
    /*
     * Stores & dependencies.
     */

    const generatorsStore = useValueGeneratorDefinitionsStore();
    const commandStore = useGeneratorCommandStore();

    // Local refs that sync with command store for proper reactivity
    const isCommandOpen = ref(false);
    const currentInputRef = ref<HTMLElement | null>(null);

    // Sync local refs with command store
    watch(
        () => commandStore.isCommandOpen,
        newValue => {
            isCommandOpen.value = newValue;
        },
        { immediate: true },
    );

    watch(
        () => commandStore.currentInputRef,
        newValue => {
            currentInputRef.value = newValue;
        },
        { immediate: true },
    );

    // Use the search composable with a function that returns the current generators
    const searchComposable = useGeneratorSearch(generatorsStore.generators);

    /*
     * Computed.
     */

    const recentGenerators = computed(() => {
        return commandStore.commandState.recentGenerators
            .map(id => generatorsStore.getGeneratorById(id))
            .filter(Boolean) as ValueGenerator[];
    });

    /*
     * Actions.
     */

    /**
     * Generates a value using the specified generator and adds it to recent list.
     */
    const generateValue = (generatorId: string): string | number | bigint => {
        const generator = generatorsStore.getGeneratorById(generatorId);
        if (!generator) {
            return '';
        }

        const value = generator.generate();

        commandStore.addToRecentGenerators(generatorId);

        return value;
    };

    /**
     * Opens the generator command with optional input reference and method.
     */
    const openCommand = (
        inputRef?: HTMLElement,
        method: Parameters<
            typeof commandStore.openCommand
        >[1] = ValueGeneratorCommandOpenMethod.CLICK,
    ) => {
        commandStore.openCommand(inputRef, method);
    };

    /**
     * Closes the generator command.
     */
    const closeCommand = () => {
        commandStore.closeCommand();
    };

    /**
     * Sets the search query for filtering generators.
     */
    const setSearchQuery = (query: string) => {
        commandStore.setSearchQuery(query);
        searchComposable.setSearchQuery(query);
    };

    /**
     * Sets the selected category for filtering generators.
     */
    const setSelectedCategory = (categoryId: string | null) => {
        commandStore.setSelectedCategory(categoryId);
        searchComposable.setSelectedCategory(categoryId);
    };

    /**
     * Restores command state to the provided command instance.
     */
    const restoreCommandState = (commandInstance: {
        filterState: { search: string };
    }) => {
        commandStore.restoreCommandState(commandInstance);
    };

    const wasOpenedViaShiftShift = computed(() => commandStore.wasOpenedViaShiftShift);

    return {
        // State from command store (using local synced refs).
        // TODO [Refactor] check if this proxying mess is still needed.
        isCommandOpen,
        currentInputRef,
        commandState: commandStore.commandState,

        // State from generators store
        categories: generatorsStore.categories,
        generators: generatorsStore.generators,

        // Computed
        filteredGenerators: searchComposable.filteredGenerators,
        recentGenerators,
        wasOpenedViaShiftShift,

        // Actions
        generateValue,
        openCommand,
        closeCommand,
        setSearchQuery,
        setSelectedCategory,
        restoreCommandState,
    };
});
