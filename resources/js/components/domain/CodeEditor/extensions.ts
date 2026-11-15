import { jsonHighlight } from '@/components/domain/CodeEditor/highlight';
import { jsonLinter } from '@/components/domain/CodeEditor/jsonLinter';
import { dynamicAwareJsonSchema } from '@/components/domain/CodeEditor/jsonSchemaExtension';
import { editorTheme } from '@/components/domain/CodeEditor/theme';
import { CODE_EDITOR_INDENT } from '@/config/code-editor';
import { DYNAMIC_PLACEHOLDER_PATTERN } from '@/config/dynamic-variables';
import {
    EnvVariableCheckStatus,
    PLACEHOLDER_PATTERN,
} from '@/interfaces/common/env-vars';
import {
    findOpenPlaceholder,
    getClosingLength,
    getVariableSuggestions,
} from '@/utils/variableSuggestions';
import { autocompletion, type CompletionContext } from '@codemirror/autocomplete';
import { json } from '@codemirror/lang-json';
import { indentUnit } from '@codemirror/language';
import { type Diagnostic, linter, lintGutter } from '@codemirror/lint';
import { EditorState, type Extension, RangeSetBuilder } from '@codemirror/state';
import {
    Decoration,
    type DecorationSet,
    EditorView,
    ViewPlugin,
    type ViewUpdate,
} from '@codemirror/view';
import type { JSONSchema7 } from 'json-schema';

// Keeps writing assistants such as Grammarly away from code editors.
const noWritingAssistants = EditorView.contentAttributes.of({
    spellcheck: 'false',
    'data-gramm': 'false',
    'data-gramm_editor': 'false',
    'data-enable-grammarly': 'false',
});

export const jsonExtensions = (
    readonly: boolean,
    schema: JSONSchema7 | undefined,
): Extension[] => {
    const extensions = commonExtensions(readonly);

    extensions.push(json(), jsonHighlight);

    if (schema !== undefined) {
        extensions.push(...dynamicAwareJsonSchema(schema));
    }

    if (!readonly) {
        extensions.push(jsonLinter);
    }

    return extensions;
};

export const commonExtensions = (readonly: boolean): Extension[] => {
    const extensions = [
        lintGutter(),
        noWritingAssistants,
        editorTheme,
        EditorState.tabSize.of(CODE_EDITOR_INDENT),
        indentUnit.of(' '.repeat(CODE_EDITOR_INDENT)),
    ];

    if (readonly) {
        extensions.push(EditorState.readOnly.of(true), EditorView.editable.of(false));
    }

    return extensions;
};

export const fallbackExtensions = (readonly: boolean): Extension[] =>
    commonExtensions(readonly);

const chipClasses: Partial<Record<EnvVariableCheckStatus, string>> = {
    [EnvVariableCheckStatus.Resolved]: 'cm-env-chip cm-env-chip-resolved',
    [EnvVariableCheckStatus.Empty]: 'cm-env-chip cm-env-chip-empty',
    [EnvVariableCheckStatus.Missing]: 'cm-env-chip cm-env-chip-missing',
};

/**
 * Draws each environment placeholder as an inline chip colored by its status.
 */
const envVariableChips = (
    getStatus: (value: string) => EnvVariableCheckStatus,
): Extension => {
    const build = (view: EditorView): DecorationSet => {
        const builder = new RangeSetBuilder<Decoration>();
        const text = view.state.doc.toString();

        const chips = [
            ...[...text.matchAll(PLACEHOLDER_PATTERN)].map(match => ({
                from: match.index,
                to: match.index + match[0].length,
                className: chipClasses[getStatus(match[1])],
            })),
            // A dynamic value always resolves, so it has one look and no status.
            ...[...text.matchAll(DYNAMIC_PLACEHOLDER_PATTERN)].map(match => ({
                from: match.index,
                to: match.index + match[0].length,
                className: 'cm-env-chip cm-env-chip-dynamic',
            })),
        ].sort((first, second) => first.from - second.from);

        for (const chip of chips) {
            if (chip.className === undefined) {
                continue;
            }

            builder.add(chip.from, chip.to, Decoration.mark({ class: chip.className }));
        }

        return builder.finish();
    };

    return ViewPlugin.fromClass(
        class {
            decorations: DecorationSet;

            constructor(view: EditorView) {
                this.decorations = build(view);
            }

            update(update: ViewUpdate) {
                if (update.docChanged || update.viewportChanged) {
                    this.decorations = build(update.view);
                }
            }
        },
        { decorations: plugin => plugin.decorations },
    );
};

/**
 * Suggests variables once {{ is typed and dynamic variables once {$ is typed.
 */
const variableCompletions = (getVariableNames: () => string[]): Extension =>
    autocompletion({
        override: [
            (context: CompletionContext) => {
                const line = context.state.doc.lineAt(context.pos);
                const before = line.text.slice(0, context.pos - line.from);
                const placeholder = findOpenPlaceholder(before, before.length);

                if (!placeholder) {
                    return null;
                }

                const after = line.text.slice(context.pos - line.from);

                return {
                    from: line.from + placeholder.from,
                    // The closing braces typed or auto-closed after the caret are replaced.
                    to: context.pos + getClosingLength(placeholder.kind, after),
                    filter: false,
                    options: getVariableSuggestions(
                        placeholder.kind,
                        placeholder.query,
                        getVariableNames(),
                    ).map(suggestion => ({
                        label: suggestion.token,
                        displayLabel: suggestion.name,
                        detail: suggestion.sample ?? '',
                        section: suggestion.group,
                        type: suggestion.group === 'Dynamic' ? 'function' : 'variable',
                    })),
                };
            },
        ],
    });

export const envVariablesCheck = (
    getStatus: (value: string) => EnvVariableCheckStatus,
    getVariableNames: () => string[] = () => [],
): Extension => {
    return [
        envVariableChips(getStatus),
        envVariablesLinter(getStatus),
        variableCompletions(getVariableNames),
    ];
};

const envVariablesLinter = (
    getStatus: (value: string) => EnvVariableCheckStatus,
): Extension => {
    return linter(view => {
        const diagnostics: Diagnostic[] = [];
        const text = view.state.doc.toString();
        const source = 'env-variables-checks';

        for (const match of text.matchAll(PLACEHOLDER_PATTERN)) {
            const from = match.index;
            const value = match[0];

            if (from === undefined) {
                continue;
            }

            const status = getStatus(match[1]);

            if (status === EnvVariableCheckStatus.None) {
                continue;
            }

            if (status === EnvVariableCheckStatus.Resolved) {
                diagnostics.push({
                    from,
                    to: from + value.length,
                    severity: 'info',
                    message: 'Environment variable resolved.',
                    source,
                });

                continue;
            }

            if (status === EnvVariableCheckStatus.Empty) {
                diagnostics.push({
                    from,
                    to: from + value.length,
                    severity: 'warning',
                    message: 'The referenced variable is found, but its value is empty.',
                    source,
                });

                continue;
            }

            diagnostics.push({
                from,
                to: from + value.length,
                severity: 'error',
                message:
                    'The referenced variable cannot be found in the selected collection.',
                source,
            });
        }

        return diagnostics;
    });
};
