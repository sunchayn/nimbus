import { jsonLinter } from '@/components/domain/CodeEditor/jsonLinter';
import {
    EnvVariableCheckStatus,
    PLACEHOLDER_PATTERN,
} from '@/interfaces/common/env-vars';
import { json } from '@codemirror/lang-json';
import { type Diagnostic, linter, lintGutter } from '@codemirror/lint';
import { EditorState, type Extension } from '@codemirror/state';
import { jsonSchema } from 'codemirror-json-schema';
import type { JSONSchema7 } from 'json-schema';

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
    const extensions = [lintGutter()];

    if (readonly) {
        extensions.push(EditorState.readOnly.of(true));
    }

    return extensions;
};

export const fallbackExtensions = (readonly: boolean): Extension[] =>
    commonExtensions(readonly);

export const envVariablesCheck = (
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
