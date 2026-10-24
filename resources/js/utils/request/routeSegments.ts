export interface SegmentPosition {
    start: number;
    end: number;
}

/**
 * Identifies which segments in a URL path are route variables (wrapped in single braces).
 *
 * Environment variables (double braces like {{host}}) are excluded from auto-selection.
 * The env variable replacement logic manages them.
 *
 * @param url - The URL path to analyze (e.g., 'api/users/{id}/posts')
 * @returns Array of segment indices that are route variables (0-based)
 */
export const identifyVariableSegments = (url: string): number[] => {
    const segments = url.split('/');
    const indices: number[] = [];

    segments.forEach((segment: string, index: number) => {
        // A segment is a candidate for auto-selection only if it is a single-brace route variable.
        const isRouteVariable =
            segment.startsWith('{') &&
            segment.endsWith('}') &&
            !segment.startsWith('{{') &&
            !segment.endsWith('}}');

        if (isRouteVariable) {
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
export const findOpeningBracePosition = (text: string, cursorPos: number): number => {
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
export const findClosingBracePosition = (text: string, startPos: number): number => {
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
export const findBraceSegment = (
    text: string,
    cursorPos: number,
): SegmentPosition | null => {
    const openingBracePos = findOpeningBracePosition(text, cursorPos);

    if (openingBracePos === -1) {
        return null;
    }

    // Exclude environment variables (double braces) from on-the-fly brace detection.
    // We only want to auto-select single-brace path parameters.
    const isDoubleBrace =
        text[openingBracePos - 1] === '{' || text[openingBracePos + 1] === '{';

    if (isDoubleBrace) {
        return null;
    }

    const closingBracePos = findClosingBracePosition(text, cursorPos);

    if (closingBracePos === -1) {
        return null;
    }

    // Verify the closing side is also not part of a double brace.
    if (text[closingBracePos] === '}') {
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
export const findSegmentIndexAtCursor = (
    text: string,
    cursorPos: number,
): number | null => {
    const segments = text.split('/');
    let charCount = 0;

    for (let i = 0; i < segments.length; i++) {
        const segmentLength = segments[i].length;
        const segmentStart = charCount;
        const segmentEnd = charCount + segmentLength;

        const isCursorInSegment = cursorPos >= segmentStart && cursorPos <= segmentEnd;

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
export const getSegmentPosition = (
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
 * @param variableSegmentIndices - Indices of segments that were originally variables
 * @returns Segment position or null if not found
 */
export const findOriginalVariableSegment = (
    text: string,
    cursorPos: number,
    variableSegmentIndices: readonly number[],
): SegmentPosition | null => {
    const segmentIndex = findSegmentIndexAtCursor(text, cursorPos);

    if (segmentIndex === null) {
        return null;
    }

    const isOriginalVariable = variableSegmentIndices.includes(segmentIndex);

    if (!isOriginalVariable) {
        return null;
    }

    return getSegmentPosition(text, segmentIndex);
};
