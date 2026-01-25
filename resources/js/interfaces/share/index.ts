/**
 * Interfaces for shareable links feature
 */

import type { RequestLog } from '@/interfaces/history/logs';
import type { STATUS } from '@/interfaces/http/status';

/**
 * The payload structure stored in a shareable link.
 *
 * Contains the pending request state and optional response data
 * that will be restored when the link is accessed.
 */
export interface ShareableLinkPayload {
    /** The HTTP method for the request */
    method: string;

    /** The API endpoint path */
    endpoint: string;

    /** Request headers */
    headers: Array<{
        key: string;
        value: string | number | boolean | null;
    }>;

    /** Query parameters */
    queryParameters: Array<{
        key: string;
        value: string;
        type?: 'text' | 'file';
    }>;

    /** Request body organized by method and payload type */
    body: Record<
        string,
        Record<string, FormData | string | null | undefined> | undefined
    >;

    /** The currently selected payload type */
    payloadType: string;

    /** Authorization configuration */
    authorization: {
        type: string;
        value?: string | number | { username: string; password: string };
    };

    /** The response data (if available) */
    response?: {
        status: STATUS;
        statusCode: number;
        statusText: string;
        body: string;
        sizeInBytes: number;
        headers: Array<{ key: string; value: string | number | boolean | null }>;
        cookies: Array<{
            key: string;
            value: {
                raw: string;
                decrypted: string | null;
            };
        }>;
        timestamp: number;
        durationInMs: number;
    };

    /** Request log for history restoration */
    requestLog?: RequestLog;

    /** Application key this request was created in */
    applicationKey?: string;
}

/**
 * Shared state injected by the backend into window.Nimbus.
 *
 * Contains decoded payload and route existence metadata.
 */
export interface SharedState {
    /** The decoded shareable link payload */
    payload: ShareableLinkPayload;

    /** Whether the route exists in any application */
    routeExists: boolean;

    /** Error message if decoding failed */
    error?: string;
}
