import type { HttpHeaders } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';

/**
 * Type shape for request body types with their associated metadata.
 */
export interface TypeShape {
    id: RequestBodyTypeEnum;
    label: string;
    autoFillable: boolean;
    mimeType: string | null;
}

/**
 * Available request body types with their MIME type mappings.
 *
 * Each entry has an id, a label for the UI, and an autoFillable flag for schema-based auto-fill.
 * The mimeType is the Content-Type header value, or null if no header should be set.
 */
export const types: TypeShape[] = [
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
];

/**
 * Gets the Content-Type a request needs for its body.
 * A binary body uses the type of its file and other types use their own MIME type.
 */
export function getMimeTypeForRequest(request: {
    payloadType: RequestBodyTypeEnum;
    body: Partial<
        Record<string, Partial<Record<RequestBodyTypeEnum, FormData | string | null>>>
    >;
    method: string;
}): string | null {
    if (request.payloadType === RequestBodyTypeEnum.BINARY) {
        const body = request.body[request.method]?.[RequestBodyTypeEnum.BINARY];
        const file = body instanceof FormData ? body.get('file') : null;

        return file instanceof File && file.type ? file.type : 'application/octet-stream';
    }

    return getMimeTypeForPayloadType(request.payloadType);
}

/**
 * Gets the MIME type for a given payload type.
 *
 * @param payloadType - The request body type to look up
 * @returns The MIME type string, or null if the type has no associated MIME type
 *
 * @example
 * getMimeTypeForPayloadType(RequestBodyTypeEnum.JSON) // 'application/json'
 * getMimeTypeForPayloadType(RequestBodyTypeEnum.EMPTY) // null
 */
export function getMimeTypeForPayloadType(
    payloadType: RequestBodyTypeEnum,
): string | null {
    return types.find(type => type.id === payloadType)?.mimeType ?? null;
}

/**
 * Generates a new headers array with the appropriate Content-Type header.
 *
 * A payload type with a MIME type adds the Content-Type header when the user did not set one.
 * A Content-Type the user set is kept, except for Form Data because its boundary is generated.
 * A payload type without a MIME type, such as EMPTY, removes any Content-Type header.
 * The input headers array is not mutated.
 *
 * @param payloadType - The request body type to generate Content-Type for
 * @param existingHeaders - The current headers array
 * @returns A new headers array with Content-Type properly set
 *
 * @example
 * const headers = [{ key: 'Accept', value: 'application/json' }];
 * const newHeaders = generateContentTypeHeader(RequestBodyTypeEnum.JSON, headers);
 * // Returns: [
 * //   { key: 'Accept', value: 'application/json' },
 * //   { key: 'content-type', value: 'application/json' }
 * // ]
 */
export function generateContentTypeHeader(
    payloadType: RequestBodyTypeEnum,
    existingHeaders: HttpHeaders[],
    mimeTypeOverride: string | null = null,
): HttpHeaders[] {
    const mimeType = mimeTypeOverride ?? getMimeTypeForPayloadType(payloadType);

    // Find existing Content-Type header (case-insensitive)
    const contentTypeIndex = existingHeaders.findIndex(
        (header: HttpHeaders) => header.key.toLowerCase() === 'content-type',
    );

    if (mimeType === null) {
        if (contentTypeIndex !== -1) {
            return [
                ...existingHeaders.slice(0, contentTypeIndex),
                ...existingHeaders.slice(contentTypeIndex + 1),
            ];
        }

        return existingHeaders;
    }

    if (contentTypeIndex !== -1) {
        // The relay detects multipart bodies from this header, so Form Data always overrides it.
        if (payloadType !== RequestBodyTypeEnum.FORM_DATA) {
            return existingHeaders;
        }

        return [
            ...existingHeaders.slice(0, contentTypeIndex),
            {
                key: 'content-type',
                value: mimeType,
            },
            ...existingHeaders.slice(contentTypeIndex + 1),
        ];
    }

    return [
        ...existingHeaders,
        {
            key: 'content-type',
            value: mimeType,
        },
    ];
}
