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
 * This constant defines the supported payload types and their characteristics:
 * - id: The enum value identifying the type
 * - label: Human-readable name for UI display
 * - autoFillable: Whether the type supports schema-based auto-fill
 * - mimeType: The Content-Type header value, or null if no header should be set
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
 * This function creates a new array of headers based on the payload type:
 * - If the payload type has a MIME type, adds/updates the Content-Type header
 * - If the payload type has no MIME type (e.g., EMPTY), removes any Content-Type header
 * - Does NOT mutate the input headers array
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

    // If no MIME type for this payload type, remove Content-Type if it exists
    if (mimeType === null) {
        if (contentTypeIndex !== -1) {
            // Return new array without the Content-Type header
            return [
                ...existingHeaders.slice(0, contentTypeIndex),
                ...existingHeaders.slice(contentTypeIndex + 1),
            ];
        }

        // No Content-Type to remove, return as-is
        return existingHeaders;
    }

    // If Content-Type exists, update it
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

    // Add new Content-Type header
    return [
        ...existingHeaders,
        {
            key: 'content-type',
            value: mimeType,
        },
    ];
}
