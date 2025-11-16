import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { useGeneratorCommandStore } from '@/stores/generators/useGeneratorCommandStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

describe('useGeneratorCommandStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('opens and closes command while tracking input refs', () => {
        const store = useGeneratorCommandStore();
        const input = document.createElement('input');

        store.openCommand(input, ValueGeneratorCommandOpenMethod.SHIFT_SHIFT);

        expect(store.isCommandOpen).toBe(true);
        expect(store.currentInputRef).toBe(input);
        expect(store.wasOpenedViaShiftShift).toBe(true);

        store.closeCommand();

        expect(store.isCommandOpen).toBe(false);
        expect(store.currentInputRef).toBeNull();
    });

    it('updates command state and maintains recent generators cap', () => {
        const store = useGeneratorCommandStore();

        store.setSearchQuery('email');
        store.setSelectedCategory('strings');
        store.addToRecentGenerators('uuid');
        store.addToRecentGenerators('email');
        store.addToRecentGenerators('uuid'); // promotes existing entry

        expect(store.commandState.searchQuery).toBe('email');
        expect(store.commandState.selectedCategory).toBe('strings');
        expect(store.commandState.recentGenerators).toEqual(['uuid', 'email']);
    });

    it('restores prior command search when command has no query', () => {
        const store = useGeneratorCommandStore();

        store.setSearchQuery('date');

        const commandInstance = { filterState: { search: '' } };

        store.restoreCommandState(commandInstance);

        expect(commandInstance.filterState.search).toBe('date');
    });
});
