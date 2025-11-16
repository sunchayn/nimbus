import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { describe, expect, it, vi } from 'vitest';
import { effectScope } from 'vue';

const scrollMocks = vi.hoisted(() => ({
    getScrollBounds: vi.fn(() => ({ current: 50 })),
    getMaskVisibility: vi.fn(() => ({ showLeftMask: true, showRightMask: false })),
    getElementVisibility: vi.fn(() => ({ isFullyVisible: false })),
    calculateScrollToElement: vi.fn(() => 120),
}));

const debounceMock = vi.hoisted(() => vi.fn((fn: () => void) => fn));

vi.mock('@/utils/scroll', () => scrollMocks);

vi.mock('@vueuse/core', () => ({
    useDebounceFn: debounceMock,
}));

describe('useTabHorizontalScroll', () => {
    const runComposable = () => {
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

        // @ts-expect-error assigned above
        return composable as ReturnType<typeof useTabHorizontalScroll>;
    };

    it('updates mask visibility and saves scroll position', () => {
        const composable = runComposable();
        const container = {
            scrollLeft: 0,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        } as unknown as HTMLElement;

        composable.scrollContainer.value = container;
        composable.updateScrollMasks();

        expect(scrollMocks.getScrollBounds).toHaveBeenCalledWith(container, 30);
        expect(scrollMocks.getMaskVisibility).toHaveBeenCalled();
        expect(composable.showLeftMask.value).toBe(true);
        expect(composable.showRightMask.value).toBe(false);
    });

    it('scrolls tab into view when not visible', () => {
        vi.useFakeTimers();

        const composable = runComposable();
        const scrollTo = vi.fn();

        composable.scrollContainer.value = {
            scrollTo,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        } as unknown as HTMLElement;

        const button = document.createElement('button');

        composable.scrollTabIntoView(button);

        expect(scrollMocks.getElementVisibility).toHaveBeenCalled();
        expect(scrollMocks.calculateScrollToElement).toHaveBeenCalled();
        expect(scrollTo).toHaveBeenCalledWith({ left: 120, behavior: 'smooth' });

        vi.useRealTimers();
    });

    it('restores saved scroll position', async () => {
        const composable = runComposable();
        const container = {
            scrollLeft: 0,
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
        } as unknown as HTMLElement;

        composable.scrollContainer.value = container;
        composable.updateScrollMasks();

        composable.scrollContainer.value!.scrollLeft = 0;

        await composable.restoreScrollPosition();

        expect(container.scrollLeft).toBe(50);
    });
});
