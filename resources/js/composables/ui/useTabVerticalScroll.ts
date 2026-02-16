import type { TabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { tabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { useDebounceFn, useMutationObserver, useResizeObserver } from '@vueuse/core';
import {
    nextTick,
    onUnmounted,
    readonly,
    ref,
    watch,
    type DeepReadonly,
    type Ref,
} from 'vue';

export interface UseTabVerticalScrollResult {
    scrollContainer: Ref<HTMLElement | null>;
    showTopMask: DeepReadonly<Ref<boolean>>;
    showBottomMask: DeepReadonly<Ref<boolean>>;
    updateScrollMasks: () => void;
    scrollTabIntoView: (element: HTMLElement) => void;
}

/**
 * Handles vertical scroll state and masks.
 *
 * Manages top/bottom masks based on scroll position and provides
 * functionality to scroll specific elements into the visible area.
 */
export function useTabVerticalScroll(
    config: Partial<TabNavigationScrollConfig> & {
        /**
         * Mask height in pixels (Vertical).
         *
         * Height of the gradient masks that fade content at scroll boundaries vertically.
         * Provides visual indication of scrollable content beyond viewport.
         */
        MASK_HEIGHT: number;
    },
): UseTabVerticalScrollResult {
    /*
     * Configuration.
     */

    const scrollThreshold =
        config?.SCROLL_THRESHOLD ?? tabNavigationScrollConfig.SCROLL_THRESHOLD;

    const scrollPadding =
        config?.SCROLL_PADDING ?? tabNavigationScrollConfig.SCROLL_PADDING;

    const animationDuration =
        config?.ANIMATION_DURATION ?? tabNavigationScrollConfig.ANIMATION_DURATION;

    const debounceDelay =
        config?.DEBOUNCE_DELAY ?? tabNavigationScrollConfig.DEBOUNCE_DELAY;

    /*
     * State.
     */

    const scrollContainer = ref<HTMLElement | null>(null);
    const showTopMask = ref(false);
    const showBottomMask = ref(false);

    /*
     * Actions.
     */

    /**
     * Updates gradient mask visibility based on scroll position.
     */
    const updateScrollMasks = () => {
        if (!scrollContainer.value) {
            return;
        }

        const { scrollTop, scrollHeight, clientHeight } = scrollContainer.value;

        // Round values to avoid sub-pixel jitter during layout shifts/resizing
        const roundedScrollTop = Math.round(scrollTop);
        const roundedScrollHeight = Math.round(scrollHeight);
        const roundedClientHeight = Math.round(clientHeight);
        const maxScroll = Math.max(0, roundedScrollHeight - roundedClientHeight);

        // Threshold-aware fits: if content is only slightly larger than viewport, don't show masks
        const contentFits = maxScroll <= scrollThreshold;

        const isAtStart = roundedScrollTop <= scrollThreshold;
        const isAtEnd = contentFits || roundedScrollTop >= maxScroll - scrollThreshold;

        showTopMask.value = !isAtStart && !contentFits;
        showBottomMask.value = !isAtEnd && !contentFits;
    };

    const debouncedUpdateMasks = useDebounceFn(updateScrollMasks, debounceDelay);

    /**
     * Scrolls an element into view vertically, accounting for gradient masks.
     */
    const scrollTabIntoView = (element: HTMLElement) => {
        if (!scrollContainer.value) {
            return;
        }

        const container = scrollContainer.value;
        const containerRect = container.getBoundingClientRect();
        const elementRect = element.getBoundingClientRect();

        const relativeTop = elementRect.top - containerRect.top + container.scrollTop;
        const relativeBottom = relativeTop + elementRect.height;

        // Safety check: ensure mask height doesn't exceed container height
        const activeMaskHeightTop =
            container.clientHeight < config.MASK_HEIGHT * 2.5
                ? container.clientHeight / 4
                : config.MASK_HEIGHT;
        const activeMaskHeightBottom =
            container.clientHeight < config.MASK_HEIGHT * 2.5
                ? container.clientHeight / 4
                : config.MASK_HEIGHT;

        const visibleTop = container.scrollTop + activeMaskHeightTop;
        const visibleBottom =
            container.scrollTop + container.clientHeight - activeMaskHeightBottom;

        let targetScrollTop = container.scrollTop;

        if (relativeTop < visibleTop) {
            targetScrollTop = Math.max(
                0,
                relativeTop - activeMaskHeightTop - scrollPadding,
            );
        } else if (relativeBottom > visibleBottom) {
            targetScrollTop =
                relativeBottom -
                container.clientHeight +
                activeMaskHeightBottom +
                scrollPadding;
        }

        if (targetScrollTop !== container.scrollTop) {
            container.scrollTo({
                top: targetScrollTop,
                behavior: 'smooth',
            });

            // Update masks after scrolling animation completes
            setTimeout(() => {
                updateScrollMasks();
            }, animationDuration);
        }
    };

    /**
     * Sets up scroll event listeners.
     */
    const setupScrollListeners = () => {
        if (!scrollContainer.value) {
            return;
        }

        scrollContainer.value.addEventListener('scroll', debouncedUpdateMasks, {
            passive: true,
        });

        return () => {
            scrollContainer.value?.removeEventListener('scroll', debouncedUpdateMasks);
        };
    };

    /**
     * Sets up resize observer for the container.
     */
    const setupResizeObserver = () => {
        return useResizeObserver(scrollContainer, debouncedUpdateMasks);
    };

    /**
     * Sets up mutation observer for the content.
     */
    const setupMutationObserver = () => {
        return useMutationObserver(scrollContainer, debouncedUpdateMasks, {
            childList: true,
            subtree: true,
        });
    };

    /*
     * Lifecycle.
     */

    let cleanupScrollListeners: (() => void) | null = null;
    let cleanupResizeObserver: (() => void | undefined) | null = null;
    let cleanupMutationObserver: (() => void | undefined) | null = null;

    watch(scrollContainer, () => {
        cleanupScrollListeners = setupScrollListeners() ?? null;
        cleanupResizeObserver = setupResizeObserver().stop;
        cleanupMutationObserver = setupMutationObserver().stop;

        nextTick(() => {
            updateScrollMasks();
        });
    });

    onUnmounted(() => {
        cleanupScrollListeners?.();
        cleanupResizeObserver?.();
        cleanupMutationObserver?.();
    });

    return {
        // State
        scrollContainer,
        showTopMask: readonly(showTopMask),
        showBottomMask: readonly(showBottomMask),

        // Actions
        updateScrollMasks,
        scrollTabIntoView,
    };
}
