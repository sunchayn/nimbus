import { generateFromPattern } from '@/utils/payload/patternGenerator';
import { describe, expect, it } from 'vitest';

describe('patternGenerator', () => {
    it.each([
        ['a unicode alpha dash pattern', String.raw`^[\p{L}\p{M}\p{N}_-]+$`],
        ['a fixed number of digits', String.raw`^\d{4}$`],
        ['a lowercase slug', '^[a-z0-9-]+$'],
        ['alternatives', '^(red|green|blue)-[0-9]{2,3}$'],
        ['an optional group', String.raw`^SKU(-[A-Z]{3})?\d+$`],
    ])('generates a value that matches %s', (_label, pattern) => {
        // Act

        const values = Array.from({ length: 50 }, () => generateFromPattern(pattern));

        // Assert

        for (const value of values) {
            expect(value).not.toBeNull();
            expect(new RegExp(pattern, 'u').test(value as string)).toBe(true);
        }
    });

    it('respects the length limits', () => {
        // Act

        const values = Array.from({ length: 50 }, () =>
            generateFromPattern('^[a-z]+$', 6, 3),
        );

        // Assert

        for (const value of values) {
            expect((value as string).length).toBeGreaterThanOrEqual(3);
            expect((value as string).length).toBeLessThanOrEqual(6);
        }
    });

    it('returns null for a pattern it cannot read', () => {
        // Act & Assert

        expect(generateFromPattern('^(?=.*a)[a-z]+$')).toBeNull();
        expect(generateFromPattern('^[^a]+$')).toBeNull();
        expect(generateFromPattern('^(unclosed$')).toBeNull();
    });
});
