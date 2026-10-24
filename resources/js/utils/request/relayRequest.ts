import type { AuthorizationContract, ParameterContract } from '@/interfaces';
import type { ResolverFn } from '@/interfaces/common/env-vars';
import { AuthorizationType } from '@/interfaces/generated';
import type {
    HttpHeaders,
    PendingRequest,
    RelayProxyResponse,
    Response,
} from '@/interfaces/http';
import { getStatusGroup } from '@/utils/http';
import { generateContentTypeHeader } from '@/utils/request/contentTypeHeaderGenerator';
import { buildRequestUrl } from '@/utils/request/requestUrlBuilder';

export type ResolveEnvironmentVariables = ResolverFn;

export interface RelayPayload {
    endpoint: string;
    method: string;
    headers: HttpHeaders[];
    authorization: AuthorizationContract;
    body: FormData | string | null;
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

export const createRelayPayload = (
    request: PendingRequest,
    apiUrl: string,
    resolve: ResolveEnvironmentVariables,
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
    );

    return {
        endpoint: buildUrlFromRequest(request, apiUrl, resolve),
        method: request.method,
        headers: headersWithContentType,
        authorization: buildRelayAuthorization(request.authorization, resolve),
        body: getMemoizedBody(request, resolve),
    };
};

export const transformRelayResponse = (relayResponse: RelayProxyResponse): Response => {
    const headers = relayResponse.headers;
    const statusCode = relayResponse.statusCode;

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
    };
};
