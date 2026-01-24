import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ValueGenerator } from '@/interfaces/ui';
import { useValueGeneratorDefinitionsStore } from '@/stores/generators/useValueGeneratorDefinitionsStore';

/*
 * Fixtures.
 */

const generatorFixtures = vi.hoisted(() => ({
    allValueGenerators: [
        {
            id: 'email',
            name: 'Email Generator',
            description: 'Generates email',
            category: { id: 'string', name: 'String' },
            generate: () => 'test@example.com',
        },
    ] as ValueGenerator[],
    generatorCategories: [{ id: 'string', name: 'String' }],
}));

vi.mock('@/config/generators', () => generatorFixtures);

describe('useValueGeneratorDefinitionsStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Initialization tests.
     */

    describe('Initialization', () => {
        it('initializes with generators from config', () => {
            // Act

            const store = useValueGeneratorDefinitionsStore();

            // Assert

            expect(store.generators).toEqual(generatorFixtures.allValueGenerators);
        });

        it('initializes with categories from config', () => {
            // Act

            const store = useValueGeneratorDefinitionsStore();

            // Assert

            expect(store.categories).toEqual(generatorFixtures.generatorCategories);
        });
    });

    /*
     * Search tests.
     */

    describe('Search', () => {
        it('returns generator when found by id', () => {
            // Arrange

            const store = useValueGeneratorDefinitionsStore();

            // Act

            const generator = store.getGeneratorById('email');

            // Assert

            expect(generator?.id).toBe('email');
        });

        it('returns undefined when generator not found', () => {
            // Arrange

            const store = useValueGeneratorDefinitionsStore();

            // Act

            const generator = store.getGeneratorById('nonexistent');

            // Assert

            expect(generator).toBeUndefined();
        });
    });
});
