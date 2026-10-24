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
];

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
 * A payload type with a MIME type adds or updates the Content-Type header.
 * A payload type without one, such as EMPTY, removes any Content-Type header.
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
): HttpHeaders[] {
    const mimeType = getMimeTypeForPayloadType(payloadType);

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
