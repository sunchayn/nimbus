import { DYNAMIC_VARIABLES, resolveDynamicVariable } from '@/config/dynamic-variables';

export type SuggestionGroup = 'Variables' | 'Dynamic';

export type PlaceholderKind = 'variable' | 'dynamic';

export interface VariableSuggestion {
    group: SuggestionGroup;
    // The name as written in the placeholder, such as token or uuid.
    name: string;
    // The complete placeholder to insert, such as {{token}} or {$uuid}.
    token: string;
    description?: string;
    // A sample value for a dynamic variable.
    sample?: string;
}

export interface OpenPlaceholder {
    kind: PlaceholderKind;
    // The index of the opening braces.
    from: number;
    query: string;
}

/**
 * Finds the placeholder being typed at the caret.
 * {{na is an environment variable and {$uu is a dynamic variable.
 */
export const findOpenPlaceholder = (
    text: string,
    caret: number,
): OpenPlaceholder | null => {
    const before = text.slice(0, caret);
    const variableFrom = before.lastIndexOf('{{');
    const dynamicFrom = before.lastIndexOf('{$');
    const isDynamic =
        dynamicFrom !== -1 &&
        dynamicFrom > variableFrom &&
        before[dynamicFrom - 1] !== '{';

    if (isDynamic) {
        const query = before.slice(dynamicFrom + 2);

        return /^[A-Za-z0-9]*$/.test(query)
            ? { kind: 'dynamic', from: dynamicFrom, query }
            : null;
    }

    if (variableFrom === -1) {
        return null;
    }

    const query = before.slice(variableFrom + 2);

    return query.includes('}')
        ? null
        : { kind: 'variable', from: variableFrom, query: query.trim() };
};

/**
 * Lists the suggestions that match what was typed for the kind of placeholder.
 */
export const getVariableSuggestions = (
    kind: PlaceholderKind,
    query: string,
    variableNames: string[],
): VariableSuggestion[] => {
    const needle = query.toLowerCase();
    const matches = (name: string) => name.toLowerCase().includes(needle);

    if (kind === 'variable') {
        return variableNames.filter(matches).map(name => ({
            group: 'Variables',
            name,
            token: `{{${name}}}`,
        }));
    }

    return DYNAMIC_VARIABLES.filter(variable => matches(variable.name)).map(variable => ({
        group: 'Dynamic',
        name: variable.name,
        token: `{$${variable.name}}`,
        description: variable.description,
        sample: resolveDynamicVariable(variable.name) ?? undefined,
    }));
};

/**
 * Gets how many closing braces right after the caret the completed placeholder replaces.
 */
export const getClosingLength = (
    kind: PlaceholderKind,
    textAfterCaret: string,
): number => {
    const maxLength = kind === 'dynamic' ? 1 : 2;
    let length = 0;

    while (length < maxLength && textAfterCaret[length] === '}') {
        length++;
    }

    return length;
};

/**
 * Builds the text with the placeholder completed, and the caret position after it.
 */
export const applySuggestion = (
    text: string,
    caret: number,
    placeholder: OpenPlaceholder,
    suggestion: VariableSuggestion,
): { text: string; caret: number } => {
    const closing = getClosingLength(placeholder.kind, text.slice(caret));

    return {
        text:
            text.slice(0, placeholder.from) +
            suggestion.token +
            text.slice(caret + closing),
        caret: placeholder.from + suggestion.token.length,
    };
};
