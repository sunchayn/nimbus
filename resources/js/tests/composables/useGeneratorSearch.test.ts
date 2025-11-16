import { useGeneratorSearch } from '@/composables/data/useGeneratorSearch';
import { ValueGenerator } from '@/interfaces/ui';
import { describe, expect, it } from 'vitest';

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
    describe('initialization', () => {
        it('initializes with empty search query and no category filter', () => {
            const { searchQuery, selectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            expect(searchQuery.value).toBe('');
            expect(selectedCategory.value).toBeNull();
            expect(filteredGenerators.value).toEqual(mockGenerators);
        });
    });

    describe('text search filtering', () => {
        it('filters generators by name case-insensitively', () => {
            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('email');

            expect(filteredGenerators.value).toHaveLength(1);
            expect(filteredGenerators.value[0].id).toBe('email');
        });

        it('filters generators by description case-insensitively', () => {
            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('uuid');

            expect(filteredGenerators.value).toHaveLength(1);
            expect(filteredGenerators.value[0].id).toBe('uuid');
        });

        it('returns empty array when no generators match search query', () => {
            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('nonexistent');

            expect(filteredGenerators.value).toEqual([]);
        });

        it('matches partial text in name or description', () => {
            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('generator');

            expect(filteredGenerators.value).toHaveLength(3);
        });

        it('clears search filter when query is empty', () => {
            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('email');
            expect(filteredGenerators.value).toHaveLength(1);

            setSearchQuery('');
            expect(filteredGenerators.value).toEqual(mockGenerators);
        });
    });

    describe('category filtering', () => {
        it('filters generators by selected category', () => {
            const { setSelectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSelectedCategory('string');

            expect(filteredGenerators.value).toHaveLength(2);
            expect(filteredGenerators.value.every(g => g.category.id === 'string')).toBe(
                true,
            );
        });

        it('returns empty array when no generators match category', () => {
            const { setSelectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSelectedCategory('nonexistent');

            expect(filteredGenerators.value).toEqual([]);
        });

        it('clears category filter when set to null', () => {
            const { setSelectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSelectedCategory('string');
            expect(filteredGenerators.value).toHaveLength(2);

            setSelectedCategory(null);
            expect(filteredGenerators.value).toEqual(mockGenerators);
        });
    });

    describe('combined filtering', () => {
        it('applies both search query and category filter simultaneously', () => {
            const { setSearchQuery, setSelectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('generator');
            setSelectedCategory('string');

            expect(filteredGenerators.value).toHaveLength(2);
            expect(filteredGenerators.value.every(g => g.category.id === 'string')).toBe(
                true,
            );
        });

        it('returns empty array when filters exclude all generators', () => {
            const { setSearchQuery, setSelectedCategory, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('email');
            setSelectedCategory('number');

            expect(filteredGenerators.value).toEqual([]);
        });
    });

    describe('filter management', () => {
        it('clears all filters when clearFilters is called', () => {
            const {
                setSearchQuery,
                setSelectedCategory,
                clearFilters,
                filteredGenerators,
            } = useGeneratorSearch(mockGenerators);

            setSearchQuery('email');
            setSelectedCategory('string');

            clearFilters();

            expect(filteredGenerators.value).toEqual(mockGenerators);
        });

        it('correctly identifies when filters are active', () => {
            const {
                setSearchQuery,
                setSelectedCategory,
                hasActiveFilters,
                clearFilters,
            } = useGeneratorSearch(mockGenerators);

            expect(hasActiveFilters.value).toBe(false);

            setSearchQuery('test');
            expect(hasActiveFilters.value).toBe(true);

            clearFilters();
            setSelectedCategory('string');
            expect(hasActiveFilters.value).toBe(true);

            clearFilters();
            expect(hasActiveFilters.value).toBe(false);
        });
    });

    describe('edge cases', () => {
        it('handles empty generators array', () => {
            const { filteredGenerators } = useGeneratorSearch([]);

            expect(filteredGenerators.value).toEqual([]);
        });

        it('handles search query with special characters', () => {
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

            setSearchQuery('@');
            expect(filteredGenerators.value).toHaveLength(1);

            setSearchQuery('(');
            expect(filteredGenerators.value).toHaveLength(1);
        });

        it('handles very long search queries', () => {
            const { setSearchQuery, filteredGenerators } =
                useGeneratorSearch(mockGenerators);

            setSearchQuery('a'.repeat(1000));

            expect(filteredGenerators.value).toEqual([]);
        });
    });
});
