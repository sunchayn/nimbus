import { beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope } from 'vue';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';

/*
 * Fixtures.
 */

const scrollMocks = vi.hoisted(() => ({
    getScrollBounds: vi.fn(() => ({ current: 50 })),
    getMaskVisibility: vi.fn(() => ({ showLeftMask: true, showRightMask: false })),
    getElementVisibility: vi.fn(() => ({ isFullyVisible: false })),
    calculateScrollToElement: vi.fn(() => 120),
}));

vi.mock('@/utils/scroll', () => scrollMocks);
vi.mock('@vueuse/core', () => ({
    useDebounceFn: vi.fn(fn => fn),
}));

describe('useTabHorizontalScroll', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    const runComposable = () => {
        let composable: any;
        effectScope().run(() => {
            composable = useTabHorizontalScroll({
                MASK_WIDTH: 20,
                SCROLL_THRESHOLD: 30,
                SCROLL_PADDING: 10,
                ANIMATION_DURATION: 0,
                DEBOUNCE_DELAY: 0,
            });
        });
        return composable;
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
            } as any;

            composable.scrollContainer.value = container;

            // Act

            composable.updateScrollMasks();

            // Assert

            expect(scrollMocks.getScrollBounds).toHaveBeenCalled();
            expect(composable.showLeftMask.value).toBe(true);
        });

        it('scrolls tab into view when not visible', () => {
            // Arrange

            const composable = runComposable();
            const scrollTo = vi.fn();
            composable.scrollContainer.value = { scrollTo, addEventListener: vi.fn(), removeEventListener: vi.fn() } as any;
            const button = document.createElement('button');

            // Act

            composable.scrollTabIntoView(button);

            // Assert

            expect(scrollMocks.calculateScrollToElement).toHaveBeenCalled();
            expect(scrollTo).toHaveBeenCalledWith({ left: 120, behavior: 'smooth' });
        });
    });
});
