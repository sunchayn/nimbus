import { DYNAMIC_PLACEHOLDER_PATTERN } from '@/config/dynamic-variables';

export interface MaskedRange {
    from: number;
    to: number;
}

export interface MaskedText {
    // Same length as the source text, so every position still matches.
    text: string;
    // The dynamic variables that stand for a whole value, outside any string.
    // A range starts at the colon before the value, where schema errors are reported.
    valueRanges: MaskedRange[];
}

/**
 * Finds which positions of a JSON text are inside a string.
 */
const findStringPositions = (text: string): boolean[] => {
    const inside: boolean[] = [];
    let inString = false;
    let escaped = false;

    for (const char of text) {
        if (!inString) {
            inString = char === '"';
            inside.push(false);

            continue;
        }

        inside.push(true);

        if (escaped) {
            escaped = false;
        } else if (char === '\\') {
            escaped = true;
        } else if (char === '"') {
            inString = false;
            inside[inside.length - 1] = false;
        }
    }

    return inside;
};

/**
 * Gets the position of the colon right before a value, or the value start without one.
 */
const findValueStart = (source: string, valueStart: number): number => {
    const before = source.slice(0, valueStart).trimEnd();

    return before.endsWith(':') ? before.length - 1 : valueStart;
};

/**
 * Replaces each dynamic variable such as {$uuid} with text of the same length that is valid JSON.
 * Inside a string it becomes filler characters, and elsewhere it becomes a string value.
 * Linting the result avoids syntax and schema errors that only exist because of the variable.
 */
export const maskDynamicVariables = (source: string): MaskedText => {
    const inside = findStringPositions(source);
    const valueRanges: MaskedRange[] = [];

    const text = source.replace(
        DYNAMIC_PLACEHOLDER_PATTERN,
        (match: string, _name: string, offset: number) => {
            if (inside[offset]) {
                return 'x'.repeat(match.length);
            }

            valueRanges.push({
                from: findValueStart(source, offset),
                to: offset + match.length,
            });

            return `"${'x'.repeat(match.length - 2)}"`;
        },
    );

    return { text, valueRanges };
};
