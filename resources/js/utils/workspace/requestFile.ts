import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import { AuthorizationType } from '@/interfaces/generated';
import type { PendingRequest } from '@/interfaces/http';
import { RequestBodyTypeEnum } from '@/interfaces/http';
import type { ParameterContract } from '@/interfaces/ui';
import { ParameterType } from '@/interfaces/ui';
import type { Tab } from '@/interfaces/ui/tabs';
import type {
    WorkspaceAuthorization,
    WorkspaceBodyType,
    WorkspaceParameter,
    WorkspaceRequestFile,
    WorkspaceRequestPayload,
} from '@/interfaces/workspace';

export class UnsupportedWorkspaceRequestError extends Error {}

const BODY_TYPES: Partial<Record<RequestBodyTypeEnum, WorkspaceBodyType>> = {
    [RequestBodyTypeEnum.EMPTY]: 'empty',
    [RequestBodyTypeEnum.JSON]: 'json',
    [RequestBodyTypeEnum.PLAIN_TEXT]: 'plain-text',
};

const PAYLOAD_TYPES: Record<WorkspaceBodyType, RequestBodyTypeEnum> = {
    empty: RequestBodyTypeEnum.EMPTY,
    json: RequestBodyTypeEnum.JSON,
    'plain-text': RequestBodyTypeEnum.PLAIN_TEXT,
};

// Files cannot be written to a request file, so only text parameters are kept.
const toParameters = (parameters: ParameterContract[]): WorkspaceParameter[] =>
    parameters
        .filter(parameter => parameter.type === ParameterType.Text)
        .map(({ key, value, enabled }) => ({ key, value, enabled }));

const toAuthorization = (
    authorization: AuthorizationContract,
): WorkspaceAuthorization => {
    switch (authorization.type) {
        case AuthorizationType.Bearer:
        case AuthorizationType.Basic:
        case AuthorizationType.Impersonate:
        case AuthorizationType.Token:
            return { type: authorization.type, value: authorization.value };
        default:
            return { type: authorization.type };
    }
};

/**
 * Builds the payload that saves a pending request.
 * Variables are kept as they are written, they are never resolved.
 *
 * @throws UnsupportedWorkspaceRequestError when the body cannot be written to a file.
 */
export const toRequestFile = (
    request: PendingRequest,
    name: string,
): WorkspaceRequestPayload => {
    const bodyType = BODY_TYPES[request.payloadType];

    if (!bodyType) {
        throw new UnsupportedWorkspaceRequestError(
            'Form data bodies cannot be saved to the workspace yet.',
        );
    }

    const content = request.body[request.method]?.[request.payloadType];

    return {
        name,
        method: request.method,
        endpoint: request.endpoint,
        route: request.routeDefinition.metadata?.name ?? null,
        headers: toParameters(request.headers),
        query: toParameters(request.queryParameters),
        body:
            bodyType === 'empty'
                ? { type: 'empty' }
                : { type: bodyType, content: typeof content === 'string' ? content : '' },
        authorization: toAuthorization(request.authorization),
    };
};

/**
 * Gives the parts of a pending request that a request file holds.
 */
export const fromRequestFile = (
    file: WorkspaceRequestFile,
): Pick<
    PendingRequest,
    | 'method'
    | 'endpoint'
    | 'headers'
    | 'queryParameters'
    | 'payloadType'
    | 'authorization'
> & { body: string | null } => ({
    method: file.method.toUpperCase(),
    endpoint: file.endpoint,
    headers: file.headers.map(
        (header): ParameterContract => ({ ...header, type: ParameterType.Text }),
    ),
    queryParameters: file.query.map(
        (parameter): ParameterContract => ({ ...parameter, type: ParameterType.Text }),
    ),
    payloadType: PAYLOAD_TYPES[file.body.type],
    body: file.body.type === 'empty' ? null : (file.body.content ?? ''),
    authorization: file.authorization as AuthorizationContract,
});

/**
 * Serializes the payload of a request, to tell later whether it changed.
 */
export const serializeRequestFile = (payload: WorkspaceRequestPayload): string =>
    JSON.stringify(payload);

/**
 * Tells whether a tab linked to a request file changed since it was loaded or saved.
 */
export const isTabDirty = (tab: Tab): boolean => {
    if (!tab.workspace) {
        return false;
    }

    try {
        return (
            serializeRequestFile(toRequestFile(tab.request, tab.workspace.name)) !==
            tab.workspace.saved
        );
    } catch {
        return true;
    }
};
