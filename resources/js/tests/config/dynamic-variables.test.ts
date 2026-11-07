import {
    resolveDynamicPlaceholders,
    resolveDynamicVariable,
} from '@/config/dynamic-variables';
import { describe, expect, it } from 'vitest';

describe('dynamic variables', () => {
    describe('resolveDynamicVariable', () => {
        it('generates a value for a known name and null for an unknown one', () => {
            expect(resolveDynamicVariable('uuid')).toMatch(/^[0-9a-f-]{36}$/);
            expect(resolveDynamicVariable('missing')).toBeNull();
        });

        it('keeps the value of a name while the cache lives', () => {
            const cache = new Map<string, string>();

            expect(resolveDynamicVariable('uuid', cache)).toBe(
                resolveDynamicVariable('uuid', cache),
            );
        });
    });

    describe('resolveDynamicPlaceholders', () => {
        it('replaces the single brace syntax and keeps unknown names', () => {
            const result = resolveDynamicPlaceholders('a-{$timestamp}-{$nope}');

            expect(result).toMatch(/^a-\d+-\{\$nope\}$/);
        });

        it('ignores the double brace syntax', () => {
            expect(resolveDynamicPlaceholders('{{$uuid}}')).toBe('{{$uuid}}');
        });
    });
});
