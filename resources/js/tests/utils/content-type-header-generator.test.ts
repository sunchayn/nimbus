import { RequestBodyTypeEnum } from '@/interfaces/http';
import {
    generateContentTypeHeader,
    getMimeTypeForPayloadType,
    types,
} from '@/utils/request/content-type-header-generator';
import { describe, expect, it } from 'vitest';

describe('content-type-header-generator', () => {
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
            expect(getMimeTypeForPayloadType(RequestBodyTypeEnum.JSON)).toBe(
                'application/json',
            );
        });

        it('returns correct MIME type for Plain Text', () => {
            expect(getMimeTypeForPayloadType(RequestBodyTypeEnum.PLAIN_TEXT)).toBe(
                'text/plain',
            );
        });

        it('returns correct MIME type for Form Data', () => {
            expect(getMimeTypeForPayloadType(RequestBodyTypeEnum.FORM_DATA)).toBe(
                'multipart/form-data',
            );
        });

        it('returns null for Empty payload type', () => {
            expect(getMimeTypeForPayloadType(RequestBodyTypeEnum.EMPTY)).toBeNull();
        });
    });

    describe('generateContentTypeHeader', () => {
        it('adds Content-Type header when none exists', () => {
            const headers = [{ key: 'Accept', value: 'application/json' }];

            const result = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'application/json' },
            ]);
        });

        it('updates existing Content-Type header', () => {
            const headers = [
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'text/plain' },
            ];

            const result = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'application/json' },
            ]);
        });

        it('removes Content-Type header when payload type is EMPTY', () => {
            const headers = [
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'application/json' },
            ];

            const result = generateContentTypeHeader(RequestBodyTypeEnum.EMPTY, headers);

            expect(result).toEqual([{ key: 'Accept', value: 'application/json' }]);
        });

        it('does not modify headers when EMPTY and no Content-Type exists', () => {
            const headers = [{ key: 'Accept', value: 'application/json' }];

            const result = generateContentTypeHeader(RequestBodyTypeEnum.EMPTY, headers);

            expect(result).toEqual([{ key: 'Accept', value: 'application/json' }]);
        });

        it('handles case-insensitive Content-Type header matching', () => {
            const headers = [
                { key: 'Accept', value: 'application/json' },
                { key: 'Content-Type', value: 'text/plain' },
            ];

            const result = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'application/json' },
            ]);
        });

        it('does not mutate the original headers array', () => {
            const headers = [{ key: 'Accept', value: 'application/json' }];
            const originalHeaders = [...headers];

            generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            expect(headers).toEqual(originalHeaders);
        });

        it('handles Form Data payload type', () => {
            const headers = [{ key: 'Accept', value: 'application/json' }];

            const result = generateContentTypeHeader(
                RequestBodyTypeEnum.FORM_DATA,
                headers,
            );

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'multipart/form-data' },
            ]);
        });

        it('handles Plain Text payload type', () => {
            const headers = [{ key: 'Accept', value: 'application/json' }];

            const result = generateContentTypeHeader(
                RequestBodyTypeEnum.PLAIN_TEXT,
                headers,
            );

            expect(result).toEqual([
                { key: 'Accept', value: 'application/json' },
                { key: 'content-type', value: 'text/plain' },
            ]);
        });
    });
});
