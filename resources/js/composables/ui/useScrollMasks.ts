import type { TabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { tabNavigationScrollConfig } from '@/config/tab-navigation-scroll';
import { useDebounceFn } from '@vueuse/core';
import { type Ref, ref, shallowRef } from 'vue';

export interface ScrollMaskState {
    showStartMask: boolean;
    showEndMask: boolean;
    /** Current scroll offset along the axis, when the caller wants it remembered. */
    position?: number;
}

export interface UseScrollMasksOptions {
    config?: Partial<TabNavigationScrollConfig>;

    /** The property passed to `scrollTo` for the axis. */
    scrollProperty: 'left' | 'top';

    /** Computes which masks are visible for the container. */
    computeMasks: (container: HTMLElement, scrollThreshold: number) => ScrollMaskState;

    /**
     * Computes the scroll offset that reveals the element.
     * Returns null when the element is already visible.
     */
    computeScrollTarget: (
        element: HTMLElement,
        container: HTMLElement,
        settings: ScrollSettings,
    ) => number | null;
}

export type ScrollSettings = Pick<
    TabNavigationScrollConfig,
    'SCROLL_THRESHOLD' | 'SCROLL_PADDING' | 'ANIMATION_DURATION' | 'DEBOUNCE_DELAY'
>;

export interface UseScrollMasksResult {
    scrollContainer: Ref<HTMLElement | null>;
    showStartMask: Ref<boolean>;
    showEndMask: Ref<boolean>;
    savedScrollPosition: Ref<number>;
    scrollThreshold: number;
    updateScrollMasks: () => void;
    debouncedUpdateMasks: () => Promise<void> | void;
    scrollElementIntoView: (element: HTMLElement) => void;
}

/**
 * Axis independent core for scroll containers with fading edge masks.
 *
 * The axis specific wrappers provide the geometry and own their observers.
 */
export const useScrollMasks = (options: UseScrollMasksOptions): UseScrollMasksResult => {
    const { config, scrollProperty, computeMasks, computeScrollTarget } = options;

    const settings: ScrollSettings = {
        SCROLL_THRESHOLD:
            config?.SCROLL_THRESHOLD ?? tabNavigationScrollConfig.SCROLL_THRESHOLD,
        SCROLL_PADDING:
            config?.SCROLL_PADDING ?? tabNavigationScrollConfig.SCROLL_PADDING,
        ANIMATION_DURATION:
            config?.ANIMATION_DURATION ?? tabNavigationScrollConfig.ANIMATION_DURATION,
        DEBOUNCE_DELAY:
            config?.DEBOUNCE_DELAY ?? tabNavigationScrollConfig.DEBOUNCE_DELAY,
    };

    const scrollContainer = ref<HTMLElement | null>(null);
    const showStartMask = shallowRef(false);
    const showEndMask = shallowRef(false);
    const savedScrollPosition = shallowRef(0);

    const updateScrollMasks = (): void => {
        if (!scrollContainer.value) {
            return;
        }

        const state = computeMasks(scrollContainer.value, settings.SCROLL_THRESHOLD);

        showStartMask.value = state.showStartMask;
        showEndMask.value = state.showEndMask;

        if (state.position !== undefined) {
            savedScrollPosition.value = state.position;
        }
    };

    const debouncedUpdateMasks = useDebounceFn(
        updateScrollMasks,
        settings.DEBOUNCE_DELAY,
    );

    const scrollElementIntoView = (element: HTMLElement): void => {
        if (!scrollContainer.value) {
            return;
        }

        const target = computeScrollTarget(element, scrollContainer.value, settings);

        if (target === null) {
            return;
        }

        scrollContainer.value.scrollTo({ [scrollProperty]: target, behavior: 'smooth' });

        // Masks are refreshed once the smooth scroll animation has finished.
        setTimeout(() => {
            updateScrollMasks();
        }, settings.ANIMATION_DURATION);
    };

    return {
        scrollContainer,
        showStartMask,
        showEndMask,
        savedScrollPosition,
        scrollThreshold: settings.SCROLL_THRESHOLD,
        updateScrollMasks,
        debouncedUpdateMasks,
        scrollElementIntoView,
    };
};
