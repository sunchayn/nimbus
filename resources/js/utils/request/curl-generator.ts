import { ParametersExternalContract } from '@/interfaces';
import { AuthorizationContract } from '@/interfaces/auth/authorization';
import { AuthorizationType } from '@/interfaces/generated';
import { PendingRequest, RequestBodyTypeEnum, RequestHeader } from '@/interfaces/http';
import { buildRequestUrl } from '@/utils';

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
): CurlGenerationResult {
    const { queryParameters, requestBody } =
        getEffectiveQueryParametersAndBodyValue(request);

    const methodPart = buildHttpMethodPart(request.method);
    const fullUrl = buildRequestUrl(baseUrl, request.endpoint, queryParameters);
    const headerParts = buildRequestHeaderParts(request);
    const authPart = buildAuthorizationHeaderPart(request.authorization);
    const bodyParts = buildRequestBodyParts(requestBody);

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

function getEffectiveQueryParametersAndBodyValue(request: PendingRequest): {
    queryParameters: ParametersExternalContract[];
    requestBody: FormData | string | null;
} {
    const requestBody = getRequestEffectiveBody(request);

    const isGetRequest = request.method.toLowerCase() === 'get';

    // In GET requests, we want to move the body content to query parameters and discard the body.
    if (isGetRequest) {
        const requestBodyKeyValuePairs = transformRequestBodyToKeyValuePairs(
            requestBody,
            request.payloadType,
        );

        return {
            queryParameters: [
                ...request.queryParameters,
                ...convertKeyValuePairsToQueryParameters(requestBodyKeyValuePairs),
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
function buildRequestHeaderParts(request: PendingRequest): string[] {
    const validHeaders = getValidHeaders(request);

    const headerParts = validHeaders.map(header => `-H "${header.key}: ${header.value}"`);

    // Add Content-Type header for JSON payloads if not already present
    if (request.payloadType === RequestBodyTypeEnum.JSON) {
        const hasContentType = validHeaders.some(
            header => header.key.toLowerCase() === 'content-type',
        );

        if (!hasContentType) {
            headerParts.push(`-H "Content-Type: application/json"`);
        }
    }

    return headerParts;
}

/**
 * Builds authorization header part.
 */
function buildAuthorizationHeaderPart(
    authorization: AuthorizationContract,
): string | null {
    const authHeader = buildAuthHeader(authorization);

    if (!authHeader) {
        return null;
    }

    return `-H "${authHeader}"`;
}

function convertKeyValuePairsToQueryParameters(
    keyValuePairs: Record<string, string>,
): ParametersExternalContract[] {
    return Object.entries(keyValuePairs).map(([key, value]) => ({
        type: 'text',
        key,
        value,
    }));
}

/**
 * Filters headers to only include valid ones.
 */
function getValidHeaders(request: PendingRequest): RequestHeader[] {
    return request.headers.filter(isValidHeader);
}

/**
 * Checks if a header is valid for inclusion.
 */
function isValidHeader(header: RequestHeader): boolean {
    return header.key.trim() !== '' && header.value !== null && header.value !== '';
}

/**
 * Builds authorization header string.
 */
function buildAuthHeader(authorization: AuthorizationContract): string | null {
    if (!authorization) {
        return null;
    }

    switch (authorization.type) {
        case AuthorizationType.Bearer:
            return `Authorization: Bearer ${authorization.value}`;

        case AuthorizationType.Basic:
            return buildBasicAuthHeader(authorization.value);

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
function buildBasicAuthHeader(authValue: {
    username: string;
    password: string;
}): string | null {
    // btoa() encodes username:password string to Base64 for HTTP Basic Authentication
    const credentials = btoa(`${authValue.username}:${authValue.password}`);

    return `Authorization: Basic ${credentials}`;
}

function getRequestEffectiveBody(request: PendingRequest): FormData | string | null {
    const bodyData = request.body;

    const methodBodies = bodyData[request.method];

    if (methodBodies === undefined) {
        return null;
    }

    const body = methodBodies[request.payloadType];

    if (body === undefined) {
        return null;
    }

    return body;
}

/**
 * Builds request body parts.
 */
function buildRequestBodyParts(requestBody: FormData | string | null): string[] {
    if (requestBody === null) {
        return [];
    }

    return convertBodyValueToRequestParts(requestBody);
}

function transformRequestBodyToKeyValuePairs(
    bodyValue: string | FormData | null,
    payloadType: RequestBodyTypeEnum,
): Record<string, string> {
    if (bodyValue === null) {
        return {};
    }

    if (typeof bodyValue === 'string' && payloadType === RequestBodyTypeEnum.JSON) {
        return JSON.parse(bodyValue);
    }

    if (bodyValue instanceof FormData) {
        return Object.fromEntries(
            Array.from(bodyValue.entries()).map(([key, value]) => [
                key,
                value instanceof File ? `@${value}` : value,
            ]),
        );
    }

    return bodyValue.split('\n').reduce<Record<string, string>>(function (
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
function convertBodyValueToRequestParts(bodyValue: string | FormData): string[] {
    if (typeof bodyValue === 'string') {
        return [`-d '${bodyValue}'`];
    }

    return convertFormDataToCUrlFields(bodyValue);
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
