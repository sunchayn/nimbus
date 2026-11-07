import { maskDynamicVariables } from '@/utils/dynamicVariableMask';
import { json, jsonLanguage } from '@codemirror/lang-json';
import { type Diagnostic, linter } from '@codemirror/lint';
import { EditorState, type Extension } from '@codemirror/state';
import { hoverTooltip } from '@codemirror/view';
import {
    handleRefresh,
    jsonCompletion,
    jsonSchemaHover,
    jsonSchemaLinter,
    parseJSONDocumentState,
    stateExtensions,
} from 'codemirror-json-schema';
import type { JSONSchema7 } from 'json-schema';

/**
 * Parses the document with each dynamic variable standing in as a value.
 */
const parseMaskedDocument = (state: EditorState) =>
    parseJSONDocumentState(
        EditorState.create({
            doc: maskDynamicVariables(state.doc.toString()).text,
            extensions: [json()],
        }),
    );

/**
 * Validates against the schema without errors that exist only because of a dynamic variable.
 * The type of a dynamic variable is unknown, so the errors on its value are dropped.
 */
const dynamicAwareSchemaLinter = () => {
    const validate = jsonSchemaLinter({ jsonParser: parseMaskedDocument });

    return (view: Parameters<typeof validate>[0]): Diagnostic[] => {
        const { valueRanges } = maskDynamicVariables(view.state.doc.toString());

        return validate(view).filter(
            diagnostic =>
                !valueRanges.some(
                    range => diagnostic.from < range.to && diagnostic.to > range.from,
                ),
        );
    };
};

/**
 * The schema editor extensions of codemirror-json-schema, made aware of dynamic variables.
 * The syntax lint is not part of it because jsonLinter already covers it.
 */
export const dynamicAwareJsonSchema = (schema: JSONSchema7): Extension[] => [
    json(),
    linter(dynamicAwareSchemaLinter(), { needsRefresh: handleRefresh }),
    jsonLanguage.data.of({ autocomplete: jsonCompletion() }),
    hoverTooltip(jsonSchemaHover()),
    stateExtensions(schema),
];
