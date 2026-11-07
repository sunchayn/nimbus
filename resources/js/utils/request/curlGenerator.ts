import type { ParameterContract } from '@/interfaces';
import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import type { ResolverFn } from '@/interfaces/common/env-vars';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { buildRequestUrl } from '@/utils';
import { getMimeTypeForRequest } from '@/utils/request/contentTypeHeaderGenerator';

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
    const requestBody = getRequestEffectiveBody(request);
    const bodyParts = buildRequestBodyParts(requestBody, request.payloadType, resolver);

    const methodPart = buildHttpMethodPart(request.method, bodyParts.length > 0);
    const fullUrl = buildRequestUrl(
        baseUrl,
        resolver(request.endpoint),
        request.queryParameters,
        resolver,
    );
    const headerParts = buildRequestHeaderParts(request, resolver);
    const authPart = buildAuthorizationHeaderPart(request.authorization, resolver);

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

/**
 * Builds HTTP method part.
 */
function buildHttpMethodPart(method: string, hasBody: boolean): string | null {
    const upperMethod = method.toUpperCase();

    // GET is the default HTTP method in cURL, unless a body is sent, which makes cURL use POST.
    if (upperMethod === 'GET' && !hasBody) {
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

    const mimeType = getMimeTypeForRequest(request);
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
    // Basic authentication needs the username and password joined and encoded in Base64.
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
 * Wraps a value in single quotes so the shell reads it literally.
 */
function quoteShellValue(value: string): string {
    return `'${value.replace(/'/g, `'\\''`)}'`;
}

/**
 * Builds the cURL body flags for the payload type.
 *
 * The body never goes into the query string, whatever the HTTP method is.
 */
function buildRequestBodyParts(
    requestBody: FormData | string | null,
    payloadType: RequestBodyTypeEnum,
    resolver: ResolverFn,
): string[] {
    if (requestBody === null || payloadType === RequestBodyTypeEnum.EMPTY) {
        return [];
    }

    switch (payloadType) {
        case RequestBodyTypeEnum.FORM_DATA:
            return requestBody instanceof FormData
                ? buildFormDataParts(requestBody, resolver)
                : [];

        case RequestBodyTypeEnum.URLENCODED:
            return requestBody instanceof FormData
                ? buildUrlencodedParts(requestBody, resolver)
                : [];

        case RequestBodyTypeEnum.BINARY:
            return requestBody instanceof FormData ? buildBinaryParts(requestBody) : [];

        default:
            return typeof requestBody === 'string'
                ? [`-d ${quoteShellValue(resolver(requestBody))}`]
                : [];
    }
}

/**
 * Builds one -F flag for each form data field.
 */
function buildFormDataParts(formData: FormData, resolver: ResolverFn): string[] {
    return Array.from(formData.entries()).map(([key, value]) =>
        value instanceof File
            ? `-F ${quoteShellValue(`${key}=@${value.name}`)}`
            : `-F ${quoteShellValue(`${key}=${resolver(value)}`)}`,
    );
}

/**
 * Builds one --data-urlencode flag for each field.
 */
function buildUrlencodedParts(formData: FormData, resolver: ResolverFn): string[] {
    return Array.from(formData.entries()).map(
        ([key, value]) =>
            `--data-urlencode ${quoteShellValue(`${key}=${resolver(String(value))}`)}`,
    );
}

/**
 * Builds the flag that sends the file as the raw request body.
 */
function buildBinaryParts(formData: FormData): string[] {
    const file = formData.get('file');

    return file instanceof File
        ? [`--data-binary ${quoteShellValue(`@${file.name}`)}`]
        : [];
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
