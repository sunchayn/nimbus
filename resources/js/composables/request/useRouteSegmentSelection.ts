import { type DeepReadonly, type Ref, nextTick, readonly, ref, watch } from 'vue';

import {
    type SegmentPosition,
    findBraceSegment,
    findOriginalVariableSegment,
    identifyVariableSegments,
} from '@/utils/request/routeSegments';

export interface UseRouteSegmentSelectionOptions {
    /**
     * The endpoint URL to watch for changes
     */
    endpoint: Ref<string>;
}

export interface UseRouteSegmentSelectionResult {
    /**
     * Handler to be attached to the input's click event
     */
    handleClick: (event: MouseEvent) => void;

    /**
     * Manually identify variable segments in a URL
     */
    identifyVariableSegments: (url: string) => number[];

    /**
     * The indices of segments that were originally variables
     */
    variableSegmentIndices: DeepReadonly<Ref<number[]>>;
}

/**
 * Composable for handling automatic selection of route segments in endpoint inputs.
 *
 * This composable tracks variable segments (enclosed in braces like {id}) in route URLs
 * and enables automatic selection when users click on those segments, even after
 * they've been modified to contain actual values.
 *
 * @example
 * ```ts
 * const endpoint = ref('api/users/{id}/posts/{postId}');
 * const { handleClick } = useRouteSegmentSelection({ endpoint });
 *
 * // In template: <input v-model="endpoint" @click="handleClick" />
 * // Clicking on {id} or {postId} will select the entire segment
 * // Even after changing to 'api/users/123/posts/456', clicking on '123' or '456' still selects the whole segment
 * ```
 */
export const useRouteSegmentSelection = (
    options: UseRouteSegmentSelectionOptions,
): UseRouteSegmentSelectionResult => {
    /*
     * Dependencies.
     */

    const { endpoint } = options;

    /*
     * State.
     */

    const variableSegmentIndices = ref<number[]>([]);

    /*
     * Utilities.
     */

    /**
     * Selects a text range in the input element.
     */
    const selectRange = (input: HTMLInputElement, position: SegmentPosition): void => {
        nextTick(() => {
            input.setSelectionRange(position.start, position.end);
        });
    };

    /*
     * Actions.
     */

    /**
     * Handles click events on the input to auto-select route segments.
     * Segments with braces come first, then segments that were originally variables.
     */
    const handleClick = (event: MouseEvent): void => {
        const input = event.target as HTMLInputElement;

        if (!input || input.selectionStart === null) {
            return;
        }

        const cursorPos = input.selectionStart;
        const text = input.value;

        const braceSegment = findBraceSegment(text, cursorPos);

        if (braceSegment) {
            selectRange(input, braceSegment);

            return;
        }

        // Fall back to a segment that was originally a variable.
        const originalSegment = findOriginalVariableSegment(
            text,
            cursorPos,
            variableSegmentIndices.value,
        );

        if (originalSegment) {
            selectRange(input, originalSegment);
        }
    };

    /*
     * Watchers.
     */

    watch(
        endpoint,
        (newValue: string) => {
            const hasVariableSegments = newValue.includes('{');

            if (hasVariableSegments) {
                variableSegmentIndices.value = identifyVariableSegments(newValue);
            }
        },
        { immediate: true },
    );

    return {
        // Actions
        handleClick,
        identifyVariableSegments,

        // State
        variableSegmentIndices: readonly(variableSegmentIndices),
    };
};
