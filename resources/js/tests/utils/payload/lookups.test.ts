import {
    collectLookupFields,
    collectLookups,
    setJsonPayloadField,
} from '@/utils/payload/lookups';
import { convertPayloadToJson } from '@/utils/payload/payloadConverter';
import type { JSONSchema7 } from 'json-schema';
import { describe, expect, it } from 'vitest';

const shape = {
    type: 'object',
    properties: {
        author_id: { type: 'integer', lookup: { table: 'users', column: 'id' } },
        name: { type: 'string' },
        items: {
            type: 'array',
            items: {
                type: 'object',
                properties: {
                    product_id: {
                        type: 'integer',
                        lookup: { table: 'products', column: 'id' },
                    },
                    owner_id: {
                        type: 'integer',
                        lookup: { table: 'users', column: 'id' },
                    },
                },
            },
        },
    },
} as JSONSchema7;

describe('lookups', () => {
    it('collects every distinct pair at any depth', () => {
        expect(collectLookups(shape)).toEqual([
            { table: 'users', column: 'id' },
            { table: 'products', column: 'id' },
        ]);
    });

    it('collects only top level fields for the picker', () => {
        expect(collectLookupFields(shape)).toEqual([
            { field: 'author_id', table: 'users', column: 'id' },
        ]);
        expect(collectLookupFields(undefined)).toEqual([]);
    });

    it('sets a field of a JSON payload', () => {
        expect(
            JSON.parse(setJsonPayloadField('{"name":"a"}', 'author_id', 3) ?? ''),
        ).toEqual({
            name: 'a',
            author_id: 3,
        });
        expect(JSON.parse(setJsonPayloadField(null, 'author_id', 3) ?? '')).toEqual({
            author_id: 3,
        });
    });

    it('indents the payload the way the autofill does', () => {
        const payload = { name: 'a', author_id: 1 };

        const fromPicker = setJsonPayloadField(
            convertPayloadToJson({ name: 'a' }),
            'author_id',
            1,
        );

        expect(fromPicker).toBe(convertPayloadToJson(payload));
    });

    it('leaves a payload that is not a JSON object alone', () => {
        expect(setJsonPayloadField('not json', 'a', 1)).toBe('not json');
        expect(setJsonPayloadField('[1]', 'a', 1)).toBe('[1]');
    });
});
