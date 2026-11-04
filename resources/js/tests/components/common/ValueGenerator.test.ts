import ValueGenerator from '@/components/common/ValueGenerator/ValueGenerator.vue';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, reactive } from 'vue';

/*
 * Fixtures.
 */

const restoreScrollPosition = vi.fn(() => Promise.resolve());

const mockStore = reactive({
    isCommandOpen: false,
    currentInputRef: null as HTMLElement | null,
    addToRecentGenerators: vi.fn(),
    closeCommand: vi.fn(),
    openCommand: vi.fn(),
    commandState: { recentGenerators: [] },
    recentGenerators: [],
    restoreCommandState: vi.fn(),
});

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return { ...actual, useGeneratorCommandStore: () => mockStore };
});

const generateValue = vi.hoisted(() => vi.fn());

vi.mock('@/utils/value-generator/generateValue', () => ({ generateValue }));

vi.mock('@/composables/ui/useTabHorizontalScroll', () => ({
    useTabHorizontalScroll: () => ({ restoreScrollPosition }),
}));

vi.mock('@/components/common/ValueGenerator/ValueGeneratorGeneratorList.vue', () => ({
    default: {
        template:
            '<button data-testid="trigger-generator" @click="$emit(\'generator-selected\', \'email\')">Gen</button>',
        emits: ['generator-selected'],
        methods: { focusInput: () => undefined },
    },
}));

const createWrapper = (pinia: ReturnType<typeof createPinia>): VueWrapper => {
    return mount(ValueGenerator, {
        global: {
            plugins: [pinia],
            stubs: {
                Teleport: true, // Disable teleport for easier testing
            },
        },
    });
};

describe('ValueGenerator', () => {
    let pinia: ReturnType<typeof createPinia>;

    beforeEach(() => {
        pinia = createPinia();
        setActivePinia(pinia);
        mockStore.isCommandOpen = false;
        vi.clearAllMocks();
    });

    describe('Rendering', () => {
        it('opens overlay when store toggles', async () => {
            // Arrange

            const wrapper = createWrapper(pinia);

            // Act

            mockStore.isCommandOpen = true;
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="value-generator-overlay"]').exists()).toBe(
                true,
            );
        });
    });

    describe('Behavior', () => {
        it('propagates generated values to inputs and emits event', async () => {
            // Arrange

            const input = document.createElement('input');
            generateValue.mockReturnValue('val');
            mockStore.currentInputRef = input;
            const wrapper = createWrapper(pinia);

            // Act

            mockStore.isCommandOpen = true;
            await nextTick();
            await wrapper.get('[data-testid="trigger-generator"]').trigger('click');

            // Assert

            expect(input.value).toBe('val');
            expect(mockStore.addToRecentGenerators).toHaveBeenCalled();
            expect(mockStore.closeCommand).toHaveBeenCalled();
        });
    });
});
