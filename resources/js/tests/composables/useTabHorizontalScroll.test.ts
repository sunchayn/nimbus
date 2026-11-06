import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { useEventListener } from '@vueuse/core';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope } from 'vue';

/*
 * Fixtures.
 */

const scrollMocks = vi.hoisted(() => ({
    getScrollBounds: vi.fn(() => ({ current: 50 })),
    getMaskVisibility: vi.fn(() => ({
        showLeftMask: true,
        showRightMask: false,
    })),
    getElementVisibility: vi.fn(() => ({ isFullyVisible: false })),
    calculateScrollToElement: vi.fn(() => 120),
}));

vi.mock('@/utils/scroll', () => scrollMocks);
const vueuseMocks = vi.hoisted(() => ({
    resizeCallback: null as (() => void) | null,
}));

vi.mock('@vueuse/core', () => ({
    useDebounceFn: vi.fn(fn => fn),
    useEventListener: vi.fn(),
    useResizeObserver: vi.fn((_target, callback: () => void) => {
        vueuseMocks.resizeCallback = callback;
    }),
}));

describe('useTabHorizontalScroll', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    const runComposable = (): ReturnType<typeof useTabHorizontalScroll> => {
        let composable: ReturnType<typeof useTabHorizontalScroll>;
        effectScope().run(() => {
            composable = useTabHorizontalScroll({
                MASK_WIDTH: 20,
                SCROLL_THRESHOLD: 30,
                SCROLL_PADDING: 10,
                ANIMATION_DURATION: 0,
                DEBOUNCE_DELAY: 0,
            });
        });

        return composable!;
    };

    /*
     * Interaction tests.
     */

    describe('Interaction', () => {
        it('updates mask visibility and saves scroll position', () => {
            // Arrange

            const composable = runComposable();
            const container = {
                scrollLeft: 0,
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
            } as unknown as HTMLElement;

            composable.scrollContainer.value = container;

            // Act

            composable.updateScrollMasks();

            // Assert

            expect(scrollMocks.getScrollBounds).toHaveBeenCalled();
            expect(composable.showLeftMask.value).toBe(true);
        });

        it('updates mask visibility when the container is resized', () => {
            // Arrange

            const composable = runComposable();
            composable.scrollContainer.value = {
                scrollLeft: 0,
            } as unknown as HTMLElement;

            // Act

            vueuseMocks.resizeCallback?.();

            // Assert

            expect(scrollMocks.getScrollBounds).toHaveBeenCalled();
            expect(composable.showLeftMask.value).toBe(true);
        });

        it('scrolls tab into view when not visible', () => {
            // Arrange

            const composable = runComposable();
            const scrollTo = vi.fn();
            composable.scrollContainer.value = {
                scrollTo,
                addEventListener: vi.fn(),
                removeEventListener: vi.fn(),
            } as unknown as HTMLElement;
            const button = document.createElement('button');

            // Act

            composable.scrollTabIntoView(button);

            // Assert

            expect(scrollMocks.calculateScrollToElement).toHaveBeenCalled();
            expect(scrollTo).toHaveBeenCalledWith({
                left: 120,
                behavior: 'smooth',
            });
        });

        it('scrolls the list sideways on a vertical wheel', () => {
            // Arrange

            const composable = runComposable();
            const container = { scrollLeft: 10 } as unknown as HTMLElement;
            composable.scrollContainer.value = container;
            const wheelCall = vi
                .mocked(useEventListener)
                .mock.calls.find(call => call[1] === 'wheel');
            const preventDefault = vi.fn();

            // Act

            (wheelCall![2] as (event: unknown) => void)({
                deltaX: 0,
                deltaY: 40,
                preventDefault,
            });

            // Assert

            expect(container.scrollLeft).toBe(50);
            expect(preventDefault).toHaveBeenCalled();
        });

        it('ignores a wheel that already moves sideways', () => {
            // Arrange

            const composable = runComposable();
            const container = { scrollLeft: 10 } as unknown as HTMLElement;
            composable.scrollContainer.value = container;
            const wheelCall = vi
                .mocked(useEventListener)
                .mock.calls.find(call => call[1] === 'wheel');

            // Act

            (wheelCall![2] as (event: unknown) => void)({
                deltaX: 40,
                deltaY: 2,
                preventDefault: vi.fn(),
            });

            // Assert

            expect(container.scrollLeft).toBe(10);
        });
    });
});
