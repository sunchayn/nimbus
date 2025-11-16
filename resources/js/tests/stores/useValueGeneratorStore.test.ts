import { ValueGenerator } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores/generators/useValueGeneratorStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, reactive, ref } from 'vue';

const generators: ValueGenerator[] = [
    {
        id: 'uuid',
        name: 'UUID',
        description: 'Generates UUID',
        category: { id: 'strings', name: 'Strings' },
        generate: vi.fn(() => 'uuid-value'),
    },
    {
        id: 'email',
        name: 'Email',
        description: 'Generates email',
        category: { id: 'strings', name: 'Strings' },
        generate: vi.fn(() => 'email@example.com'),
    },
];

const categories = [{ id: 'strings', name: 'Strings' }];

const commandStore = reactive({
    isCommandOpen: false,
    currentInputRef: null as HTMLElement | null,
    commandState: {
        recentGenerators: [] as string[],
    },
    openCommand: vi.fn(),
    closeCommand: vi.fn(),
    setSearchQuery: vi.fn(),
    setSelectedCategory: vi.fn(),
    addToRecentGenerators: vi.fn(id => {
        commandStore.commandState.recentGenerators = [
            id,
            ...commandStore.commandState.recentGenerators.filter(
                existing => existing !== id,
            ),
        ].slice(0, 2);
    }),
    restoreCommandState: vi.fn(),
    wasOpenedViaShiftShift: false,
});

const filteredGenerators = ref(generators);
const setSearchQuery = vi.fn();
const setSelectedCategory = vi.fn();

vi.mock('@/stores/generators/useValueGeneratorDefinitionsStore', () => ({
    useValueGeneratorDefinitionsStore: () => ({
        generators,
        categories,
        getGeneratorById: (id: string) =>
            generators.find(generator => generator.id === id),
    }),
}));

vi.mock('@/stores/generators/useGeneratorCommandStore', () => ({
    useGeneratorCommandStore: () => commandStore,
}));

vi.mock('@/composables/data/useGeneratorSearch', () => ({
    useGeneratorSearch: () => ({
        filteredGenerators,
        setSearchQuery,
        setSelectedCategory,
        searchQuery: ref(''),
        selectedCategory: ref(null),
        clearFilters: vi.fn(),
        hasActiveFilters: computed(() => false),
    }),
}));

describe('useValueGeneratorStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        commandStore.isCommandOpen = false;
        commandStore.currentInputRef = null;
        commandStore.commandState.recentGenerators = [];
        commandStore.openCommand.mockClear();
        commandStore.closeCommand.mockClear();
        commandStore.setSearchQuery.mockClear();
        commandStore.setSelectedCategory.mockClear();
        commandStore.addToRecentGenerators.mockClear();
        setSearchQuery.mockClear();
        setSelectedCategory.mockClear();
        generators.forEach(generator => (generator.generate as vi.Mock).mockClear());
    });

    it('generates value and records generator usage', () => {
        const store = useValueGeneratorStore();

        const value = store.generateValue('uuid');

        expect(value).toBe('uuid-value');
        expect(commandStore.addToRecentGenerators).toHaveBeenCalledWith('uuid');
    });

    it('proxies command interactions', () => {
        const store = useValueGeneratorStore();
        const input = document.createElement('input');

        store.openCommand(input);
        store.closeCommand();

        expect(commandStore.openCommand).toHaveBeenCalledWith(input, expect.anything());
        expect(commandStore.closeCommand).toHaveBeenCalled();
    });

    it('mirrors command open state from command store', async () => {
        const store = useValueGeneratorStore();

        commandStore.isCommandOpen = true;
        commandStore.currentInputRef = document.createElement('input');

        await Promise.resolve();

        expect(store.isCommandOpen).toBe(true);
        expect(store.currentInputRef).toBe(commandStore.currentInputRef);
    });

    it('synchronizes search state with command store and composable', () => {
        const store = useValueGeneratorStore();

        store.setSearchQuery('email');
        store.setSelectedCategory('strings');

        expect(commandStore.setSearchQuery).toHaveBeenCalledWith('email');
        expect(setSearchQuery).toHaveBeenCalledWith('email');
        expect(commandStore.setSelectedCategory).toHaveBeenCalledWith('strings');
        expect(setSelectedCategory).toHaveBeenCalledWith('strings');
    });

    it('exposes recent generators resolved to generator definitions', () => {
        const store = useValueGeneratorStore();

        commandStore.commandState.recentGenerators = ['email', 'uuid'];

        expect(store.recentGenerators.map(generator => generator.id)).toEqual([
            'email',
            'uuid',
        ]);
    });
});
