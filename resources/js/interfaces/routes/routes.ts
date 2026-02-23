import type { JSONSchema7 } from 'json-schema';

export type RouteDefinition = {
    endpoint: string;
    method: string;
    schema: {
        shape: JSONSchema7;
        extractionErrors: string | null;
    };
    shortEndpoint: string;
    metadata?: Record<string, unknown>;
    keywords?: string[];
    prefix?: string;
};

/**
 * Metadata for a single route from OpenAPI strategy.
 */
export interface RouteDetectionMetadata {
    isPrimarySourceRouteImplementationMissing: boolean;
    isRouteMissingFromPrimarySource: boolean;
}

export interface RoutesGroup {
    /** The resource name that groups these routes (or prefix name for prefix-level groups) */
    resource: string;

    /** Array of route definitions belonging to this group */
    routes: Array<RouteDefinition>;

    /** Present when this is a prefix-level group */
    prefix?: string;

    /** Child resource groups within this prefix group */
    children?: Array<RoutesGroup>;
}
