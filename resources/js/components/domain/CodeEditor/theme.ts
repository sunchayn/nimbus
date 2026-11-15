import { type Extension } from '@codemirror/state';
import { EditorView } from '@codemirror/view';

const tint = (token: string, percent: number): string =>
    `color-mix(in oklab, var(${token}) ${percent}%, transparent)`;

// A dot is the status mark of the app, so the gutter and the tooltips use the same one.
const statusDot = (token: string) => ({
    content: 'none',
    backgroundColor: `var(${token})`,
});

/**
 * The look of the editor, built only from the tokens of the app.
 * The tokens switch with the theme, so no dark variant is needed here.
 */
export const editorTheme: Extension = EditorView.theme({
    '&': {
        color: 'var(--ui-text)',
        backgroundColor: 'var(--ui-bg)',
        fontSize: 'var(--text-xs)',
    },
    '&.cm-focused': { outline: 'none' },
    '.cm-scroller': { fontFamily: 'var(--font-mono)', lineHeight: '1.6' },
    '.cm-content': { caretColor: 'var(--ui-text-highlighted)' },
    '.cm-cursor, .cm-dropCursor': {
        borderLeftColor: 'var(--ui-text-highlighted)',
        borderLeftWidth: '1.5px',
    },
    '.cm-placeholder': { color: 'var(--ui-text-dimmed)' },

    '.cm-selectionBackground, .cm-content ::selection': {
        backgroundColor: `${tint('--ui-info', 25)} !important`,
    },
    '&.cm-focused > .cm-scroller > .cm-selectionLayer .cm-selectionBackground': {
        backgroundColor: `${tint('--ui-info', 35)} !important`,
    },
    // The line tint is see-through, so the selection layer under it stays visible.
    '.cm-activeLine, .cm-activeLineGutter': { backgroundColor: tint('--ui-text', 5) },
    '&.cm-focused .cm-matchingBracket': {
        backgroundColor: tint('--ui-text', 12),
        outline: 'none',
    },
    '&.cm-focused .cm-nonmatchingBracket': {
        backgroundColor: tint('--ui-error', 20),
        outline: 'none',
    },

    '.cm-gutters': {
        backgroundColor: 'var(--ui-bg)',
        color: 'var(--ui-text-dimmed)',
        border: 'none',
    },
    '.cm-foldGutter .cm-gutterElement:hover': { color: 'var(--ui-text-highlighted)' },

    /*
     * Tooltips.
     * Every tooltip uses the surface of the popovers and the menus.
     */
    '.cm-tooltip': {
        backgroundColor: 'var(--color-overlay)',
        color: 'var(--ui-text)',
        border: 'var(--spacing-line) solid var(--ui-border)',
        borderRadius: 'var(--radius-sm)',
        boxShadow: 'var(--elevation-overlay)',
        fontSize: 'var(--text-xs)',
        lineHeight: 'var(--text-xs--line-height)',
        overflow: 'hidden',
    },
    '.cm-tooltip .cm-tooltip-arrow': { display: 'none' },
    '.cm-tooltip-section:not(:first-child)': { borderTop: 'none' },

    // The type hover of the JSON schema.
    '.cm6-json-schema-hover': {
        display: 'flex',
        flexDirection: 'column',
        gap: 'var(--spacing-tight)',
        padding: 'var(--spacing-tight) var(--spacing-base)',
    },
    '.cm6-json-schema-hover p': { margin: '0' },
    '.cm6-json-schema-hover--description': {
        color: 'var(--ui-text)',
        maxWidth: 'var(--max-width-popover)',
        overflowWrap: 'anywhere',
    },
    '.cm6-json-schema-hover--description code': {
        fontFamily: 'var(--font-mono)',
        fontWeight: '600',
    },
    // The facts are smaller than the description, so the label never outweighs the main text.
    '.cm6-json-schema-hover--code': {
        fontFamily: 'var(--font-mono)',
        fontSize: 'var(--text-xxs)',
        lineHeight: 'var(--text-xxs--line-height)',
        color: 'var(--ui-text-muted)',
        maxWidth: 'var(--max-width-popover)',
        overflowWrap: 'anywhere',
    },

    /*
     * Lint.
     */
    '.cm-lintRange': { backgroundImage: 'none', paddingBottom: '0' },
    '.cm-lintRange-error, .cm-lintRange-warning, .cm-lintRange-info': {
        textDecorationLine: 'underline',
        textDecorationStyle: 'wavy',
        textDecorationThickness: 'var(--spacing-line)',
        textUnderlineOffset: '3px',
        textDecorationSkipInk: 'none',
    },
    '.cm-lintRange-error': { textDecorationColor: 'var(--ui-error)' },
    '.cm-lintRange-warning': { textDecorationColor: 'var(--ui-warning)' },
    '.cm-lintRange-info': { textDecorationColor: 'var(--ui-info)' },
    '.cm-lintRange-active': { backgroundColor: tint('--ui-error', 10) },
    '.cm-lintPoint-error:after': { borderBottomColor: 'var(--ui-error)' },
    '.cm-lintPoint-warning:after': { borderBottomColor: 'var(--ui-warning)' },
    '.cm-lintPoint-info:after': { borderBottomColor: 'var(--ui-info)' },

    '.cm-gutter-lint': { width: 'var(--spacing-loose)' },
    '.cm-gutter-lint .cm-gutterElement': {
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '0',
    },
    '.cm-lint-marker': {
        width: 'var(--spacing-dot-sm)',
        height: 'var(--spacing-dot-sm)',
        borderRadius: '9999px',
    },
    '.cm-lint-marker-error': statusDot('--ui-error'),
    '.cm-lint-marker-warning': statusDot('--ui-warning'),
    '.cm-lint-marker-info': statusDot('--ui-info'),

    // The diagnostic is a status dot, the message, and the source below it.
    '.cm-tooltip-lint': { margin: '0', padding: '0' },
    '.cm-diagnostic': {
        '--diagnostic-color': 'var(--ui-text-dimmed)',
        display: 'grid',
        gridTemplateColumns: 'auto 1fr',
        columnGap: 'var(--spacing-base)',
        alignItems: 'baseline',
        margin: '0',
        padding: 'var(--spacing-tight) var(--spacing-base)',
        border: 'none',
        borderTop: 'var(--spacing-line) solid var(--ui-border)',
        color: 'var(--ui-text)',
        fontSize: 'var(--text-xs)',
        lineHeight: 'var(--text-xs--line-height)',
        '&:first-child': { borderTop: 'none' },
        '&::before': {
            content: '""',
            width: 'var(--spacing-dot-sm)',
            height: 'var(--spacing-dot-sm)',
            borderRadius: '9999px',
            backgroundColor: 'var(--diagnostic-color)',
            alignSelf: 'center',
        },
    },
    '.cm-diagnostic-error': { '--diagnostic-color': 'var(--ui-error)' },
    '.cm-diagnostic-warning': { '--diagnostic-color': 'var(--ui-warning)' },
    '.cm-diagnostic-info': { '--diagnostic-color': 'var(--ui-info)' },
    '.cm-diagnosticText code': { fontFamily: 'inherit', fontWeight: '600' },
    '.cm-diagnosticSource': {
        gridColumn: '2',
        fontSize: 'var(--text-xxs)',
        lineHeight: 'var(--text-xxs--line-height)',
        color: 'var(--ui-text-dimmed)',
        opacity: '1',
    },
});
