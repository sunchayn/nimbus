import type { TabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { tabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import {
    calculateScrollToElement,
    getElementVisibility,
    getMaskVisibility,
    getScrollBounds,
} from '@/utils/scroll';
import type { ScrollBounds } from '@/utils/scroll/tab-scroll-utils';
import { useDebounceFn } from '@vueuse/core';
import {
    type ComputedRef,
    type DeepReadonly,
    type Ref,
    computed,
    nextTick,
    onMounted,
    onUnmounted,
    readonly,
    ref,
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
export function useTabHorizontalScroll(
    config?: Partial<TabNavigationScrollConfig>,
): UseTabHorizontalScrollResult {
    /*
     * Configuration.
     *
     * Note: variables are assigned explicitly to make the config keys referencable.
     */

    const maskWidth = config?.MASK_WIDTH ?? tabNavigationScrollConfig.MASK_WIDTH;

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
    const showLeftMask = ref(false);
    const showRightMask = ref(false);
    const savedScrollPosition = ref(0);

    /*
     * Computed.
     */

    const scrollBounds = computed(() =>
        scrollContainer.value
            ? getScrollBounds(scrollContainer.value, scrollThreshold)
            : null,
    );

    /*
     * Actions.
     */

    /**
     * Updates gradient mask visibility based on scroll position
     *
     * Uses debounced updates for better performance during scroll events.
     */
    const updateScrollMasks = () => {
        if (!scrollContainer.value) {
            return;
        }

        const bounds = getScrollBounds(scrollContainer.value, scrollThreshold);
        const maskVisibility = getMaskVisibility(bounds);

        showLeftMask.value = maskVisibility.showLeftMask;
        showRightMask.value = maskVisibility.showRightMask;
        savedScrollPosition.value = bounds.current;
    };

    /**
     * Debounced version of updateScrollMasks for scroll events
     */
    const debouncedUpdateMasks = useDebounceFn(updateScrollMasks, debounceDelay);

    /**
     * Scrolls a tab button into view, accounting for gradient masks
     *
     * Uses extracted calculation utilities for cleaner logic and better maintainability.
     */
    const scrollTabIntoView = (buttonElement: HTMLElement) => {
        if (!scrollContainer.value) {
            return;
        }

        const visibility = getElementVisibility(
            buttonElement,
            scrollContainer.value,
            maskWidth,
        );

        if (!visibility.isFullyVisible) {
            const targetScrollLeft = calculateScrollToElement(
                buttonElement,
                scrollContainer.value,
                maskWidth,
                scrollPadding,
            );

            scrollContainer.value.scrollTo({
                left: targetScrollLeft,
                behavior: 'smooth',
            });

            // Update masks after scrolling animation completes
            setTimeout(() => {
                updateScrollMasks();
            }, animationDuration);
        }
    };

    /**
     * Restores the previously saved scroll position when menu reopens
     */
    const restoreScrollPosition = async () => {
        await nextTick();

        if (scrollContainer.value && savedScrollPosition.value > 0) {
            scrollContainer.value.scrollLeft = savedScrollPosition.value;
        }

        updateScrollMasks();
    };

    /**
     * Sets up scroll event listeners
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

    onMounted(() => {
        cleanupScrollListeners = setupScrollListeners() ?? null;
    });

    onUnmounted(() => {
        cleanupScrollListeners?.();
        savedScrollPosition.value = 0;
    });

    return {
        // State
        scrollContainer,
        showLeftMask: readonly(showLeftMask),
        showRightMask: readonly(showRightMask),

        // Computed
        scrollBounds,

        // Actions
        updateScrollMasks,
        scrollTabIntoView,
        restoreScrollPosition,
    };
}
