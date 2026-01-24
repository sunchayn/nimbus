// composables/useRouteSegmentSelection.ts
import type { Ref } from 'vue';
import { nextTick, ref, watch } from 'vue';

export interface UseRouteSegmentSelectionOptions {
    /**
     * The endpoint URL to watch for changes
     */
    endpoint: Ref<string>;
}

export interface UseRouteSegmentSelectionReturn {
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
    variableSegmentIndices: Ref<number[]>;
}

interface SegmentPosition {
    start: number;
    end: number;
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
export function useRouteSegmentSelection(
    options: UseRouteSegmentSelectionOptions,
): UseRouteSegmentSelectionReturn {
    const { endpoint } = options;

    const variableSegmentIndices = ref<number[]>([]);

    /**
     * Identifies which segments in a URL path are variables (wrapped in braces).
     *
     * @param url - The URL path to analyze (e.g., 'api/users/{id}/posts')
     * @returns Array of segment indices that are variables (0-based)
     */
    const identifyVariableSegments = (url: string): number[] => {
        const segments = url.split('/');
        const indices: number[] = [];

        segments.forEach((segment: string, index: number) => {
            const isVariable = segment.startsWith('{') && segment.endsWith('}');

            if (isVariable) {
                indices.push(index);
            }
        });

        return indices;
    };

    /**
     * Checks if a character is an opening brace.
     */
    const isOpeningBrace = (char: string): boolean => char === '{';

    /**
     * Checks if a character is a closing brace.
     */
    const isClosingBrace = (char: string): boolean => char === '}';

    /**
     * Searches backward from cursor position to find an opening brace.
     * Returns -1 if a closing brace is found first or no opening brace exists.
     */
    const findOpeningBracePosition = (text: string, cursorPos: number): number => {
        for (let i = cursorPos; i >= 0; i--) {
            if (isOpeningBrace(text[i])) {
                return i;
            }
            if (isClosingBrace(text[i])) {
                return -1;
            }
        }

        return -1;
    };

    /**
     * Searches forward from a position to find a closing brace.
     * Returns -1 if an opening brace is found first or no closing brace exists.
     */
    const findClosingBracePosition = (text: string, startPos: number): number => {
        for (let i = startPos; i < text.length; i++) {
            if (isClosingBrace(text[i])) {
                return i + 1; // +1 to include the closing brace
            }
            if (isOpeningBrace(text[i])) {
                return -1;
            }
        }

        return -1;
    };

    /**
     * Finds the position of a segment containing braces at the cursor position.
     *
     * @param text - The full input text
     * @param cursorPos - Current cursor position
     * @returns Segment position or null if not found
     */
    const findBraceSegment = (
        text: string,
        cursorPos: number,
    ): SegmentPosition | null => {
        const openingBracePos = findOpeningBracePosition(text, cursorPos);

        if (openingBracePos === -1) {
            return null;
        }

        const closingBracePos = findClosingBracePosition(text, cursorPos);

        if (closingBracePos === -1) {
            return null;
        }

        return {
            start: openingBracePos,
            end: closingBracePos,
        };
    };

    /**
     * Finds which segment index the cursor is currently in.
     */
    const findSegmentIndexAtCursor = (text: string, cursorPos: number): number | null => {
        const segments = text.split('/');
        let charCount = 0;

        for (let i = 0; i < segments.length; i++) {
            const segmentLength = segments[i].length;
            const segmentStart = charCount;
            const segmentEnd = charCount + segmentLength;

            const isCursorInSegment =
                cursorPos >= segmentStart && cursorPos <= segmentEnd;

            if (isCursorInSegment) {
                return i;
            }

            charCount += segmentLength + 1; // +1 for the '/' separator
        }

        return null;
    };

    /**
     * Calculates the start and end positions of a segment by its index.
     */
    const getSegmentPosition = (
        text: string,
        segmentIndex: number,
    ): SegmentPosition | null => {
        const segments = text.split('/');

        if (segmentIndex >= segments.length) {
            return null;
        }

        let charCount = 0;

        for (let i = 0; i < segmentIndex; i++) {
            charCount += segments[i].length + 1; // +1 for the '/' separator
        }

        return {
            start: charCount,
            end: charCount + segments[segmentIndex].length,
        };
    };

    /**
     * Finds the position of a segment that was originally a variable.
     *
     * @param text - The full input text
     * @param cursorPos - Current cursor position
     * @returns Segment position or null if not found
     */
    const findOriginalVariableSegment = (
        text: string,
        cursorPos: number,
    ): SegmentPosition | null => {
        const segmentIndex = findSegmentIndexAtCursor(text, cursorPos);

        if (segmentIndex === null) {
            return null;
        }

        const isOriginalVariable = variableSegmentIndices.value.includes(segmentIndex);

        if (!isOriginalVariable) {
            return null;
        }

        return getSegmentPosition(text, segmentIndex);
    };

    /**
     * Selects a text range in the input element.
     */
    const selectRange = (input: HTMLInputElement, position: SegmentPosition): void => {
        nextTick(() => {
            input.setSelectionRange(position.start, position.end);
        });
    };

    /**
     * Handles click events on the input to auto-select route segments.
     * Priority: 1) Segments with braces, 2) Segments that were originally variables
     */
    const handleClick = (event: MouseEvent): void => {
        const input = event.target as HTMLInputElement;

        if (!input || input.selectionStart === null) {
            return;
        }

        const cursorPos = input.selectionStart;
        const text = input.value;

        // First priority: Check if we're inside a segment with braces
        const braceSegment = findBraceSegment(text, cursorPos);

        if (braceSegment) {
            selectRange(input, braceSegment);

            return;
        }

        // Second priority: Check if cursor is in a segment that was originally a variable
        const originalSegment = findOriginalVariableSegment(text, cursorPos);

        if (originalSegment) {
            selectRange(input, originalSegment);
        }
    };

    // Watch for endpoint changes to track variable segments
    watch(
        endpoint,
        (newValue: string) => {
            const hasVariableSegments = newValue?.includes('{');

            if (hasVariableSegments) {
                variableSegmentIndices.value = identifyVariableSegments(newValue);
            }
        },
        { immediate: true },
    );

    return {
        handleClick,
        identifyVariableSegments,
        variableSegmentIndices,
    };
}
