import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { PayloadObject } from '@/interfaces/schema/payload';
import { convertPayloadToFormData } from '@/utils/http';

/**
 * Convert payload to JSON format with proper indentation.
 */
export const convertPayloadToJson = (payload: PayloadObject): string => {
    return JSON.stringify(payload, null, 3);
};

/**
 * Convert payload to plain text format (key=value pairs).
 */
export const convertPayloadToPlainText = (payload: PayloadObject): string => {
    return Object.entries(payload)
        .map(([key, value]) => `${key}=${value};`)
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
        case RequestBodyTypeEnum.FORM_DATA:
            return convertPayloadToFormData(payload);
        default:
            return null;
    }
};
