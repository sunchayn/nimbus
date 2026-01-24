import { useGeneratorSearch } from '@/composables/data/useGeneratorSearch';
import type { ValueGenerator } from '@/interfaces/ui';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const mockGenerators: ValueGenerator[] = [
    {
        id: 'email',
        name: 'Email Generator',
        description: 'Generates random email addresses',
        category: { id: 'string', name: 'String' },
        generate: () => 'test@example.com',
    },
    {
        id: 'uuid',
        name: 'UUID Generator',
        description: 'Generates UUID v4',
        category: { id: 'string', name: 'String' },
        generate: () => '123e4567-e89b-12d3-a456-426614174000',
    },
    {
        id: 'number',
        name: 'Number Generator',
        description: 'Generates random numbers',
        category: { id: 'number', name: 'Number' },
        generate: () => 42,
    },
];

describe('useGeneratorSearch', () => {
    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('initializes with empty search query and no category filter', () => {
            // Act

            const { searchQuery, selectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            // Assert

            expect(searchQuery.value).toBe('');
            expect(selectedCategory.value).toBeNull();
            expect(filteredGenerators.value).toEqual(mockGenerators);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Filtering', () => {
        it('filters generators by name case-insensitively', () => {
            // Arrange

            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            // Act

            setSearchQuery('email');

            // Assert

            expect(filteredGenerators.value).toHaveLength(1);
            expect(filteredGenerators.value[0].id).toBe('email');
        });

        it('filters generators by description case-insensitively', () => {
            // Arrange

            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            // Act

            setSearchQuery('uuid');

            // Assert

            expect(filteredGenerators.value).toHaveLength(1);
            expect(filteredGenerators.value[0].id).toBe('uuid');
        });

        it('filters generators by selected category', () => {
            // Arrange

            const { setSelectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            // Act

            setSelectedCategory('string');

            // Assert

            expect(filteredGenerators.value).toHaveLength(2);
            expect(filteredGenerators.value.every(g => g.category.id === 'string')).toBe(
                true,
            );
        });

        it('applies both search query and category filter simultaneously', () => {
            // Arrange

            const { setSearchQuery, setSelectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            // Act

            setSearchQuery('generator');
            setSelectedCategory('string');

            // Assert

            expect(filteredGenerators.value).toHaveLength(2);
            expect(filteredGenerators.value.every(g => g.category.id === 'string')).toBe(
                true,
            );
        });

        it('clears all filters when clearFilters is called', () => {
            // Arrange

            const {
                setSearchQuery,
                setSelectedCategory,
                clearFilters,
                filteredGenerators,
            } = useGeneratorSearch(mockGenerators);

            setSearchQuery('email');
            setSelectedCategory('string');

            // Act

            clearFilters();

            // Assert

            expect(filteredGenerators.value).toEqual(mockGenerators);
        });
    });

    /*
     * Edge Cases.
     */

    describe('Edge Cases', () => {
        it('handles search query with special characters', () => {
            // Arrange

            const generatorsWithSpecialChars: ValueGenerator[] = [
                {
                    id: 'special',
                    name: 'Email@Generator',
                    description: 'Test (with) special-chars',
                    category: { id: 'string', name: 'String' },
                    generate: () => 'test',
                },
            ];

            const { setSearchQuery, filteredGenerators } = useGeneratorSearch(
                generatorsWithSpecialChars,
            );

            // Act

            setSearchQuery('@');

            // Assert

            expect(filteredGenerators.value).toHaveLength(1);
        });

        it('handles very long search queries', () => {
            // Arrange

            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            // Act

            setSearchQuery('a'.repeat(1000));

            // Assert

            expect(filteredGenerators.value).toEqual([]);
        });
    });
});
