import type { JSONSchema7 } from 'json-schema';

export interface RouteMetadata {
    name?: string;
    operationId?: string;
    isMissingImplementation?: boolean;
    isUndocumented?: boolean;
    /** Resolved middleware class names. */
    middleware?: string[];
    /** Whether the route needs a signed in caller, only known middleware names count. */
    auth?: 'required' | 'hint' | 'none';
    /** Authorization middleware such as `can:update,post`. */
    authHints?: string[];
    /** Guards named by the route that the app does not define. */
    unknownGuards?: string[];
    /** The throttle limit as written, such as `60,1` or the name of a rate limiter. */
    throttle?: string;
    bindings?: RouteBinding[];
    /** Patterns by route parameter. */
    where?: Record<string, string>;
    [key: string]: unknown;
}

export interface RouteBinding {
    parameter: string;
    /** The model class, for example `App\Models\User`. */
    model: string;
    key: string | null;
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
