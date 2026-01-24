import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { useGeneratorCommandStore } from '@/stores/generators/useGeneratorCommandStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

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
});
