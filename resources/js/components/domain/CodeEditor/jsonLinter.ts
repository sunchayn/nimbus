import { maskDynamicVariables } from '@/utils/dynamicVariableMask';
import { linter } from '@codemirror/lint';
import type { ParseError } from 'jsonc-parser';
import { parse as parseJson, printParseErrorCode } from 'jsonc-parser';

export const jsonLinter = linter(view => {
    const text = view.state.doc.toString();

    if (text.length === 0) {
        return [];
    }

    const errors: ParseError[] = [];

    // A dynamic variable is valid wherever a JSON value can be written.
    parseJson(maskDynamicVariables(text).text, errors);

    return errors.map(error => ({
        from: error.offset,
        to: error.offset + 1, // Highlight the problematic area
        severity: 'error',
        message: printParseErrorCode(error.error), // Human-readable error message
    }));
});
