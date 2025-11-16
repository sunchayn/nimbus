import { ValueGenerator } from '@/interfaces/ui';
import { useValueGeneratorDefinitionsStore } from '@/stores/generators/useValueGeneratorDefinitionsStore';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const generatorFixtures = vi.hoisted(() => ({
    allValueGenerators: [
        {
            id: 'email',
            name: 'Email Generator',
            description: 'Generates email',
            category: { id: 'string', name: 'String' },
            generate: () => 'test@example.com',
        },
        {
            id: 'uuid',
            name: 'UUID Generator',
            description: 'Generates UUID',
            category: { id: 'string', name: 'String' },
            generate: () => '123e4567-e89b-12d3-a456-426614174000',
        },
        {
            id: 'number',
            name: 'Number Generator',
            description: 'Generates number',
            category: { id: 'number', name: 'Number' },
            generate: () => 42,
        },
    ] as ValueGenerator[],
    generatorCategories: [
        { id: 'string', name: 'String' },
        { id: 'number', name: 'Number' },
    ],
}));

vi.mock('@/config/generators', () => generatorFixtures);

const mockGenerators = generatorFixtures.allValueGenerators;
const mockCategories = generatorFixtures.generatorCategories;

describe('useValueGeneratorDefinitionsStore', () => {
    let store: ReturnType<typeof useValueGeneratorDefinitionsStore>;

    beforeEach(() => {
        store = useValueGeneratorDefinitionsStore();
    });

    describe('initial state', () => {
        it('initializes with generators from config', () => {
            expect(store.generators).toEqual(mockGenerators);
        });

        it('initializes with categories from config', () => {
            expect(store.categories).toEqual(mockCategories);
        });
    });

    describe('getGeneratorById', () => {
        it('returns generator when found by id', () => {
            const generator = store.getGeneratorById('email');

            expect(generator).toEqual(mockGenerators[0]);
        });

        it('returns undefined when generator not found', () => {
            const generator = store.getGeneratorById('nonexistent');

            expect(generator).toBeUndefined();
        });
    });

    describe('getGeneratorsByCategory', () => {
        it('returns all generators for specified category', () => {
            const generators = store.getGeneratorsByCategory('string');

            expect(generators).toHaveLength(2);
            expect(generators.every(g => g.category.id === 'string')).toBe(true);
        });

        it('returns empty array when no generators match category', () => {
            const generators = store.getGeneratorsByCategory('nonexistent');

            expect(generators).toEqual([]);
        });
    });

    describe('getAllCategories', () => {
        it('returns all categories', () => {
            const categories = store.getAllCategories();

            expect(categories).toEqual(mockCategories);
        });
    });
});
