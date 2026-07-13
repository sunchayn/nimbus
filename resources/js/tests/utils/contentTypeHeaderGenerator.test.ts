import { RequestBodyTypeEnum } from '@/interfaces/http';
import {
    generateContentTypeHeader,
    getMimeTypeForPayloadType,
    types,
} from '@/utils/request/contentTypeHeaderGenerator';
import { describe, expect, it } from 'vitest';

describe('contentTypeHeaderGenerator', () => {
    describe('types constant', () => {
        it('exports all payload types with correct structure', () => {
            expect(types).toHaveLength(4);
            expect(types).toEqual([
                {
                    id: RequestBodyTypeEnum.EMPTY,
                    label: 'Empty',
                    autoFillable: false,
                    mimeType: null,
                },
                {
                    id: RequestBodyTypeEnum.JSON,
                    label: 'JSON',
                    autoFillable: true,
                    mimeType: 'application/json',
                },
                {
                    id: RequestBodyTypeEnum.PLAIN_TEXT,
                    label: 'Plain Text',
                    autoFillable: false,
                    mimeType: 'text/plain',
                },
                {
                    id: RequestBodyTypeEnum.FORM_DATA,
                    label: 'Form Data',
                    autoFillable: true,
                    mimeType: 'multipart/form-data',
                },
            ]);
        });
    });

    describe('getMimeTypeForPayloadType', () => {
        it('returns correct MIME type for JSON', () => {
            // Act

            const result = getMimeTypeForPayloadType(RequestBodyTypeEnum.JSON);

            // Assert

            expect(result).toBe('application/json');
        });

        it('returns correct MIME type for Plain Text', () => {
            // Act

            const result = getMimeTypeForPayloadType(RequestBodyTypeEnum.PLAIN_TEXT);

            // Assert

            expect(result).toBe('text/plain');
        });

        it('returns correct MIME type for Form Data', () => {
            // Act

            const result = getMimeTypeForPayloadType(RequestBodyTypeEnum.FORM_DATA);

            // Assert

            expect(result).toBe('multipart/form-data');
        });

        it('returns null for Empty payload type', () => {
            // Act

            const result = getMimeTypeForPayloadType(RequestBodyTypeEnum.EMPTY);

            // Assert

            expect(result).toBeNull();
        });
    });

    describe('generateContentTypeHeader', () => {
        it('adds Content-Type header when none exists', () => {
            // Arrange

            const headers = [{ key: 'Accept', value: 'application/json' }];

            // Act

            const result = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            // Assert

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'application/json' },
            ]);
        });

        it('updates existing Content-Type header', () => {
            // Arrange

            const headers = [
                {
                    key: 'Accept',
                    value: 'application/json',
                },
                {
                    key: 'content-type',
                    value: 'text/plain',
                },
            ];

            // Act

            const result = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            // Assert

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'application/json' },
            ]);
        });

        it('removes Content-Type header when payload type is EMPTY', () => {
            // Arrange

            const headers = [
                {
                    key: 'Accept',
                    value: 'application/json',
                },
                {
                    key: 'content-type',
                    value: 'application/json',
                },
            ];

            // Act

            const result = generateContentTypeHeader(RequestBodyTypeEnum.EMPTY, headers);

            // Assert

            expect(result).toEqual([{ key: 'Accept', value: 'application/json' }]);
        });

        it('does not modify headers when EMPTY and no Content-Type exists', () => {
            // Arrange

            const headers = [{ key: 'Accept', value: 'application/json' }];

            // Act

            const result = generateContentTypeHeader(RequestBodyTypeEnum.EMPTY, headers);

            // Assert

            expect(result).toEqual([{ key: 'Accept', value: 'application/json' }]);
        });

        it('handles case-insensitive Content-Type header matching', () => {
            // Arrange

            const headers = [
                {
                    key: 'Accept',
                    value: 'application/json',
                },
                {
                    key: 'Content-Type',
                    value: 'text/plain',
                },
            ];

            // Act

            const result = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            // Assert

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'application/json' },
            ]);
        });

        it('does not mutate the original headers array', () => {
            // Arrange

            const headers = [{ key: 'Accept', value: 'application/json' }];
            const originalHeaders = [...headers];

            // Act

            generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            // Assert

            expect(headers).toEqual(originalHeaders);
        });

        it('handles Form Data payload type', () => {
            // Arrange

            const headers = [{ key: 'Accept', value: 'application/json' }];

            // Act

            const result = generateContentTypeHeader(
                RequestBodyTypeEnum.FORM_DATA,
                headers,
            );

            // Assert

            expect(result).toEqual([
                {
                    key: 'Accept',
                    value: 'application/json',
                },
                {
                    key: 'content-type',
                    value: 'multipart/form-data',
                },
            ]);
        });

        it('handles Plain Text payload type', () => {
            // Arrange

            const headers = [{ key: 'Accept', value: 'application/json' }];

            // Act

            const result = generateContentTypeHeader(
                RequestBodyTypeEnum.PLAIN_TEXT,
                headers,
            );

            // Assert

            expect(result).toEqual([
                {
                    key: 'Accept',
                    value: 'application/json',
                },
                {
                    key: 'content-type',
                    value: 'text/plain',
                },
            ]);
        });
    });
});
