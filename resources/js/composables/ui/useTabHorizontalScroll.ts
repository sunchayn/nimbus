import { useScrollMasks } from '@/composables/ui/useScrollMasks';
import type { TabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { tabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import {
    calculateScrollToElement,
    getElementVisibility,
    getMaskVisibility,
    getScrollBounds,
} from '@/utils/scroll';
import type { ScrollBounds } from '@/utils/scroll/tabScrollUtils';
import { useEventListener, useResizeObserver } from '@vueuse/core';
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    readonly,
    type ComputedRef,
    type DeepReadonly,
    type Ref,
} from 'vue';

export interface UseTabHorizontalScrollResult {
    scrollContainer: Ref<HTMLElement | null>;
    showLeftMask: DeepReadonly<Ref<boolean>>;
    showRightMask: DeepReadonly<Ref<boolean>>;
    scrollBounds: ComputedRef<ScrollBounds | null>;
    updateScrollMasks: () => void;
    scrollTabIntoView: (buttonElement: HTMLElement) => void;
    restoreScrollPosition: () => Promise<void>;
}

/**
 * Handles horizontal scroll state and masks for tab containers.
 *
 * Manages left/right gradient masks based on scroll position and provides
 * functionality to scroll specific tabs into the visible area.
 */
export const useTabHorizontalScroll = (
    config?: Partial<TabNavigationScrollConfig>,
): UseTabHorizontalScrollResult => {
    const maskWidth = config?.MASK_WIDTH ?? tabNavigationScrollConfig.MASK_WIDTH;

    const {
        scrollContainer,
        showStartMask,
        showEndMask,
        savedScrollPosition,
        scrollThreshold,
        updateScrollMasks,
        debouncedUpdateMasks,
        scrollElementIntoView,
    } = useScrollMasks({
        config,
        scrollProperty: 'left',
        computeMasks: (container, threshold) => {
            const bounds = getScrollBounds(container, threshold);
            const visibility = getMaskVisibility(bounds);

            return {
                showStartMask: visibility.showLeftMask,
                showEndMask: visibility.showRightMask,
                position: bounds.current,
            };
        },
        computeScrollTarget: (element, container, settings) => {
            const visibility = getElementVisibility(element, container, maskWidth);

            if (visibility.isFullyVisible) {
                return null;
            }

            return calculateScrollToElement(
                element,
                container,
                maskWidth,
                settings.SCROLL_PADDING,
            );
        },
    });

    const scrollBounds = computed(() =>
        scrollContainer.value
            ? getScrollBounds(scrollContainer.value, scrollThreshold)
            : null,
    );

    /**
     * Restores the previously saved scroll position.
     */
    const restoreScrollPosition = async () => {
        await nextTick();

        if (scrollContainer.value && savedScrollPosition.value > 0) {
            scrollContainer.value.scrollLeft = savedScrollPosition.value;
        }

        updateScrollMasks();
    };

    // Callers assign the container after mount, so listeners must follow the ref.
    useEventListener(scrollContainer, 'scroll', debouncedUpdateMasks, {
        passive: true,
    });

    // Window and splitter resizes change the overflow without firing a scroll event.
    useResizeObserver(scrollContainer, updateScrollMasks);

    // A vertical wheel has no horizontal axis, so it scrolls the list sideways.
    useEventListener(
        scrollContainer,
        'wheel',
        (event: WheelEvent) => {
            const container = scrollContainer.value;

            if (!container || Math.abs(event.deltaY) <= Math.abs(event.deltaX)) {
                return;
            }

            container.scrollLeft += event.deltaY;
            event.preventDefault();
        },
        { passive: false },
    );

    // Dragging anywhere on the list scrolls it, including from a tab button.
    let dragStartX: number | null = null;
    let dragStartScroll = 0;
    let dragged = false;

    useEventListener(scrollContainer, 'pointerdown', (event: PointerEvent) => {
        if (event.button !== 0 || event.pointerType === 'touch') {
            return;
        }

        dragStartX = event.clientX;
        dragStartScroll = scrollContainer.value?.scrollLeft ?? 0;
        dragged = false;
    });

    useEventListener(window, 'pointermove', (event: PointerEvent) => {
        if (dragStartX === null || !scrollContainer.value) {
            return;
        }

        const distance = event.clientX - dragStartX;

        if (Math.abs(distance) > 4) {
            dragged = true;
        }

        if (dragged) {
            scrollContainer.value.scrollLeft = dragStartScroll - distance;
        }
    });

    useEventListener(window, 'pointerup', () => {
        dragStartX = null;
    });

    // A drag that ends on a tab must not also select it.
    useEventListener(
        scrollContainer,
        'click',
        (event: MouseEvent) => {
            if (dragged) {
                event.stopPropagation();
                event.preventDefault();
                dragged = false;
            }
        },
        { capture: true },
    );

    onMounted(() => {
        debouncedUpdateMasks();
    });

    onUnmounted(() => {
        savedScrollPosition.value = 0;
    });

    return {
        // State
        scrollContainer,
        showLeftMask: readonly(showStartMask),
        showRightMask: readonly(showEndMask),

        // Computed
        scrollBounds,

        // Actions
        updateScrollMasks,
        scrollTabIntoView: scrollElementIntoView,
        restoreScrollPosition,
    };
};
