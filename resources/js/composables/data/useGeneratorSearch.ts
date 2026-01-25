import type { ValueGenerator } from '@/interfaces/ui';
import {
    type ComputedRef,
    type DeepReadonly,
    type Ref,
    computed,
    readonly,
    ref,
} from 'vue';

export interface UseGeneratorSearchResult {
    searchQuery: DeepReadonly<Ref<string>>;
    selectedCategory: DeepReadonly<Ref<string | null>>;
    filteredGenerators: ComputedRef<ValueGenerator[]>;
    hasActiveFilters: ComputedRef<boolean>;
    setSearchQuery: (query: string) => void;
    setSelectedCategory: (categoryId: string | null) => void;
    clearFilters: () => void;
}

/**
 * Composable for filtering and searching value generators.
 *
 * Provides reactive filtering capabilities for generator lists,
 * supporting both text search and category filtering.
 */
export function useGeneratorSearch(
    generators: ValueGenerator[],
): UseGeneratorSearchResult {
    /*
     * State.
     */

    const searchQuery = ref('');
    const selectedCategory = ref<string | null>(null);

    /*
     * Computed.
     */

    /**
     * Filters generators based on search query and selected category.
     *
     * Performs case-insensitive search across generator name and description,
     * and optionally filters by category if one is selected.
     */
    const filteredGenerators = computed(() => {
        let filtered = generators;

        // Apply search filter if there's a query
        if (searchQuery.value) {
            const query = searchQuery.value.toLowerCase();

            filtered = filtered.filter(
                (generator: ValueGenerator) =>
                    generator.name.toLowerCase().includes(query) ||
                    generator.description.toLowerCase().includes(query),
            );
        }

        // Apply category filter if a category is selected
        if (selectedCategory.value) {
            filtered = filtered.filter(
                (generator: ValueGenerator) =>
                    generator.category.id === selectedCategory.value,
            );
        }

        return filtered;
    });

    /**
     * Checks if any filters are currently active.
     */
    const hasActiveFilters = computed(() => {
        return searchQuery.value !== '' || selectedCategory.value !== null;
    });

    /*
     * Actions.
     */

    /**
     * Sets the search query for filtering generators.
     */
    const setSearchQuery = (query: string) => {
        searchQuery.value = query;
    };

    /**
     * Sets the selected category for filtering generators.
     */
    const setSelectedCategory = (categoryId: string | null) => {
        selectedCategory.value = categoryId;
    };

    /**
     * Clears all active filters.
     */
    const clearFilters = () => {
        searchQuery.value = '';
        selectedCategory.value = null;
    };

    return {
        // State
        searchQuery: readonly(searchQuery),
        selectedCategory: readonly(selectedCategory),

        // Computed
        filteredGenerators,
        hasActiveFilters,

        // Actions
        setSearchQuery,
        setSelectedCategory,
        clearFilters,
    };
}
