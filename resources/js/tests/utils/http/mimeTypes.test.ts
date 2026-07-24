import { getMimeTypeInfo } from '@/utils/http/mimeTypes';
import { describe, expect, it } from 'vitest';

describe('mimeTypes utility', () => {
    it('correctly maps standard mime types', () => {
        expect(getMimeTypeInfo('application/json')).toEqual({
            extension: 'json',
            mimeType: 'application/json',
        });

        expect(getMimeTypeInfo('text/html')).toEqual({
            extension: 'html',
            mimeType: 'text/html',
        });

        expect(getMimeTypeInfo('application/xml')).toEqual({
            extension: 'xml',
            mimeType: 'application/xml',
        });
    });

    it('strips parameters like charset', () => {
        expect(getMimeTypeInfo('application/json; charset=utf-8')).toEqual({
            extension: 'json',
            mimeType: 'application/json',
        });

        expect(getMimeTypeInfo('text/html; charset=ISO-8859-1')).toEqual({
            extension: 'html',
            mimeType: 'text/html',
        });
    });

    it('handles fuzzy matching and fallbacks', () => {
        expect(getMimeTypeInfo('application/problem+json')).toEqual({
            extension: 'json',
            mimeType: 'application/json',
        });

        expect(getMimeTypeInfo('image/png')).toEqual({
            extension: 'png',
            mimeType: 'image/png',
        });

        expect(getMimeTypeInfo('unknown/type')).toEqual({
            extension: 'txt',
            mimeType: 'text/plain',
        });

        expect(getMimeTypeInfo('')).toEqual({
            extension: 'txt',
            mimeType: 'text/plain',
        });
    });
});
