import { jsonLinter } from '@/components/domain/CodeEditor/jsonLinter';
import { json } from '@codemirror/lang-json';
import { lintGutter } from '@codemirror/lint';
import { type Extension, EditorState } from '@codemirror/state';
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
