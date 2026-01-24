import type { RouteExtractorException } from '@/interfaces';
import type { RouteDefinition, RoutesGroup } from '@/interfaces/routes/routes';
import type { JSONSchema7 } from 'json-schema';

/**
 * Source route configuration from window.Nimbus.routes
 * TODO [Refactor] move these to interfaces.
 */
export interface SourceRouteConfig {
    uri: string;
    shortUri: string;
    methods: string[];
    schema: JSONSchema7;
    extractionError: string | null;
}

export type SourceRouteConfigArray = {
    [key: string]: {
        // <- key = version
        [key: string]: SourceRouteConfig[]; // <- key = routes group api `resource`
    };
};

/**
 * Processes raw route data from window.Nimbus into structured route groups.
 *
 * Transforms the nested source route configuration into a clean structure
 * organized by version and resource, with proper sorting and error handling.
 */
export async function processRoutesData(sourceRoutes: SourceRouteConfigArray): Promise<{
    [key: string]: RoutesGroup[];
}> {
    const processedRoutes: { [key: string]: RoutesGroup[] } = {};

    Object.keys(sourceRoutes).forEach((version: string) => {
        const sourceRoutesInVersion = sourceRoutes[version];

        const routesInVersion: RoutesGroup[] = [];

        Object.keys(sourceRoutesInVersion).forEach((resource: string) => {
            const resourceRoutes = sourceRoutesInVersion[resource];

            routesInVersion.push({
                resource: resource,
                routes: resourceRoutes
                    .flatMap((route: SourceRouteConfig) => {
                        return route.methods.map(
                            // <- Each method becomes its own indivual route.
                            (method: string): RouteDefinition => ({
                                method: method,
                                endpoint: route.uri,
                                shortEndpoint: route.shortUri,
                                schema: {
                                    shape: route.schema,
                                    extractionErrors: route.extractionError,
                                },
                            }),
                        );
                    })
                    // Sort routes (inside a given `resource`) by endpoint.
                    .sort((a, b) => a.shortEndpoint.localeCompare(b.shortEndpoint)),
            });
        });

        // Sort routes by `resource`.
        processedRoutes[version] = routesInVersion.sort((a, b) =>
            a.resource.localeCompare(b.resource),
        );
    });

    return processedRoutes;
}

/**
 * Parses route extraction exception from window.Nimbus.
 *
 * Safely parses the route extraction exception JSON string,
 * returning null if parsing fails or data is invalid.
 */
export function parseRouteExtractionException(
    exceptionData: string | null,
): RouteExtractorException | null {
    if (typeof exceptionData !== 'string') {
        return null;
    }

    try {
        return JSON.parse(exceptionData);
    } catch {
        return null;
    }
}

/**
 * Searches routes across all versions and resources.
 *
 * Performs case-insensitive search across endpoint, method, and resource names,
 * returning structured results with version and resource context.
 */
export function searchRoutes(
    routes: { [key: string]: RoutesGroup[] } | null,
    query: string,
): Array<{
    version: string;
    resource: string;
    route: RouteDefinition;
}> {
    if (!routes || !query.trim()) {
        return [];
    }

    const results: Array<{
        version: string;
        resource: string;
        route: RouteDefinition;
    }> = [];

    const searchTerm = query.toLowerCase();

    Object.entries(routes).forEach(([version, versionRoutes]) => {
        versionRoutes.forEach(group => {
            group.routes.forEach(route => {
                if (
                    route.endpoint.toLowerCase().includes(searchTerm) ||
                    route.shortEndpoint.toLowerCase().includes(searchTerm) ||
                    route.method.toLowerCase().includes(searchTerm) ||
                    group.resource.toLowerCase().includes(searchTerm)
                ) {
                    results.push({
                        version,
                        resource: group.resource,
                        route,
                    });
                }
            });
        });
    });

    return results;
}

/**
 * Calculates total route count across all versions and resources.
 *
 * Provides efficient counting without creating intermediate arrays,
 * useful for statistics and UI indicators.
 */
export function calculateTotalRouteCount(
    routes: { [key: string]: RoutesGroup[] } | null,
): number {
    if (!routes) {
        return 0;
    }

    return Object.values(routes).reduce((total, versionRoutes) => {
        return (
            total +
            versionRoutes.reduce((versionTotal, group) => {
                return versionTotal + group.routes.length;
            }, 0)
        );
    }, 0);
}
