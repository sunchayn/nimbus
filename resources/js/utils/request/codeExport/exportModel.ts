import type { ParameterContract } from '@/interfaces';
import type { ResolverFn } from '@/interfaces/common/env-vars';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import { ParameterType } from '@/interfaces/ui/key-value-parameters';
import { getMimeTypeForRequest } from '@/utils/request/contentTypeHeaderGenerator';
import { buildRequestUrl } from '@/utils/request/requestUrlBuilder';
import { maskJson, maskValue } from './masking';
import type { ExportBody, ExportModel, Field, Header } from './types';

const BODYLESS_METHODS = ['GET', 'HEAD'];

const isEnabled = (parameter: ParameterContract): boolean =>
    parameter.enabled && parameter.key.trim() !== '';

const toQueryParameter = (key: string, value: string): ParameterContract => ({
    type: ParameterType.Text,
    key,
    value,
    enabled: true,
});

/*
 * Body.
 */

const readBinaryBody = (stored: FormData): ExportBody => {
    const file = stored.get('file');

    return file instanceof File ? { kind: 'file', name: file.name } : { kind: 'none' };
};

const readFields = (stored: FormData, resolver: ResolverFn): Field[] =>
    Array.from(stored.entries()).map(([key, value]) =>
        value instanceof File
            ? { key, value: value.name, file: true }
            : { key, value: maskValue(key, resolver(value)) },
    );

const readTextBody = (stored: string, resolver: ResolverFn): ExportBody => {
    const text = resolver(stored);

    try {
        const parsed = JSON.parse(text);

        if (parsed !== null && typeof parsed === 'object') {
            return { kind: 'json', value: maskJson(parsed) };
        }
    } catch {
        // Not JSON, so the text is sent as written.
    }

    return { kind: 'text', value: text };
};

const readBody = (request: PendingRequest, resolver: ResolverFn): ExportBody => {
    const stored = request.body[request.method]?.[request.payloadType] ?? null;

    if (stored === null || stored === '') {
        return { kind: 'none' };
    }

    if (!(stored instanceof FormData)) {
        return readTextBody(stored, resolver);
    }

    if (request.payloadType === RequestBodyTypeEnum.BINARY) {
        return readBinaryBody(stored);
    }

    return {
        kind: 'fields',
        fields: readFields(stored, resolver),
        urlencoded: request.payloadType === RequestBodyTypeEnum.URLENCODED,
    };
};

/**
 * A GET request has no body, so its fields become query parameters like the relay does.
 */
const bodyToQueryParameters = (body: ExportBody): ParameterContract[] => {
    if (body.kind === 'json' && body.value !== null && !Array.isArray(body.value)) {
        return Object.entries(body.value as Record<string, unknown>).map(([key, value]) =>
            toQueryParameter(key, String(value)),
        );
    }

    if (body.kind === 'fields') {
        return body.fields.map(field => toQueryParameter(field.key, field.value));
    }

    return [];
};

/*
 * Query and headers.
 */

const buildQuery = (request: PendingRequest, resolver: ResolverFn): ParameterContract[] =>
    request.queryParameters.filter(isEnabled).map(parameter => ({
        ...parameter,
        value: maskValue(parameter.key, resolver(parameter.value)),
    }));

const buildUserHeaders = (request: PendingRequest, resolver: ResolverFn): Header[] =>
    request.headers.filter(isEnabled).map(header => ({
        key: header.key,
        value: maskValue(header.key, resolver(header.value)),
    }));

const hasHeader = (headers: Header[], name: string): boolean =>
    headers.some(header => header.key.toLowerCase() === name);

/**
 * Gets the Content-Type header the code must send, or none when the user set one.
 * A multipart body gets none because the HTTP client adds the boundary.
 */
const buildContentTypeHeaders = (
    request: PendingRequest,
    body: ExportBody,
    userHeaders: Header[],
): Header[] => {
    const mimeType = body.kind === 'none' ? null : getMimeTypeForRequest(request);
    const isMultipart = body.kind === 'fields' && !body.urlencoded;

    if (!mimeType || isMultipart || hasHeader(userHeaders, 'content-type')) {
        return [];
    }

    return [{ key: 'Content-Type', value: mimeType }];
};

/**
 * Secrets never reach the code, so the credentials are placeholders.
 */
const buildAuthorizationHeaders = (request: PendingRequest): Header[] => {
    switch (request.authorization.type) {
        case AuthorizationType.Bearer:
            return [{ key: 'Authorization', value: 'Bearer YOUR_TOKEN' }];
        case AuthorizationType.Basic:
            return [{ key: 'Authorization', value: 'Basic YOUR_CREDENTIALS' }];
        default:
            return [];
    }
};

const actsAsUser = (request: PendingRequest): boolean =>
    request.authorization.type === AuthorizationType.CurrentUser ||
    request.authorization.type === AuthorizationType.Impersonate;

/**
 * Reads a request into the model the generators write code from.
 * Variables are resolved and secret values become placeholders.
 */
export const buildExportModel = (
    request: PendingRequest,
    baseUrl: string,
    resolver: ResolverFn,
): ExportModel => {
    const method = request.method.toUpperCase();
    const isBodyless = BODYLESS_METHODS.includes(method);
    const readBodyValue = readBody(request, resolver);
    const body: ExportBody = isBodyless ? { kind: 'none' } : readBodyValue;

    const query = [
        ...buildQuery(request, resolver),
        ...(isBodyless ? bodyToQueryParameters(readBodyValue) : []),
    ];
    const userHeaders = buildUserHeaders(request, resolver);
    const headers = [
        ...userHeaders,
        ...buildContentTypeHeaders(request, body, userHeaders),
        ...buildAuthorizationHeaders(request),
    ];

    const url = buildRequestUrl(baseUrl, resolver(request.endpoint), query, resolver);
    const { pathname, search } = new URL(url);

    return {
        method,
        url,
        path: pathname + search,
        headers,
        body,
        actor: actsAsUser(request),
    };
};
