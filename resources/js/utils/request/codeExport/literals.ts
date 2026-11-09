import type { Field, Header } from './types';

/**
 * Indents every line after the first, so a block fits inside a longer line.
 */
export const indentLines = (text: string, spaces: number): string =>
    text
        .split('\n')
        .map((line, index) => (index === 0 ? line : ' '.repeat(spaces) + line))
        .join('\n');

/**
 * Turns a text into a lowercase word list joined by underscores.
 */
export const toSnakeCase = (text: string): string =>
    text
        .replace(/[^a-z0-9]+/gi, '_')
        .replace(/^_|_$/g, '')
        .toLowerCase();

export const js = (value: unknown): string => JSON.stringify(value, null, 4);

export const jsObject = (entries: Header[]): string =>
    js(Object.fromEntries(entries.map(entry => [entry.key, entry.value])));

export const phpString = (value: string): string =>
    `'${value.replace(/\\/g, '\\\\').replace(/'/g, "\\'")}'`;

export const php = (value: unknown, depth = 0): string => {
    const pad = '    '.repeat(depth + 1);
    const closing = '    '.repeat(depth);

    if (value === null) {
        return 'null';
    }

    if (typeof value === 'string') {
        return phpString(value);
    }

    if (typeof value === 'number' || typeof value === 'boolean') {
        return String(value);
    }

    if (Array.isArray(value)) {
        return value.length === 0
            ? '[]'
            : `[\n${value.map(item => `${pad}${php(item, depth + 1)},`).join('\n')}\n${closing}]`;
    }

    const entries = Object.entries(value as Record<string, unknown>);

    return entries.length === 0
        ? '[]'
        : `[\n${entries.map(([key, item]) => `${pad}${phpString(key)} => ${php(item, depth + 1)},`).join('\n')}\n${closing}]`;
};

export const phpHeaders = (headers: Header[]): string =>
    php(Object.fromEntries(headers.map(header => [header.key, header.value])));

export const fieldsObject = (fields: Field[]): Record<string, string> =>
    Object.fromEntries(fields.map(field => [field.key, field.value]));
