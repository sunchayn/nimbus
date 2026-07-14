import type { ParameterContract } from '@/interfaces';
import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import type { ResolverFn } from '@/interfaces/common/env-vars';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { buildRequestUrl } from '@/utils';
import { getMimeTypeForPayloadType } from '@/utils/request/contentTypeHeaderGenerator';

/**
 * Result of cURL command generation.
 */
export interface CurlGenerationResult {
    command: string;
    hasSpecialAuth: boolean;
}

/**
 * Generates a complete cURL command from a pending request.
 *
 * Builds a properly formatted cURL command with method, URL, headers,
 * authorization, and body data.
 */
export function generateCurlCommand(
    request: PendingRequest,
    baseUrl: string,
    resolver: ResolverFn,
): CurlGenerationResult {
    const { queryParameters, requestBody } = getEffectiveQueryParametersAndBodyValue(
        request,
        resolver,
    );

    const methodPart = buildHttpMethodPart(request.method);
    const fullUrl = buildRequestUrl(
        baseUrl,
        resolver(request.endpoint),
        queryParameters,
        resolver,
    );
    const headerParts = buildRequestHeaderParts(request, resolver);
    const authPart = buildAuthorizationHeaderPart(request.authorization, resolver);
    const bodyParts = buildRequestBodyParts(requestBody, resolver);

    const command = ['curl']
        .concat(methodPart ? [methodPart] : [])
        .concat([`"${fullUrl}"`])
        .concat(headerParts)
        .concat(authPart ? [authPart] : [])
        .concat(bodyParts)
        .join(' \\\n  ');

    return {
        command: command,
        hasSpecialAuth: requiresSpecialAuthorization(request.authorization),
    };
}

function getEffectiveQueryParametersAndBodyValue(
    request: PendingRequest,
    resolver: ResolverFn,
): {
    queryParameters: ParameterContract[];
    requestBody: FormData | string | null;
} {
    const requestBody = getRequestEffectiveBody(request);

    const isGetRequest = request.method.toLowerCase() === 'get';

    // In GET requests, we want to move the body content to query parameters and discard the body.
    if (isGetRequest) {
        const requestBodyKeyValuePairs = transformRequestBodyToKeyValuePairs(
            requestBody,
            request.payloadType,
            resolver,
        );

        return {
            queryParameters: [
                ...request.queryParameters.filter(isValidParameter),
                ...convertKeyValuePairsToQueryParameters(
                    requestBodyKeyValuePairs,
                    resolver,
                ),
            ],
            requestBody: null,
        };
    }

    return {
        queryParameters: request.queryParameters,
        requestBody: requestBody,
    };
}

/**
 * Builds HTTP method part.
 */
function buildHttpMethodPart(method: string): string | null {
    const upperMethod = method.toUpperCase();

    // GET is the default HTTP method in cURL, so no explicit flag needed
    if (upperMethod === 'GET') {
        return null;
    }

    return `-X ${upperMethod}`;
}

/**
 * Builds request header parts.
 */
function buildRequestHeaderParts(
    request: PendingRequest,
    resolver: ResolverFn,
): string[] {
    const validHeaders = getValidHeaders(request);

    const headerParts = validHeaders.map(function (header) {
        const headerValue = resolver(header.value);

        return `-H "${header.key}: ${headerValue}"`;
    });

    // Add Content-Type header for payload types with MIME types if not already present
    const mimeType = getMimeTypeForPayloadType(request.payloadType);
    if (mimeType) {
        const hasContentType = validHeaders.some(
            header => header.key.toLowerCase() === 'content-type',
        );

        if (!hasContentType) {
            headerParts.push(`-H "Content-Type: ${mimeType}"`);
        }
    }

    return headerParts;
}

/**
 * Builds authorization header part.
 */
function buildAuthorizationHeaderPart(
    authorization: AuthorizationContract,
    resolver: ResolverFn,
): string | null {
    const authHeader = buildAuthHeader(authorization, resolver);

    if (!authHeader) {
        return null;
    }

    return `-H "${authHeader}"`;
}

function convertKeyValuePairsToQueryParameters(
    keyValuePairs: Record<string, string>,
    _resolver: ResolverFn,
): ParameterContract[] {
    return Object.entries(keyValuePairs).map(([key, value]) => ({
        type: ParameterType.Text,
        enabled: true,
        key,
        value: value,
    }));
}

