import { maskDynamicVariables } from '@/utils/dynamicVariableMask';
import { describe, expect, it } from 'vitest';

describe('maskDynamicVariables', () => {
    it('turns an unquoted variable into a string value of the same length', () => {
        const source = '{"id": {$uuid}}';
        const { text, valueRanges } = maskDynamicVariables(source);

        expect(text).toHaveLength(source.length);
        expect(() => JSON.parse(text)).not.toThrow();
        expect(valueRanges).toEqual([{ from: 5, to: 14 }]);
    });

    it('turns a variable inside a string into filler characters', () => {
        const source = '{"id": "a-{$uuid}"}';
        const { text, valueRanges } = maskDynamicVariables(source);

        expect(text).toHaveLength(source.length);
        expect(JSON.parse(text).id).toBe('a-xxxxxxx');
        expect(valueRanges).toEqual([]);
    });

    it('does not read an escaped quote as the end of a string', () => {
        const source = '{"id": "\\"{$uuid}"}';

        expect(() => JSON.parse(maskDynamicVariables(source).text)).not.toThrow();
    });

    it('leaves text without a dynamic variable unchanged', () => {
        const source = '{"id": "{{token}}"}';

        expect(maskDynamicVariables(source)).toEqual({ text: source, valueRanges: [] });
    });
});
