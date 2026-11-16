import type { AuthorizationContract } from '@/interfaces/auth/authorization';
import type { ExplorerNode } from '@/interfaces/explorer';
import type { RequestBodyTypeEnum } from '@/interfaces/http';
import type { ParameterContract } from '@/interfaces/ui';

/** The body types a request file can hold. */
export type WorkspaceBodyType = `${
    | RequestBodyTypeEnum.EMPTY
    | RequestBodyTypeEnum.JSON
    | RequestBodyTypeEnum.PLAIN_TEXT}`;

/** A header or query parameter without the fields that only the interface needs. */
export type WorkspaceParameter = Pick<ParameterContract, 'key' | 'value' | 'enabled'>;

/** The authorization a request file holds, which is its type and value. */
export type WorkspaceAuthorization = Pick<AuthorizationContract, 'type' | 'value'>;

/**
 * The request as the backend validates it on write.
 */
export interface WorkspaceRequestPayload {
    name: string;
    method: string;
    endpoint: string;
    route: string | null;
    headers: WorkspaceParameter[];
    query: WorkspaceParameter[];
    body: { type: WorkspaceBodyType; content?: string };
    authorization: WorkspaceAuthorization;
}

/**
 * The request as it is stored in a request file.
 */
export interface WorkspaceRequestFile extends Omit<WorkspaceRequestPayload, 'route'> {
    id: string;
    route?: string;
}

export interface WorkspaceSummary {
    id: string;
    name: string;
    method: string;
    endpoint: string;
    route: string | null;
    file: string;
    /** The folder path inside the workspace, empty for the root. */
    folder: string;
    hash: string;
}

export interface WorkspaceListing {
    folders: string[];
    requests: WorkspaceSummary[];
}

export interface StoredWorkspaceRequest {
    request: WorkspaceRequestFile;
    hash: string;
}

/**
 * Links a tab to its request file.
 */
export interface WorkspaceLink {
    id: string;
    name: string;
    /** The hash of the file as last loaded or saved. */
    hash: string;
    /** The saved request serialized, to tell when the tab changed. */
    saved: string;
}

/** A folder of the workspace tree. */
export type WorkspaceFolderNode = ExplorerNode<WorkspaceSummary>;
