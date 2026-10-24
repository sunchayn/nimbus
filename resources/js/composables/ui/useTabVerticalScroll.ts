import { useScrollMasks } from '@/composables/ui/useScrollMasks';
import type { TabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { useMutationObserver, useResizeObserver } from '@vueuse/core';
import { nextTick, onUnmounted, readonly, watch, type DeepReadonly, type Ref } from 'vue';

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
export const useTabVerticalScroll = (
    config: Partial<TabNavigationScrollConfig> & {
        /**
         * Mask height in pixels (Vertical).
         *
         * Height of the gradient masks that fade content at scroll boundaries vertically.
         * Provides visual indication of scrollable content beyond viewport.
         */
        MASK_HEIGHT: number;
    },
): UseTabVerticalScrollResult => {
    const {
        scrollContainer,
        showStartMask,
        showEndMask,
        updateScrollMasks,
        debouncedUpdateMasks,
        scrollElementIntoView,
    } = useScrollMasks({
        config,
        scrollProperty: 'top',
        computeMasks: (container, scrollThreshold) => {
            const { scrollTop, scrollHeight, clientHeight } = container;

            // Round values to avoid sub-pixel jitter during layout shifts/resizing
            const roundedScrollTop = Math.round(scrollTop);
            const roundedScrollHeight = Math.round(scrollHeight);
            const roundedClientHeight = Math.round(clientHeight);
            const maxScroll = Math.max(0, roundedScrollHeight - roundedClientHeight);

            // Content only slightly larger than the viewport does not show masks.
            const contentFits = maxScroll <= scrollThreshold;

            const isAtStart = roundedScrollTop <= scrollThreshold;
            const isAtEnd =
                contentFits || roundedScrollTop >= maxScroll - scrollThreshold;

            return {
                showStartMask: !isAtStart && !contentFits,
                showEndMask: !isAtEnd && !contentFits,
            };
        },
        computeScrollTarget: (element, container, settings) => {
            const containerRect = container.getBoundingClientRect();
            const elementRect = element.getBoundingClientRect();

            const relativeTop = elementRect.top - containerRect.top + container.scrollTop;
            const relativeBottom = relativeTop + elementRect.height;

            // The mask height must not exceed the container height.
            const activeMaskHeight =
                container.clientHeight < config.MASK_HEIGHT * 2.5
                    ? container.clientHeight / 4
                    : config.MASK_HEIGHT;

            const visibleTop = container.scrollTop + activeMaskHeight;
            const visibleBottom =
                container.scrollTop + container.clientHeight - activeMaskHeight;

            let targetScrollTop = container.scrollTop;

            if (relativeTop < visibleTop) {
                targetScrollTop = Math.max(
                    0,
                    relativeTop - activeMaskHeight - settings.SCROLL_PADDING,
                );
            } else if (relativeBottom > visibleBottom) {
                targetScrollTop =
                    relativeBottom -
                    container.clientHeight +
                    activeMaskHeight +
                    settings.SCROLL_PADDING;
            }

            return targetScrollTop === container.scrollTop ? null : targetScrollTop;
        },
    });

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

    /*
     * Lifecycle.
     */

    let cleanupScrollListeners: (() => void) | null = null;
    let cleanupResizeObserver: (() => void | undefined) | null = null;
    let cleanupMutationObserver: (() => void | undefined) | null = null;

    watch(scrollContainer, () => {
        cleanupScrollListeners = setupScrollListeners() ?? null;
        cleanupResizeObserver = useResizeObserver(
            scrollContainer,
            debouncedUpdateMasks,
        ).stop;
        cleanupMutationObserver = useMutationObserver(
            scrollContainer,
            debouncedUpdateMasks,
            {
                childList: true,
                subtree: true,
            },
        ).stop;

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
        showTopMask: readonly(showStartMask),
        showBottomMask: readonly(showEndMask),

        // Actions
        updateScrollMasks,
        scrollTabIntoView: scrollElementIntoView,
    };
};
