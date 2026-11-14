import { useMediaQuery, useTimeoutFn } from '@vueuse/core';
import { nextTick, ref, type ComponentPublicInstance, type Ref } from 'vue';

/** How long the highlight of the element a jump points at stays. */
export const JUMP_HIGHLIGHT_MS = 1200;

export interface JumpHighlight {
    /** The key of the element that is marked right now. */
    highlighted: Ref<string | null>;
    /** Returns the ref function that registers an element under a key. */
    bind: (key: string) => (element: Element | ComponentPublicInstance | null) => void;
    /** Marks the element under a key and scrolls to it. */
    jump: (key: string) => Promise<void>;
}

/**
 * Scrolls an element into view and marks it for a moment, so a jump from another tab shows where it landed.
 * Elements register under a key with `bind`, and `jump` points at one of them.
 */
export const useJumpHighlight = (): JumpHighlight => {
    const highlighted = ref<string | null>(null);

    const elements = new Map<string, HTMLElement>();

    const prefersReducedMotion = useMediaQuery('(prefers-reduced-motion: reduce)');

    const { start: startTimer } = useTimeoutFn(
        () => {
            highlighted.value = null;
        },
        JUMP_HIGHLIGHT_MS,
        { immediate: false },
    );

    const bind = (key: string) => (element: Element | ComponentPublicInstance | null) => {
        if (element instanceof HTMLElement) {
            elements.set(key, element);
        } else {
            elements.delete(key);
        }
    };

    const jump = async (key: string) => {
        highlighted.value = key;
        startTimer();

        // Wait for the tab switch and any open state to render before scrolling.
        await nextTick();

        elements.get(key)?.scrollIntoView?.({
            block: 'nearest',
            behavior: prefersReducedMotion.value ? 'auto' : 'smooth',
        });
    };

    return { highlighted, bind, jump };
};