/**
 * Filters headers to only include valid ones.
 */
function getValidHeaders(request: PendingRequest): ParameterContract[] {
    return request.headers.filter(isValidParameter);
}

/**
 * Checks if a parameter is valid for inclusion.
 */
function isValidParameter(parameter: ParameterContract): boolean {
    return parameter.enabled && parameter.key.trim() !== '';
}

/**
 * Builds authorization header string.
 */
function buildAuthHeader(
    authorization: AuthorizationContract,
    resolver: ResolverFn,
): string | null {
    if (!authorization) {
        return null;
    }

    switch (authorization.type) {
        case AuthorizationType.Bearer:
            return `Authorization: Bearer ${resolver(authorization.value)}`;

        case AuthorizationType.Basic:
            return buildBasicAuthHeader(authorization.value, resolver);

        case AuthorizationType.None:
        case AuthorizationType.CurrentUser:
        case AuthorizationType.Impersonate:
            return null;

        default:
            return null;
    }
}

/**
 * Builds Basic authentication header.
 */
function buildBasicAuthHeader(
    authValue: {
        username: string;
        password: string;
    },
    resolver: ResolverFn,
): string | null {
    // btoa() encodes username:password string to Base64 for HTTP Basic Authentication
    const credentials = btoa(
        `${resolver(authValue.username)}:${resolver(authValue.password)}`,
    );

    return `Authorization: Basic ${credentials}`;
}

function getRequestEffectiveBody(request: PendingRequest): FormData | string | null {
    const bodyData = request.body;

    const methodBodies = bodyData[request.method];

    if (methodBodies === undefined) {
        return null;
    }

    const body = methodBodies[request.payloadType ?? RequestBodyTypeEnum.EMPTY];

    if (body === undefined) {
        return null;
    }

    return body;
}

/**
 * Builds request body parts.
 */
function buildRequestBodyParts(
    requestBody: FormData | string | null,
    resolver: ResolverFn,
): string[] {
    if (requestBody === null) {
        return [];
    }

    return convertBodyValueToRequestParts(requestBody, resolver);
}

function transformRequestBodyToKeyValuePairs(
    bodyValue: string | FormData | null,
    payloadType: RequestBodyTypeEnum,
    resolver: ResolverFn,
): Record<string, string> {
    if (bodyValue === null) {
        return {};
    }

    if (bodyValue instanceof FormData) {
        return Object.fromEntries(
            Array.from(bodyValue.entries()).map(([key, value]) => [
                key,
                value instanceof File ? `@${value}` : value,
            ]),
        );
    }

    const resolvedBodyValue = typeof bodyValue === 'string' ? resolver(bodyValue) : '';

    if (payloadType === RequestBodyTypeEnum.JSON) {
        return JSON.parse(resolvedBodyValue);
    }

    return resolvedBodyValue.split('\n').reduce<Record<string, string>>(function (
        carry,
        bodyLine,
    ) {
        const { 0: key, 1: value } = bodyLine.split('=');

        carry[key] = value;

        return carry as Record<string, string>;
    }, {}) as Record<string, string>;
}

/**
 * Formats body value based on payload type.
 */
function convertBodyValueToRequestParts(
    bodyValue: string | FormData,
    resolver: ResolverFn,
): string[] {
    if (bodyValue instanceof FormData) {
        return convertFormDataToCUrlFields(bodyValue);
    }

    return [`-d '${resolver(bodyValue)}'`];
}

/**
 * Converts FormData object to cURL field strings.
 */
function convertFormDataToCUrlFields(formData: FormData): string[] {
    return Array.from(formData.entries()).map(([key, value]) => {
        if (value instanceof File) {
            return `-F ${key}=@${value.name}`;
        }

        return `-F ${key}=${value}`;
    });
}

/**
 * Checks if authorization requires special handling (not standard HTTP headers).
 */
function requiresSpecialAuthorization(authorization: AuthorizationContract): boolean {
    if (authorization.type === AuthorizationType.CurrentUser) {
        return true;
    }

    return authorization.type === AuthorizationType.Impersonate;
}
