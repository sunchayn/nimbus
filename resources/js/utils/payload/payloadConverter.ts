import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { PayloadObject } from '@/interfaces/schema/payload';
import { convertPayloadToFormData } from '@/utils/http';

// The indentation of every generated JSON body, so edits to a body keep the same look.
export const JSON_BODY_INDENT = 2;

/**
 * Convert payload to JSON format with proper indentation.
 */
export const convertPayloadToJson = (payload: PayloadObject): string => {
    // A file has no text form, so it is left empty.
    return JSON.stringify(
        payload,
        (_key, value) => (value instanceof Blob ? '' : value),
        JSON_BODY_INDENT,
    );
};

/**
 * Convert payload to plain text format (key=value pairs).
 */
export const convertPayloadToPlainText = (payload: PayloadObject): string => {
    return Object.entries(payload)
        .map(([key, value]) => `${key}=${value instanceof Blob ? '' : value};`)
        .join('\n');
};

/**
 * Convert payload to the specified request body type.
 */
export const serializeSchemaPayload = (
    payload: PayloadObject,
    type: RequestBodyTypeEnum,
): string | FormData | null => {
    switch (type) {
        case RequestBodyTypeEnum.JSON:
            return convertPayloadToJson(payload);
        case RequestBodyTypeEnum.PLAIN_TEXT:
            return convertPayloadToPlainText(payload);
        case RequestBodyTypeEnum.URLENCODED:
        case RequestBodyTypeEnum.FORM_DATA:
            return convertPayloadToFormData(payload, true);
        default:
            return null;
    }
};
