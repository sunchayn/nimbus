import { useResizeObserver } from '@vueuse/core';
import { type ComputedRef, type Ref, type TemplateRef, computed, ref } from 'vue';

export interface UseResponsiveResizableResult {
    thresholds: ComputedRef<'vertical' | 'horizontal'>[];
}

/**
 * Manages resizable panel's direction based on width thresholds.
 */
export function useResponsiveResizable(
    thresholds: number[],
    element: TemplateRef,
): UseResponsiveResizableResult {
    /*
     * State.
     */

    const elementWidth: Ref<number> = ref(
        // @ts-expect-error it is a mess to annotate the element properly.
        element.value?.$el?.contentRect.width ?? window.screen.width,
    );

    /*
     * Lifecycle/Observer.
     */

    // @ts-expect-error it is a mess to annotate the element properly.
    useResizeObserver(element, entries => {
        const entry = entries[0];

        elementWidth.value = entry.contentRect.width;
    });

    /*
     * Computed.
     */

    const computedThresholds = thresholds.map(threshold =>
        computed<'vertical' | 'horizontal'>(() =>
            elementWidth.value < threshold ? 'vertical' : 'horizontal',
        ),
    );

    return {
        // Computed
        thresholds: computedThresholds,
    };
}
