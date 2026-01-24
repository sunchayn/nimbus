import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { reactive, ref, computed } from 'vue';
import { ValueGenerator } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores/generators/useValueGeneratorStore';

/*
 * Fixtures.
 */

const generators: ValueGenerator[] = [
    {
        id: 'uuid',
        name: 'UUID',
        description: 'Generates UUID',
        category: { id: 'strings', name: 'Strings' },
        generate: vi.fn(() => 'uuid-value'),
    },
];

const commandStore = reactive({
    isCommandOpen: false,
    currentInputRef: null as HTMLElement | null,
    commandState: { recentGenerators: [] as string[] },
    openCommand: vi.fn(),
    closeCommand: vi.fn(),
    setSearchQuery: vi.fn(),
    setSelectedCategory: vi.fn(),
    addToRecentGenerators: vi.fn(),
});

vi.mock('@/stores/generators/useValueGeneratorDefinitionsStore', () => ({
    useValueGeneratorDefinitionsStore: () => ({
        generators,
        getGeneratorById: (id: string) => generators.find(g => g.id === id),
    }),
}));

vi.mock('@/stores/generators/useGeneratorCommandStore', () => ({
    useGeneratorCommandStore: () => commandStore,
}));

vi.mock('@/composables/data/useGeneratorSearch', () => ({
    useGeneratorSearch: () => ({
        filteredGenerators: ref(generators),
        setSearchQuery: vi.fn(),
        setSelectedCategory: vi.fn(),
        searchQuery: ref(''),
        selectedCategory: ref(null),
        clearFilters: vi.fn(),
        hasActiveFilters: computed(() => false),
    }),
}));

describe('useValueGeneratorStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Behavior tests.
     */

    describe('Generation', () => {
        it('generates value and records generator usage', () => {
            // Arrange

            const store = useValueGeneratorStore();

            // Act

            const value = store.generateValue('uuid');

            // Assert

            expect(value).toBe('uuid-value');
            expect(commandStore.addToRecentGenerators).toHaveBeenCalledWith('uuid');
        });
    });

    describe('Command Proxying', () => {
        it('proxies command open/close interactions', () => {
            // Arrange

            const store = useValueGeneratorStore();
            const input = document.createElement('input');

            // Act

            store.openCommand(input);
            store.closeCommand();

            // Assert

            expect(commandStore.openCommand).toHaveBeenCalledWith(input, expect.anything());
            expect(commandStore.closeCommand).toHaveBeenCalled();
        });
    });
});
