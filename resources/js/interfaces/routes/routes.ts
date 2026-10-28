import type { JSONSchema7 } from 'json-schema';

export interface RouteMetadata {
    name?: string;
    operationId?: string;
    isMissingImplementation?: boolean;
    isUndocumented?: boolean;
    [key: string]: unknown;
}

export type RouteDefinition = {
    endpoint: string;
    method: string;
    schema: {
        shape: JSONSchema7;
        extractionErrors: string | null;
    };
    shortEndpoint: string;
    metadata?: RouteMetadata;
    keywords?: string[];
};

/**
 * Metadata for a single route from OpenAPI strategy.
 */
export interface RouteDetectionMetadata {
    isPrimarySourceRouteImplementationMissing: boolean;
    isRouteMissingFromPrimarySource: boolean;
}

export interface RoutesFolder {
    /** The folder label (e.g. "password"). */
    name: string;

    /** The full folder path (e.g. "creator/auth/password"). */
    path: string;

    /** Route definitions directly in this folder. */
    routes: Array<RouteDefinition>;

    /** Nested folders. */
    children: Array<RoutesFolder>;
}
