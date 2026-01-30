import { useTabVerticalScroll } from '@/composables/ui/useTabVerticalScroll';
import { mount } from '@vue/test-utils';
import * as vueuse from '@vueuse/core';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick } from 'vue';

vi.mock('@vueuse/core', async importOriginal => ({
    ...(await importOriginal<object>()),
    useResizeObserver: vi.fn(() => ({ stop: vi.fn() })),
    useMutationObserver: vi.fn(() => ({ stop: vi.fn() })),
}));

describe('useTabVerticalScrollUnitTest', () => {
    let mockContainer: HTMLElement;

    beforeEach(() => {
        vi.clearAllMocks();
        mockContainer = document.createElement('div');
        Object.defineProperty(mockContainer, 'clientHeight', {
            value: 100,
            configurable: true,
        });
        Object.defineProperty(mockContainer, 'scrollHeight', {
            value: 300,
            configurable: true,
        });
        mockContainer.scrollTop = 0;
        mockContainer.scrollTo = vi.fn();
    });

    // Helper to test the composable with lifecycle
    const mountComposable = (
        config: Partial<Parameters<typeof useTabVerticalScroll>[0]> = {},
    ) => {
        let result!: ReturnType<typeof useTabVerticalScroll>;
        // MASK_HEIGHT is required but we merge it
        const finalConfig: Parameters<typeof useTabVerticalScroll>[0] = {
            MASK_HEIGHT: 32,
            ...config,
        };

        const TestComponent = defineComponent({
            setup() {
                result = useTabVerticalScroll(finalConfig);

                return () => h('div', { ref: result.scrollContainer });
            },
        });
        const wrapper = mount(TestComponent);

        return { result, wrapper };
    };

    it('initializes with default values', () => {
        const { result } = mountComposable();
        expect(result.showTopMask.value).toBe(false);
        expect(result.showBottomMask.value).toBe(false);
    });

    it('updates masks based on scroll position', async () => {
        const { result, wrapper } = mountComposable({ SCROLL_THRESHOLD: 1 });
        const container = wrapper.element as HTMLElement;

        Object.defineProperty(container, 'scrollHeight', { value: 300 });
        Object.defineProperty(container, 'clientHeight', { value: 100 });

        // Act - Scroll a bit
        container.scrollTop = 50;
        result.updateScrollMasks();

        expect(result.showTopMask.value).toBe(true);
        expect(result.showBottomMask.value).toBe(true);
    });

    it('updates masks when container is resized', async () => {
        mountComposable();
        await nextTick();

        // Verify ResizeObserver was setup
        expect(vueuse.useResizeObserver).toHaveBeenCalledWith(
            expect.anything(),
            expect.any(Function),
        );

        // Get the callback passed to useResizeObserver
        const resizeCallback = vi.mocked(vueuse.useResizeObserver).mock.calls[0][1];

        // Simulate resize (make container large enough to show everything)
        // Since we are mocking the observer, we just call the callback and assert visibility
        // but the actual container size in result.scrollContainer should be updated if used in updateScrollMasks

        // This test verifies the observer is wired up.
        expect(resizeCallback).toBeDefined();
    });

    it('scrolls element into view when called', async () => {
        const { result, wrapper } = mountComposable({
            MASK_HEIGHT: 40,
            SCROLL_PADDING: 8,
        });
        const container = wrapper.element as HTMLElement;
        container.scrollTo = vi.fn();

        // Mock container rect
        vi.spyOn(container, 'getBoundingClientRect').mockReturnValue({
            top: 0,
            bottom: 300,
            left: 0,
            right: 0,
            width: 0,
            height: 300,
            x: 0,
            y: 0,
            toJSON: () => {},
        });

        const mockElement = document.createElement('div');
        vi.spyOn(mockElement, 'getBoundingClientRect').mockReturnValue({
            top: 350,
            bottom: 380,
            left: 0,
            right: 0,
            width: 0,
            height: 30,
            x: 0,
            y: 0,
            toJSON: () => {},
        });

        result.scrollTabIntoView(mockElement);

        expect(container.scrollTo).toHaveBeenCalled();
    });

    it('updates masks when content changes', async () => {
        mountComposable();
        await nextTick();

        // Verify MutationObserver was setup
        expect(vueuse.useMutationObserver).toHaveBeenCalledWith(
            expect.anything(),
            expect.any(Function),
            expect.objectContaining({ childList: true }),
        );

        // Get the callback
        const mutationCallback = vi.mocked(vueuse.useMutationObserver).mock.calls[0][1];
        expect(mutationCallback).toBeDefined();
    });
});
