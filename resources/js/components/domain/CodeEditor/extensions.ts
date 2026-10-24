import { jsonLinter } from '@/components/domain/CodeEditor/jsonLinter';
import {
    EnvVariableCheckStatus,
    PLACEHOLDER_PATTERN,
} from '@/interfaces/common/env-vars';
import { json } from '@codemirror/lang-json';
import { type Diagnostic, linter, lintGutter } from '@codemirror/lint';
import { EditorState, type Extension, RangeSetBuilder } from '@codemirror/state';
import {
    Decoration,
    type DecorationSet,
    EditorView,
    ViewPlugin,
    type ViewUpdate,
} from '@codemirror/view';
import { jsonSchema } from 'codemirror-json-schema';
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

    extensions.push(json());

    if (schema !== undefined) {
        extensions.push(jsonSchema(schema));
    }

    if (!readonly) {
        extensions.push(jsonLinter);
    }

    return extensions;
};

export const commonExtensions = (readonly: boolean): Extension[] => {
    const extensions = [lintGutter(), noWritingAssistants];

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

        for (const match of text.matchAll(PLACEHOLDER_PATTERN)) {
            const className = chipClasses[getStatus(match[1])];

            if (match.index === undefined || className === undefined) {
                continue;
            }

            builder.add(
                match.index,
                match.index + match[0].length,
                Decoration.mark({ class: className }),
            );
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

export const envVariablesCheck = (
    getStatus: (value: string) => EnvVariableCheckStatus,
): Extension => {
    return [envVariableChips(getStatus), envVariablesLinter(getStatus)];
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
