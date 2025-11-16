import { useResizeObserver } from '@vueuse/core';
import { computed, ComputedRef, Ref, ref, TemplateRef } from 'vue';

/**
 * Manages resizable panel's direction.
 */
export function useResponsiveResizable(
    thresholds: number[],
    element: TemplateRef,
): { thresholds: ComputedRef[] } {
    const elementWidth: Ref<number> = ref(
        // @ts-expect-error it is a mess to annotate the element properly.
        element.value?.$el?.contentRect.width ?? window.screen.width,
    );

    // @ts-expect-error it is a mess to annotate the element properly.
    useResizeObserver(element, entries => {
        const entry = entries[0];

        elementWidth.value = entry.contentRect.width;
    });

    const computedThresholds = thresholds.map(threshold =>
        computed(() => (elementWidth.value < threshold ? 'vertical' : 'horizontal')),
    );

    return {
        thresholds: computedThresholds,
    };
}
