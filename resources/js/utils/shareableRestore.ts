import type { AuthorizationContract } from '@/interfaces';
import type { RequestLog } from '@/interfaces/history/logs';
import type { Request, RequestBodyTypeEnum } from '@/interfaces/http';
import type { Response } from '@/interfaces/http/response';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import type { ShareableLinkPayload } from '@/interfaces/share';
import type { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui';

/**
 * Maps header objects to ParameterContract format.
 */
export const mapHeadersToParameterContract = (
    headers: Array<{ key: string; value: string | number | boolean | null }>,
): ParameterContract[] => {
    return headers.map(header => ({
        key: header.key,
        value: String(header.value ?? ''),
        type: ParameterType.Text,
        enabled: true,
    }));
};

/**
 * Maps query parameter objects to ParameterContract format.
 */
export const mapQueryParametersToParameterContract = (
    queryParameters: Array<{ key: string; value: string; type?: string }>,
): ParameterContract[] => {
    return queryParameters.map(param => ({
        key: param.key,
        value: param.value,
        type: param.type === 'file' ? ParameterType.File : ParameterType.Text,
        enabled: true,
    }));
};

/**
 * Builds a route definition from payload data.
 */
export const buildRouteDefinition = (payload: ShareableLinkPayload): RouteDefinition => {
    return {
        endpoint: payload.endpoint,
        method: payload.method,
        schema: { shape: {}, extractionErrors: null },
        shortEndpoint: payload.endpoint,
    };
};

/**
 * Builds a Request object from the shared payload.
 */
export const buildRequestFromPayload = (payload: ShareableLinkPayload): Request => {
    return {
        method: payload.method,
        endpoint: payload.endpoint,
        headers: mapHeadersToParameterContract(payload.headers),
        body: null,
        queryParameters: mapQueryParametersToParameterContract(payload.queryParameters),
        payloadType: payload.payloadType as RequestBodyTypeEnum,
        authorization: payload.authorization as AuthorizationContract,
        routeDefinition: buildRouteDefinition(payload),
    };
};

/**
 * Builds a Response object from the shared payload response data.
 */
export const buildResponseFromPayload = (
    responseData: NonNullable<ShareableLinkPayload['response']>,
): Response => {
    return {
        status: responseData.status,
        statusCode: responseData.statusCode,
        statusText: responseData.statusText,
        body: responseData.body,
        sizeInBytes: responseData.sizeInBytes,
        headers: responseData.headers.map(header => ({
            key: header.key,
            value: header.value,
        })),
        cookies: responseData.cookies,
        timestamp: responseData.timestamp,
    };
};

/**
 * Creates a synthesized RequestLog from payload data.
 */
export const createSynthesizedLog = (payload: ShareableLinkPayload): RequestLog => {
    if (!payload.response) {
        throw new Error('Cannot create synthesized log without response data');
    }

    return {
        durationInMs: payload.response.durationInMs,
        isProcessing: false,
        request: buildRequestFromPayload(payload),
        response: buildResponseFromPayload(payload.response),
        importedFromShare: true,
    };
};
