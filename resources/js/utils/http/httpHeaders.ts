import type { RequestHeader } from '@/interfaces/http';
import type { AxiosResponseHeaders } from 'axios';

/**
 * Normalizes raw HTTP headers into a consistent array format.
 *
 * Converts Axios response headers (which can be strings or string arrays)
 * into a flat array of key-value pairs for consistent processing.
 *
 * @example
 * // Before normalization (Axios response headers):
 * {
 *   'content-type': 'application/json',
 *   'set-cookie': ['session=abc123', 'theme=dark'],
 *   'cache-control': 'no-cache'
 * }
 *
 * // After normalization:
 * [
 *   { key: 'content-type', value: 'application/json' },
 *   { key: 'set-cookie', value: 'session=abc123' },
 *   { key: 'set-cookie', value: 'theme=dark' },
 *   { key: 'cache-control', value: 'no-cache' }
 * ]
 */
export const normalizeHeaders = (
    headers: AxiosResponseHeaders | undefined,
): Array<RequestHeader> => {
    if (!headers) {
        return [];
    }

    return Object.keys(headers).flatMap(key => {
        let values = headers[key];

        values = Array.isArray(values) ? values : [values];

        // An empty array should not happen, but the guard keeps the result defined.
        if (values.length === 0) {
            return {
                key,
                value: null,
            };
        }

        // Map each value to a separate header object
        // This handles multiple values for same header (e.g., set-cookie)
        return values.map((value: string) => ({
            key,
            value,
        }));
    });
};
