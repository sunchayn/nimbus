import { JSON_BODY_INDENT } from '@/utils/payload/payloadConverter';
import type { JSONSchema7 } from 'json-schema';

export interface Lookup {
    table: string;
    column: string;
}

export interface LookupField extends Lookup {
    field: string;
}

export const lookupKey = ({ table, column }: Lookup): string => `${table}.${column}`;

const readLookup = (value: unknown): Lookup | null => {
    const candidate = value as Partial<Lookup> | null;

    return typeof candidate?.table === 'string' && typeof candidate.column === 'string'
        ? { table: candidate.table, column: candidate.column }
        : null;
};

/**
 * Lists the top level properties that carry a lookup, which is what a record picker can fill.
 */
export const collectLookupFields = (shape: JSONSchema7 | undefined): LookupField[] =>
    Object.entries(shape?.properties ?? {}).flatMap(([field, property]) => {
        const lookup = readLookup((property as { lookup?: unknown }).lookup);

        return lookup ? [{ field, ...lookup }] : [];
    });

/**
 * Lists every distinct table and column pair of the schema, at any depth.
 */
export const collectLookups = (shape: unknown): Lookup[] => {
    const found = new Map<string, Lookup>();

    const visit = (node: unknown) => {
        if (Array.isArray(node)) {
            node.forEach(visit);

            return;
        }

        if (node === null || typeof node !== 'object') {
            return;
        }

        for (const [key, value] of Object.entries(node)) {
            const lookup = key === 'lookup' ? readLookup(value) : null;

            if (lookup) {
                found.set(lookupKey(lookup), lookup);
            } else {
                visit(value);
            }
        }
    };

    visit(shape);

    return [...found.values()];
};

/**
 * Sets one top level field of a JSON payload, and leaves a payload that is not a JSON object as it is.
 */
export const setJsonPayloadField = (
    payload: string | null,
    field: string,
    value: string | number,
): string | null => {
    try {
        const parsed: unknown = JSON.parse(payload || '{}');

        if (parsed === null || typeof parsed !== 'object' || Array.isArray(parsed)) {
            return payload;
        }

        return JSON.stringify({ ...parsed, [field]: value }, null, JSON_BODY_INDENT);
    } catch {
        return payload;
    }
};
