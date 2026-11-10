import type { AuthorizationContract, ParameterContract } from '@/interfaces';
import type { ResolverFn } from '@/interfaces/common/env-vars';
import { AuthorizationType } from '@/interfaces/generated';
import type {
    HttpHeaders,
    PendingRequest,
    RelayProxyResponse,
    RequestOptions,
    Response,
} from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { getStatusGroup } from '@/utils/http';
import {
    generateContentTypeHeader,
    getMimeTypeForRequest,
} from '@/utils/request/contentTypeHeaderGenerator';
import { buildRequestUrl } from '@/utils/request/requestUrlBuilder';

export type ResolveEnvironmentVariables = ResolverFn;

export interface RelayPayload {
    endpoint: string;
    method: string;
    headers: HttpHeaders[];
    authorization: AuthorizationContract;
    body: FormData | File | string | null;
    environment?: string;
    options?: Record<string, number>;
}

/**
 * Builds the full request URL with enabled query parameters and resolved variables.
 */
export const buildUrlFromRequest = (
    request: PendingRequest,
    apiUrl: string,
    resolve: ResolveEnvironmentVariables,
): string => {
    // Remove leading slashes to prevent double slashes in final URL.
    const endpoint = resolve(request.endpoint).replace(/^\/+/, '');

    return buildRequestUrl(
        apiUrl,
        endpoint,
        // Only append enabled parameters with non-empty keys to avoid malformed URLs.
        request.queryParameters.filter(
            (parameter: ParameterContract) => parameter.enabled,
        ),
        resolve,
    );
};

/**
 * Body is memoized by method > payload type structure for better UX (keep-alive state).
 */
export const getMemoizedBody = (
    request: PendingRequest,
    resolve: ResolveEnvironmentVariables,
): FormData | string | null => {
    // The first lookup picks the body for the HTTP method.
    const methodBodies = request.body[request.method] ?? null;

    // The second lookup picks the body for the payload type.
    // Both lookups are needed because the bodies are nested by method and payload type.
    const body = methodBodies ? (methodBodies[request.payloadType] ?? null) : null;

    if (body instanceof FormData) {
        return body;
    }

    if (body === null) {
        return null;
    }

    return resolve(body as string);
};

/**
 * A binary body is sent as the file itself, so the relay can read its raw bytes.
 */
const getRelayBody = (
    request: PendingRequest,
    resolve: ResolveEnvironmentVariables,
): FormData | File | string | null => {
    const body = getMemoizedBody(request, resolve);

    if (request.payloadType !== RequestBodyTypeEnum.BINARY) {
        return body;
    }

    const file = body instanceof FormData ? body.get('file') : null;

    return file instanceof File && file.size > 0 ? file : null;
};

export const buildRelayAuthorization = (
    authorization: AuthorizationContract,
    resolve: ResolveEnvironmentVariables,
): AuthorizationContract => {
    switch (authorization.type) {
        case AuthorizationType.Basic:
            return {
                type: authorization.type,
                value: {
                    username: resolve(authorization.value.username),
                    password: resolve(authorization.value.password),
                },
            };

        case AuthorizationType.Bearer:
            return {
                type: authorization.type,
                value: resolve(authorization.value),
            };

        default:
            return authorization;
    }
};

/**
 * Maps the set options to relay fields. Booleans go as 1 or 0 because they travel in form data.
 */
export const buildRelayOptions = (
    options: RequestOptions | undefined,
): Record<string, number> | undefined => {
    const mapped: Record<string, number> = {};

    if (options?.followRedirects !== undefined) {
        mapped.follow_redirects = options.followRedirects ? 1 : 0;
    }

    if (options?.timeout !== undefined) {
        mapped.timeout = options.timeout;
    }

    if (options?.verifySsl !== undefined) {
        mapped.verify_ssl = options.verifySsl ? 1 : 0;
    }

    return Object.keys(mapped).length > 0 ? mapped : undefined;
};

export const createRelayPayload = (
    request: PendingRequest,
    apiUrl: string,
    resolve: ResolveEnvironmentVariables,
    environment?: string,
): RelayPayload => {
    // Generate Content-Type header just before making the request
    // This ensures the correct header is sent without persisting it in the store
    const headersWithContentType = generateContentTypeHeader(
        request.payloadType,
        request.headers
            .filter(
                (parameter: ParameterContract) =>
                    parameter.enabled && parameter.key.trim() !== '',
            )
            .map(
                (parameter: ParameterContract): HttpHeaders => ({
                    key: parameter.key,
                    value: resolve(parameter.value),
                }),
            ),
        getMimeTypeForRequest(request),
    );

    const options = buildRelayOptions(request.options);

    return {
        endpoint: buildUrlFromRequest(request, apiUrl, resolve),
        method: request.method,
        headers: headersWithContentType,
        authorization: buildRelayAuthorization(request.authorization, resolve),
        body: getRelayBody(request, resolve),
        ...(environment ? { environment } : {}),
        ...(options ? { options } : {}),
    };
};

export const transformRelayResponse = (relayResponse: RelayProxyResponse): Response => {
    const headers = relayResponse.headers;
    const statusCode = relayResponse.statusCode;

    // The file content is forwarded once and never kept in the store or history.
    const download = relayResponse.download
        ? {
              filename: relayResponse.download.filename,
              mimeType: relayResponse.download.mimeType,
              sizeInBytes: relayResponse.download.sizeInBytes,
          }
        : null;

    const contentLengthHeader = relayResponse.headers.find(
        (header: HttpHeaders) => header.key.toLowerCase() === 'content-length',
    );

    return {
        status: getStatusGroup(statusCode),
        statusCode,
        statusText: relayResponse.statusText,
        body: relayResponse.body,
        // Prefer content-length header for accurate size, fallback to body length
        sizeInBytes: contentLengthHeader
            ? Number(contentLengthHeader.value)
            : relayResponse.body.length,
        headers: headers,
        cookies: relayResponse.cookies.map(cookie => ({
            key: cookie.key,
            value: {
                raw: cookie.value.raw,
                decrypted: cookie.value.decrypted,
            },
        })),
        timestamp: relayResponse.timestamp,
        download,
        sent: relayResponse.sent ?? null,
    };
};
