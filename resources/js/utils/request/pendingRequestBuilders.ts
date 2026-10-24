import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest, RequestBodyTypeEnum } from '@/interfaces/http';
import type { ShareableLinkPayload } from '@/interfaces/share';
import type { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui';

/**
 * Returns shallow copies of the parameters so edits do not leak between requests.
 */
export const cloneParameters = (parameters: ParameterContract[]): ParameterContract[] =>
    parameters.map(parameter => ({ ...parameter }));

/**
 * Returns a copy of the body map with the content placed in the method and type slot.
 */
export const restoreResponseBody = (
    currentBody: PendingRequest['body'],
    method: string,
    payloadType: RequestBodyTypeEnum,
    newBodyContent: FormData | string | null,
): PendingRequest['body'] => {
    const body = { ...currentBody };

    body[method] = {
        ...(body[method] ?? {}),
        [payloadType]: newBodyContent,
    };

    return body;
};

/**
 * Builds the authorization a new request starts with for the given default type.
 */
export const createDefaultAuthorization = (
    defaultType: AuthorizationType,
): AuthorizationContract => {
    switch (defaultType) {
        case AuthorizationType.CurrentUser:
            return { type: AuthorizationType.CurrentUser };
        case AuthorizationType.Impersonate:
            return { type: AuthorizationType.Impersonate, value: 1 };
        case AuthorizationType.Bearer:
            return { type: AuthorizationType.Bearer, value: '' };
        case AuthorizationType.Basic:
            return {
                type: AuthorizationType.Basic,
                value: { username: '', password: '' },
            };
        default:
            return { type: AuthorizationType.None };
    }
};

/**
 * Builds a pending request from a shared link payload.
 */
export const createRequestFromShareableLinkPayload = (
    payload: ShareableLinkPayload,
): PendingRequest => {
    const wasExecuted =
        payload.response !== undefined && payload.response.durationInMs !== undefined;

    return {
        method: payload.method.toUpperCase(),
        endpoint: payload.endpoint,
        headers: payload.headers.map(
            (header: { key: string; value: string | number | boolean | null }) => ({
                key: header.key,
                value: String(header.value ?? ''),
                type: ParameterType.Text,
                enabled: true,
            }),
        ),
        body: payload.body,
        payloadType: payload.payloadType as RequestBodyTypeEnum,
        schema: {
            shape: {},
            extractionErrors: null,
        },
        queryParameters: payload.queryParameters.map(
            (param: { key: string; value: string; type?: 'text' | 'file' }) => ({
                key: param.key,
                value: param.value,
                type: param.type === 'file' ? ParameterType.File : ParameterType.Text,
                enabled: true,
            }),
        ),
        authorization: {
            type: payload.authorization.type as AuthorizationType,
            value: payload.authorization.value,
        } as AuthorizationContract,
        supportedRoutes: [],
        routeDefinition: {
            endpoint: payload.endpoint,
            method: payload.method.toUpperCase(),
            schema: {
                shape: {},
                extractionErrors: null,
            },
            shortEndpoint: payload.endpoint,
        },
        isProcessing: false,
        wasExecuted,
        durationInMs: payload.response?.durationInMs ?? 0,
        transactionMode: false,
    };
};
