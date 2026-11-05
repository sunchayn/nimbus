import { BINARY_FIXTURES } from '@/config/binaries/fixtures';
import { pickFixture } from '@/utils/binaries/pickFixture';
import { describe, expect, it } from 'vitest';

const RUNS = 30;

describe('pickFixture', () => {
    it('picks any fixture when nothing constrains it', () => {
        // Act

        const fixture = pickFixture();

        // Assert

        expect(BINARY_FIXTURES).toContain(fixture);
    });

    it('picks only fixtures with a listed mime type', () => {
        for (let run = 0; run < RUNS; run++) {
            // Act

            const fixture = pickFixture({ mimeTypes: ['image/png', 'application/pdf'] });

            // Assert

            expect(['image/png', 'application/pdf']).toContain(fixture?.mimeType);
        }
    });

    it('picks only fixtures within the encoded length', () => {
        for (let run = 0; run < RUNS; run++) {
            // Act

            const fixture = pickFixture({ minLength: 20, maxLength: 60 });

            // Assert

            expect(fixture?.base64.length).toBeGreaterThanOrEqual(20);
            expect(fixture?.base64.length).toBeLessThanOrEqual(60);
        }
    });

    it('combines the mime types and the length', () => {
        // Act

        const fixture = pickFixture({
            mimeTypes: ['image/png', 'image/gif'],
            maxLength: 60,
        });

        // Assert

        expect(fixture?.id).toBe('gif');
    });

    it('returns undefined when no fixture fits', () => {
        // Act

        const fixture = pickFixture({ maxLength: 3 });

        // Assert

        expect(fixture).toBeUndefined();
    });

    it('returns undefined when no fixture has a listed mime type', () => {
        // Act

        const fixture = pickFixture({ mimeTypes: ['video/mp4'] });

        // Assert

        expect(fixture).toBeUndefined();
    });
});
