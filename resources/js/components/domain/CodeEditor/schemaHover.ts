import type { FoundCursorData, HoverOptions } from 'codemirror-json-schema';
import type { JSONSchema7 } from 'json-schema';

const KINDS = ['oneOf', 'anyOf', 'allOf'] as const;

const typeNames = (schema: JSONSchema7): string[] => {
    if (schema.type !== undefined) {
        return [schema.type].flat();
    }

    for (const kind of KINDS) {
        const members = schema[kind];

        if (members) {
            return [
                ...new Set(
                    members.flatMap(member =>
                        typeof member === 'object' ? typeNames(member) : [],
                    ),
                ),
            ];
        }
    }

    return [];
};

/**
 * Lists what a schema says about a value, one fact for each row.
 */
const facts = (schema: JSONSchema7): string[] => {
    const rows: string[] = [];
    const types = typeNames(schema);

    if (types.length) {
        rows.push(types.join(' or '));
    }

    if (schema.$ref) {
        rows.push(`Reference ${schema.$ref}`);
    }

    if (schema.enum) {
        rows.push(`enum ${schema.enum.map(String).join(' or ')}`);
    }

    if (schema.format) {
        rows.push(`format ${schema.format}`);
    }

    if (schema.pattern) {
        rows.push(`pattern ${schema.pattern}`);
    }

    return rows;
};

/**
 * Turns text with `code` parts into nodes, without any markup from the schema.
 */
const richText = (text: string): Node[] =>
    text.split(/`([^`]+)`/).map((part, index) => {
        if (index % 2 === 0) {
            return document.createTextNode(part);
        }

        const code = document.createElement('code');

        code.textContent = part;

        return code;
    });

const row = (className: string, children: Node[]): HTMLElement => {
    const element = document.createElement('div');

    element.className = className;
    element.append(...children);

    return element;
};

/**
 * The hover of a JSON schema value, which is its description and then its facts.
 * The default one joins the facts into one run of text with stray backticks.
 */
export const schemaHoverOptions: HoverOptions = {
    getHoverTexts: (data: FoundCursorData) => ({
        message: (data.schema as JSONSchema7).description ?? '',
        typeInfo: facts(data.schema as JSONSchema7).join('\n'),
    }),
    formatHover: ({ message, typeInfo }) => {
        const root = row('cm6-json-schema-hover', []);

        if (message) {
            root.append(row('cm6-json-schema-hover--description', richText(message)));
        }

        for (const fact of typeInfo.split('\n').filter(Boolean)) {
            root.append(
                row('cm6-json-schema-hover--code', [document.createTextNode(fact)]),
            );
        }

        return root;
    },
};
