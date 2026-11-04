import type { ValueGenerator } from '@/interfaces/ui';
import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { useGeneratorCommandStore } from '@/stores/generators/useGeneratorCommandStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const generatorFixtures = vi.hoisted(() => {
    const uuid = {
        id: 'uuid',
        name: 'UUID',
        description: 'Generates UUID',
        category: { id: 'strings', name: 'Strings' },
        generate: () => 'uuid-value',
    } as ValueGenerator;

    const age = {
        id: 'age',
        name: 'Age',
        description: 'Generates age',
        category: { id: 'numbers', name: 'Numbers' },
        generate: () => 30,
    } as ValueGenerator;

    return {
        getAllGenerators: [uuid, age],
        getGeneratorById: new Map([
            [uuid.id, uuid],
            [age.id, age],
        ]),
    };
});

vi.mock('@/config/generators', () => generatorFixtures);

describe('useGeneratorCommandStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    /*
     * Initialization tests.
     */

    describe('Visibility', () => {
        it('opens and closes command while tracking input refs', () => {
            // Arrange

            const store = useGeneratorCommandStore();
            const input = document.createElement('input');

            // Act

            store.openCommand(input, ValueGeneratorCommandOpenMethod.SHIFT_SHIFT);

            // Assert

            expect(store.isCommandOpen).toBe(true);
            expect(store.currentInputRef).toBe(input);
            expect(store.wasOpenedViaShiftShift).toBe(true);

            // Act

            store.closeCommand();

            // Assert

            expect(store.isCommandOpen).toBe(false);
            expect(store.currentInputRef).toBeNull();
        });
    });

    /*
     * State Transition tests.
     */

    describe('State Management', () => {
        it('updates command state and maintains recent generators cap', () => {
            // Arrange

            const store = useGeneratorCommandStore();

            // Act

            store.setSearchQuery('email');
            store.setSelectedCategory('strings');
            store.addToRecentGenerators('uuid');
            store.addToRecentGenerators('email');
            store.addToRecentGenerators('uuid'); // promotes existing entry

            // Assert

            expect(store.commandState.searchQuery).toBe('email');
            expect(store.commandState.selectedCategory).toBe('strings');
            expect(store.commandState.recentGenerators).toEqual(['uuid', 'email']);
        });

        it('restores prior command search when command has no query', () => {
            // Arrange

            const store = useGeneratorCommandStore();
            store.setSearchQuery('date');
            const commandInstance = { filterState: { search: '' } };

            // Act

            store.restoreCommandState(commandInstance);

            // Assert

            expect(commandInstance.filterState.search).toBe('date');
        });
    });

    describe('Derived Generators', () => {
        it('filters generators by search query and category', () => {
            // Arrange

            const store = useGeneratorCommandStore();

            // Act

            store.setSearchQuery('uuid');
            const matching = store.filteredGenerators.map(g => g.id);

            store.setSearchQuery('nope');
            const none = store.filteredGenerators;

            store.setSearchQuery('');
            store.setSelectedCategory('numbers');
            const byCategory = store.filteredGenerators.map(g => g.id);

            // Assert

            expect(matching).toEqual(['uuid']);
            expect(none).toEqual([]);
            expect(byCategory).toEqual(['age']);
        });

        it('resolves recent generators and ignores unknown ids', () => {
            // Arrange

            const store = useGeneratorCommandStore();

            // Act

            store.addToRecentGenerators('missing');
            store.addToRecentGenerators('age');

            // Assert

            expect(store.recentGenerators.map(g => g.id)).toEqual(['age']);
        });
    });
});
