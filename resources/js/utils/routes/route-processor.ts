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
    metadata: Record<string, unknown>;
    keywords: string[];
    prefix: string;
}

export type SourceRouteConfigArray = {
    [key: string]: {
        // <- key = version
        [key: string]: SourceRouteConfig[]; // <- key = routes group api `resource`
    };
};

/**
 * Converts a SourceRouteConfig into individual RouteDefinition entries (one per HTTP method).
 */
function sourceRouteToDefinitions(route: SourceRouteConfig): RouteDefinition[] {
    return route.methods.map(
        (method: string): RouteDefinition => ({
            method,
            endpoint: route.uri,
            shortEndpoint: route.shortUri,
            schema: {
                shape: route.schema,
                extractionErrors: route.extractionError,
            },
            metadata: route.metadata,
            keywords: route.keywords,
            prefix: route.prefix,
        }),
    );
}

/**
 * Processes raw route data from window.Nimbus into structured route groups.
 *
 * Transforms the nested source route configuration into a clean structure
 * organized by version and resource, with proper sorting and error handling.
 *
 * When routes come from multiple distinct prefixes, groups them hierarchically:
 * prefix → resource → routes. Otherwise, uses the flat resource → routes structure.
 */
export async function processRoutesData(sourceRoutes: SourceRouteConfigArray): Promise<{
    [key: string]: RoutesGroup[];
}> {
    const processedRoutes: { [key: string]: RoutesGroup[] } = {};

    Object.keys(sourceRoutes).forEach((version: string) => {
        const sourceRoutesInVersion = sourceRoutes[version];

        // Collect all distinct prefixes across all routes in this version
        const allPrefixes = new Set<string>();
        Object.values(sourceRoutesInVersion).forEach(routes => {
            routes.forEach(route => {
                if (route.prefix) {
                    allPrefixes.add(route.prefix);
                }
            });
        });

        const hasMultiplePrefixes = allPrefixes.size > 1;

        if (hasMultiplePrefixes) {
            processedRoutes[version] = buildPrefixGroupedRoutes(sourceRoutesInVersion);
        } else {
            processedRoutes[version] = buildFlatRoutes(sourceRoutesInVersion);
        }
    });

    return processedRoutes;
}

/**
 * Builds a flat resource → routes structure (single prefix or no prefix).
 */
function buildFlatRoutes(
    sourceRoutesInVersion: { [key: string]: SourceRouteConfig[] },
): RoutesGroup[] {
    const routesInVersion: RoutesGroup[] = [];

    Object.keys(sourceRoutesInVersion).forEach((resource: string) => {
        const resourceRoutes = sourceRoutesInVersion[resource];

        routesInVersion.push({
            resource: resource,
            routes: resourceRoutes
                .flatMap(sourceRouteToDefinitions)
                .sort((a, b) => a.shortEndpoint.localeCompare(b.shortEndpoint)),
        });
    });

    return routesInVersion.sort((a, b) => a.resource.localeCompare(b.resource));
}

/**
 * Builds a prefix → resource → routes hierarchical structure (multiple prefixes).
 */
function buildPrefixGroupedRoutes(
    sourceRoutesInVersion: { [key: string]: SourceRouteConfig[] },
): RoutesGroup[] {
    // Group routes by prefix, then by resource within each prefix
    const prefixMap = new Map<string, Map<string, RouteDefinition[]>>();

    Object.entries(sourceRoutesInVersion).forEach(([resource, resourceRoutes]) => {
        resourceRoutes.forEach((route: SourceRouteConfig) => {
            const prefix = route.prefix || '';

            if (!prefixMap.has(prefix)) {
                prefixMap.set(prefix, new Map());
            }

            const resourceMap = prefixMap.get(prefix)!;

            if (!resourceMap.has(resource)) {
                resourceMap.set(resource, []);
            }

            resourceMap.get(resource)!.push(...sourceRouteToDefinitions(route));
        });
    });

    // Convert to RoutesGroup[] with prefix nesting
    const prefixGroups: RoutesGroup[] = [];

    Array.from(prefixMap.keys())
        .sort()
        .forEach(prefix => {
            const resourceMap = prefixMap.get(prefix)!;
            const children: RoutesGroup[] = [];

            Array.from(resourceMap.keys())
                .sort()
                .forEach(resource => {
                    children.push({
                        resource,
                        routes: resourceMap
                            .get(resource)!
                            .sort((a, b) => a.shortEndpoint.localeCompare(b.shortEndpoint)),
                    });
                });

            prefixGroups.push({
                resource: prefix || '(no prefix)',
                routes: [],
                prefix,
                children,
            });
        });

    return prefixGroups;
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
 * Handles both flat and prefix-grouped route structures.
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
            const groupsToSearch = group.children ?? [group];

            groupsToSearch.forEach(innerGroup => {
                innerGroup.routes.forEach(route => {
                    if (
                        route.endpoint.toLowerCase().includes(searchTerm) ||
                        route.shortEndpoint.toLowerCase().includes(searchTerm) ||
                        route.method.toLowerCase().includes(searchTerm) ||
                        innerGroup.resource.toLowerCase().includes(searchTerm) ||
                        (group.prefix && group.prefix.toLowerCase().includes(searchTerm))
                    ) {
                        results.push({
                            version,
                            resource: innerGroup.resource,
                            route,
                        });
                    }
                });
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
 * Handles both flat and prefix-grouped route structures.
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
                if (group.children) {
                    return (
                        versionTotal +
                        group.children.reduce(
                            (childTotal, child) => childTotal + child.routes.length,
                            0,
                        )
                    );
                }

                return versionTotal + group.routes.length;
            }, 0)
        );
    }, 0);
}
