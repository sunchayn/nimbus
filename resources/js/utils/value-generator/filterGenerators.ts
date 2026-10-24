import type { ValueGenerator } from '@/interfaces/ui';

/**
 * Filters generators by a case-insensitive text query on name and description,
 * and optionally by category id.
 */
export function filterGenerators(
    generators: ValueGenerator[],
    searchQuery: string,
    selectedCategory: string | null,
): ValueGenerator[] {
    let filtered = generators;

    if (searchQuery) {
        const query = searchQuery.toLowerCase();

        filtered = filtered.filter(
            generator =>
                generator.name.toLowerCase().includes(query) ||
                generator.description.toLowerCase().includes(query),
        );
    }

    if (selectedCategory) {
        filtered = filtered.filter(
            generator => generator.category.id === selectedCategory,
        );
    }

    return filtered;
}
