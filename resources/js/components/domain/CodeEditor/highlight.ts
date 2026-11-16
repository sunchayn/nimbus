import { HighlightStyle, syntaxHighlighting } from '@codemirror/language';
import type { Extension } from '@codemirror/state';
import { tags } from '@lezer/highlight';

/**
 * The syntax colors of JSON, taken from the status tokens of the app.
 */
export const jsonHighlight: Extension = syntaxHighlighting(
    HighlightStyle.define([
        { tag: tags.propertyName, color: 'var(--ui-text-highlighted)' },
        { tag: tags.string, color: 'var(--ui-success)' },
        { tag: tags.number, color: 'var(--ui-info)' },
        { tag: [tags.bool, tags.null], color: 'var(--ui-secondary)' },
        {
            tag: [tags.punctuation, tags.separator, tags.brace, tags.squareBracket],
            color: 'var(--ui-text-dimmed)',
        },
        { tag: tags.invalid, color: 'var(--ui-error)' },
    ]),
);
