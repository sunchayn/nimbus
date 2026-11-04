import { BINARY_FIXTURES } from '@/config/binaries/fixtures';
import { MIME_TYPES } from '@/config/binaries/mimeTypes';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const startsWith = (id: string): string => {
    const bytes = atob(BINARY_FIXTURES.find(fixture => fixture.id === id)?.base64 ?? '');

    return bytes;
};

describe('binary fixtures', () => {
    it('has unique ids', () => {
        // Arrange

        const ids = BINARY_FIXTURES.map(fixture => fixture.id);

        // Assert

        expect(new Set(ids).size).toBe(ids.length);
    });

    it.each(BINARY_FIXTURES.map(fixture => [fixture.id, fixture.base64]))(
        'decodes the %s fixture from base64',
        (_id, base64) => {
            // Act

            const decode = () => atob(base64);

            // Assert

            expect(decode).not.toThrow();
            expect(decode().length).toBeGreaterThan(0);
        },
    );

    it.each(BINARY_FIXTURES.map(fixture => [fixture.id, fixture.mimeType]))(
        'uses a listed mime type for the %s fixture',
        (_id, mimeType) => {
            // Assert

            expect(MIME_TYPES).toContain(mimeType);
        },
    );

    describe('Magic bytes', () => {
        it('starts a png with the png signature', () => {
            expect(startsWith('png').startsWith('\x89PNG\r\n\x1a\n')).toBe(true);
        });

        it('starts a gif with the gif signature', () => {
            expect(startsWith('gif').startsWith('GIF8')).toBe(true);
        });

        it('starts a jpeg with the jpeg signature', () => {
            expect(startsWith('jpeg').startsWith('\xff\xd8\xff')).toBe(true);
        });

        it('starts a webp with the riff header', () => {
            // Arrange

            const bytes = startsWith('webp');

            // Assert

            expect(bytes.startsWith('RIFF')).toBe(true);
            expect(bytes.slice(8, 12)).toBe('WEBP');
        });

        it('starts a pdf with the pdf header and ends it with the eof marker', () => {
            // Arrange

            const bytes = startsWith('pdf');

            // Assert

            expect(bytes.startsWith('%PDF-')).toBe(true);
            expect(bytes.trimEnd().endsWith('%%EOF')).toBe(true);
        });

        it('starts a zip with the end of central directory signature', () => {
            expect(startsWith('zip').startsWith('PK')).toBe(true);
        });

        it('keeps the text based fixtures readable', () => {
            // Assert

            expect(startsWith('txt')).toContain('Nimbus');
            expect(JSON.parse(startsWith('json'))).toEqual({
                message: 'Hello from Nimbus',
            });
            expect(startsWith('csv').split('\n')[0]).toBe('id,name');
            expect(startsWith('svg').startsWith('<svg')).toBe(true);
        });
    });
});
