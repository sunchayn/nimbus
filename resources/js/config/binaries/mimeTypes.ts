/**
 * Common mime types used when no file is attached to the object.
 */
export const MIME_TYPES = [
    'image/png',
    'image/jpeg',
    'image/gif',
    'image/webp',
    'image/svg+xml',
    'application/pdf',
    'application/zip',
    'application/json',
    'text/plain',
    'text/csv',
] as const;

/**
 * Matches property names that hold a mime type, such as mime_type or contentType.
 */
export const MIME_TYPE_PATTERN =
    /^(.+[-_])?(mime[-_]?type|content[-_]?type|media[-_]?type)$/i;

/**
 * Matches property names that hold the name of a file, such as filename or file_name.
 */
export const FILE_NAME_PATTERN = /^(file[-_]?name|filename)$/i;
