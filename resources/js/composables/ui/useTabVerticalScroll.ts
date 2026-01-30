import type { TabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { tabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { useDebounceFn, useMutationObserver, useResizeObserver } from '@vueuse/core';
import {
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    readonly,
    ref,
    type ComputedRef,
    type DeepReadonly,
    type Ref,
} from 'vue';

export interface UseTabVerticalScrollResult {
    scrollContainer: Ref<HTMLElement | null>;
    showTopMask: DeepReadonly<Ref<boolean>>;
    showBottomMask: DeepReadonly<Ref<boolean>>;
    scrollBounds: ComputedRef<{
        current: number;
        max: number;
        isAtStart: boolean;
        isAtEnd: boolean;
    } | null>;
    updateScrollMasks: () => void;
    scrollTabIntoView: (element: HTMLElement) => void;
}

/**
 * Handles vertical scroll state and masks for tab containers.
 *
 * Manages top/bottom gradient masks based on scroll position and provides
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

    const maskHeightTop = config.MASK_HEIGHT;

    const maskHeightBottom = config.MASK_HEIGHT;

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
     * Computed.
     */

    const scrollBounds = computed(() => {
        if (!scrollContainer.value) {
            return null;
        }

        const { scrollTop, scrollHeight, clientHeight } = scrollContainer.value;
        const roundedScrollTop = Math.round(scrollTop);
        const maxScroll = Math.max(
            0,
            Math.round(scrollHeight) - Math.round(clientHeight),
        );

        return {
            current: roundedScrollTop,
            max: maxScroll,
            isAtStart: roundedScrollTop <= scrollThreshold,
            isAtEnd:
                maxScroll <= scrollThreshold ||
                roundedScrollTop >= maxScroll - scrollThreshold,
        };
    });

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
            container.clientHeight < maskHeightTop * 2.5
                ? container.clientHeight / 4
                : maskHeightTop;
        const activeMaskHeightBottom =
            container.clientHeight < maskHeightBottom * 2.5
                ? container.clientHeight / 4
                : maskHeightBottom;

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

    onMounted(() => {
        cleanupScrollListeners = setupScrollListeners() ?? null;
        cleanupResizeObserver = setupResizeObserver().stop;
        cleanupMutationObserver = setupMutationObserver().stop;
        // Initial update
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

        // Computed
        scrollBounds,

        // Actions
        updateScrollMasks,
        scrollTabIntoView,
    };
}
