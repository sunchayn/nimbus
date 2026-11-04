import { generateValue } from '@/utils/value-generator/generateValue';
import { describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const generatorFixtures = vi.hoisted(() => ({
    getGeneratorById: new Map([['uuid', { id: 'uuid', generate: () => 'uuid-value' }]]),
}));

vi.mock('@/config/generators', () => generatorFixtures);

describe('generateValue', () => {
    it('generates a value with the matching generator', () => {
        // Act

        const value = generateValue('uuid');

        // Assert

        expect(value).toBe('uuid-value');
    });

    it('returns an empty string for an unknown generator', () => {
        // Act

        const value = generateValue('missing');

        // Assert

        expect(value).toBe('');
    });
});
