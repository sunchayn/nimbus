/**
 * Utilities for encoding and decoding shareable link payloads.
 *
 * Uses pako (gzip/deflate) for compression to keep URLs within browser limits.
 */

import type { RequestLog } from '@/interfaces/history/logs';
import type { PendingRequest, Response } from '@/interfaces/http';
import type { ShareableLinkPayload } from '@/interfaces/share';
import pako from 'pako';

/**
 * Encodes a pending request and optional response into a URL-safe shareable string.
 *
 * The process:
 * 1. Create a minimal payload with essential request/response data
 * 2. Serialize to JSON
 * 3. Compress using pako (deflate)
 * 4. Base64 encode with URL-safe characters
 */
export function encodeShareablePayload(
    pendingRequest: PendingRequest,
    response?: Response,
    requestLog?: RequestLog,
    applicationKey?: string,
): string {
    const payload: ShareableLinkPayload = {
        method: pendingRequest.method,
        endpoint: pendingRequest.endpoint,
        headers: pendingRequest.headers.map(header => ({
            key: header.key,
            value: header.value,
        })),
        queryParameters: pendingRequest.queryParameters.map(param => ({
            key: param.key,
            value: param.value,
            type: param.type,
        })),
        body: pendingRequest.body,
        payloadType: pendingRequest.payloadType,
        authorization: {
            type: pendingRequest.authorization.type,
            value: pendingRequest.authorization.value,
        },
        applicationKey,
    };

    if (response) {
        payload.response = {
            status: response.status,
            statusCode: response.statusCode,
            statusText: response.statusText,
            body: response.body,
            sizeInBytes: response.sizeInBytes,
            headers: response.headers.map(header => ({
                key: header.key,
                value: header.value,
            })),
            cookies: response.cookies.map(cookie => ({
                key: cookie.key,
                value: cookie.value,
            })),
            timestamp: response.timestamp,
            durationInMs: requestLog?.durationInMs ?? 0,
        };
    }

    if (requestLog) {
        payload.requestLog = requestLog;
    }

    // Serialize to JSON
    const jsonString = JSON.stringify(payload);

    // Compress using pako (deflate)
    const compressed = pako.deflate(jsonString);

    // Convert to base64 with URL-safe characters
    const base64 = btoa(String.fromCharCode.apply(null, Array.from(compressed)));
    const urlSafeBase64 = base64
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');

    return urlSafeBase64;
}

/**
 * Builds the complete shareable URL for the current request.
 */
export function buildShareableUrl(basePath: string, encodedPayload: string): string {
    const baseUrl = window.location.origin;
    const cleanBasePath = basePath.startsWith('/') ? basePath : `/${basePath}`;

    return `${baseUrl}${cleanBasePath}?share=${encodedPayload}`;
}
