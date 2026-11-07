import { RequestBodyTypeEnum } from '@/interfaces/http';
import {
    generateContentTypeHeader,
    getMimeTypeForPayloadType,
    getMimeTypeForRequest,
    types,
} from '@/utils/request/contentTypeHeaderGenerator';
import { describe, expect, it } from 'vitest';

describe('contentTypeHeaderGenerator', () => {
    describe('types constant', () => {
        it('exports all payload types with correct structure', () => {
            expect(types).toHaveLength(8);
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
                {
                    id: RequestBodyTypeEnum.URLENCODED,
                    label: 'Form URL Encoded',
                    autoFillable: true,
                    mimeType: 'application/x-www-form-urlencoded',
                },
                {
                    id: RequestBodyTypeEnum.HTML,
                    label: 'HTML',
                    autoFillable: false,
                    mimeType: 'text/html',
                },
                {
                    id: RequestBodyTypeEnum.XML,
                    label: 'XML',
                    autoFillable: false,
                    mimeType: 'application/xml',
                },
                {
                    id: RequestBodyTypeEnum.BINARY,
                    label: 'Binary',
                    autoFillable: false,
                    mimeType: null,
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

        it('keeps a Content-Type header the user set', () => {
            // Arrange

            const headers = [
                {
                    key: 'Accept',
                    value: 'application/json',
                },
                {
                    key: 'content-type',
                    value: 'application/vnd.api+json',
                },
            ];

            // Act

            const result = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);

            // Assert

            expect(result).toEqual(headers);
        });

        it('replaces a user Content-Type for Form Data because the boundary is generated', () => {
            // Arrange

            const headers = [{ key: 'Content-Type', value: 'application/vnd.api+json' }];

            // Act

            const result = generateContentTypeHeader(
                RequestBodyTypeEnum.FORM_DATA,
                headers,
            );

            // Assert

            expect(result).toEqual([
                { key: 'content-type', value: 'multipart/form-data' },
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

            expect(result).toHaveLength(2);
            expect(result[1]).toEqual({
                key: 'Content-Type',
                value: 'text/plain',
            });
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

    describe('getMimeTypeForRequest', () => {
        const base = { method: 'POST', body: {} };

        it.each([
            [RequestBodyTypeEnum.HTML, 'text/html'],
            [RequestBodyTypeEnum.XML, 'application/xml'],
        ])('uses the own MIME type for a %s body', (payloadType, mimeType) => {
            expect(getMimeTypeForRequest({ ...base, payloadType })).toBe(mimeType);
        });

        it('uses the file type for a binary body', () => {
            const formData = new FormData();
            formData.append('file', new File(['x'], 'a.png', { type: 'image/png' }));

            expect(
                getMimeTypeForRequest({
                    method: 'POST',
                    payloadType: RequestBodyTypeEnum.BINARY,
                    body: { POST: { [RequestBodyTypeEnum.BINARY]: formData } },
                }),
            ).toBe('image/png');
            expect(
                getMimeTypeForRequest({
                    ...base,
                    payloadType: RequestBodyTypeEnum.BINARY,
                }),
            ).toBe('application/octet-stream');
        });

        it('sets the override header unless the user set one', () => {
            expect(
                generateContentTypeHeader(RequestBodyTypeEnum.BINARY, [], 'image/png'),
            ).toEqual([{ key: 'content-type', value: 'image/png' }]);

            const userHeaders = [{ key: 'Content-Type', value: 'text/csv' }];

            expect(
                generateContentTypeHeader(
                    RequestBodyTypeEnum.BINARY,
                    userHeaders,
                    'image/png',
                ),
            ).toEqual(userHeaders);
        });
    });
});
