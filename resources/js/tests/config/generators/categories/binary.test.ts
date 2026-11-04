import { MIME_TYPES } from '@/config/binaries/mimeTypes';
import { getGeneratorById } from '@/config/generators';
import type { GeneratorContext } from '@/interfaces/ui';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const RUNS = 30;

const generateWith = (id: string, context?: GeneratorContext) => {
    const generator = getGeneratorById.get(id);

    if (!generator) {
        throw new Error(`The generator ${id} does not exist.`);
    }

    return String(generator.generate(context));
};

describe('binary generators', () => {
    describe('mime-type', () => {
        it('returns a common mime type', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = generateWith('mime-type');

                // Assert

                expect(MIME_TYPES).toContain(value);
            }
        });
    });

    describe('file-name', () => {
        it('ends with a common extension', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = generateWith('file-name');

                // Assert

                expect(value).toMatch(/^[a-z]+\.(png|jpg|pdf|txt|csv|json|zip)$/);
            }
        });
    });

    describe('base64-content', () => {
        it('returns decodable base64', () => {
            // Act

            const value = generateWith('base64-content');

            // Assert

            expect(() => atob(value)).not.toThrow();
        });

        it('returns decodable base64 that fits the length', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = generateWith('base64-content', { maxLength: 8 });

                // Assert

                expect(value.length).toBeLessThanOrEqual(8);
                expect(() => atob(value)).not.toThrow();
            }
        });
    });
});
