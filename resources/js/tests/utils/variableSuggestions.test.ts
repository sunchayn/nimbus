import {
    applySuggestion,
    findOpenPlaceholder,
    getClosingLength,
    getVariableSuggestions,
} from '@/utils/variableSuggestions';
import { describe, expect, it } from 'vitest';

describe('variableSuggestions', () => {
    describe('findOpenPlaceholder', () => {
        it('finds an environment variable placeholder open at the caret', () => {
            expect(findOpenPlaceholder('a/{{na', 6)).toEqual({
                kind: 'variable',
                from: 2,
                query: 'na',
            });
        });

        it('finds a dynamic variable placeholder open at the caret', () => {
            expect(findOpenPlaceholder('{"id": {$uu', 11)).toEqual({
                kind: 'dynamic',
                from: 7,
                query: 'uu',
            });
            expect(findOpenPlaceholder('{$', 2)).toEqual({
                kind: 'dynamic',
                from: 0,
                query: '',
            });
        });

        it('treats a dollar sign after double braces as an environment variable query', () => {
            expect(findOpenPlaceholder('{{$u', 4)?.kind).toBe('variable');
        });

        it('returns null when there is no open placeholder', () => {
            expect(findOpenPlaceholder('a/{{name}}/b', 12)).toBeNull();
            expect(findOpenPlaceholder('{$uuid} ', 8)).toBeNull();
            expect(findOpenPlaceholder('no braces', 4)).toBeNull();
        });
    });

    describe('getVariableSuggestions', () => {
        it('suggests only environment variables for the variable kind', () => {
            const result = getVariableSuggestions('variable', 'tok', ['token', 'other']);

            expect(result).toEqual([
                { group: 'Variables', name: 'token', token: '{{token}}' },
            ]);
        });

        it('suggests only dynamic variables for the dynamic kind', () => {
            const all = getVariableSuggestions('dynamic', '', ['token']);

            expect(all.map(item => item.token)).toEqual([
                '{$uuid}',
                '{$timestamp}',
                '{$isoDate}',
                '{$randomInt}',
                '{$email}',
            ]);
            expect(all[0].sample).toBeTruthy();
            expect(getVariableSuggestions('dynamic', 'uu', []).map(i => i.name)).toEqual([
                'uuid',
            ]);
        });
    });

    describe('applySuggestion', () => {
        it('completes an environment variable and swallows closing braces after the caret', () => {
            const placeholder = { kind: 'variable' as const, from: 2, query: 'to' };
            const suggestion = {
                group: 'Variables' as const,
                name: 'token',
                token: '{{token}}',
            };

            expect(applySuggestion('a/{{to}}/b', 6, placeholder, suggestion)).toEqual({
                text: 'a/{{token}}/b',
                caret: 11,
            });
        });

        it('completes a dynamic variable and swallows one closing brace after the caret', () => {
            const placeholder = { kind: 'dynamic' as const, from: 0, query: 'uu' };
            const suggestion = {
                group: 'Dynamic' as const,
                name: 'uuid',
                token: '{$uuid}',
            };

            expect(applySuggestion('{$uu}', 4, placeholder, suggestion)).toEqual({
                text: '{$uuid}',
                caret: 7,
            });
        });
    });

    describe('getClosingLength', () => {
        it('counts the closing braces up to the size of the placeholder end', () => {
            expect(getClosingLength('variable', '}}}')).toBe(2);
            expect(getClosingLength('dynamic', '}}')).toBe(1);
            expect(getClosingLength('dynamic', 'x}')).toBe(0);
        });
    });
});
