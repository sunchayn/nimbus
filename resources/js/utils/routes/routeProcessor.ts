import type { RouteExtractorException } from '@/interfaces';
import type { RouteDefinition, RoutesFolder } from '@/interfaces/routes/routes';
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
}

/**
 * A folder as sent by the backend, with its routes and nested folders.
 */
export interface SourceFolder {
    name: string;
    path: string;
    routes: SourceRouteConfig[];
    children: SourceFolder[];
}

export type SourceRouteConfigArray = {
    [key: string]: SourceFolder[]; // <- key = version
};

const processFolder = (folder: SourceFolder): RoutesFolder => ({
    name: folder.name,
    path: folder.path,
    routes: folder.routes
        .flatMap((route: SourceRouteConfig) => {
            return route.methods.map(
                // <- Each method becomes its own individual route.
                (method: string): RouteDefinition => ({
                    method: method,
                    endpoint: route.uri,
                    shortEndpoint: route.shortUri,
                    schema: {
                        shape: route.schema,
                        extractionErrors: route.extractionError,
                    },
                    metadata: route.metadata,
                    keywords: route.keywords,
                }),
            );
        })
        // Sort routes (inside a given folder) by endpoint.
        .sort((a, b) => a.shortEndpoint.localeCompare(b.shortEndpoint)),
    children: folder.children.map(processFolder),
});

/**
 * Lists a folder tree as a flat list, parents before their children.
 */
const flattenFolders = (folders: RoutesFolder[]): RoutesFolder[] =>
    folders.flatMap(folder => [folder, ...flattenFolders(folder.children)]);

/**
 * Processes raw route data from window.Nimbus into structured route folders.
 *
 * Transforms the source route configuration into a clean structure
 * organized by version and folder, with proper sorting and error handling.
 */
export async function processRoutesData(sourceRoutes: SourceRouteConfigArray): Promise<{
    [key: string]: RoutesFolder[];
}> {
    const processedRoutes: { [key: string]: RoutesFolder[] } = {};

    Object.keys(sourceRoutes).forEach((version: string) => {
        // Folders within a version pertain the order sent by the backend.
        processedRoutes[version] = sourceRoutes[version].map(processFolder);
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
    routes: { [key: string]: RoutesFolder[] } | null,
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
        flattenFolders(versionRoutes).forEach(folder => {
            folder.routes.forEach(route => {
                if (
                    route.endpoint.toLowerCase().includes(searchTerm) ||
                    route.shortEndpoint.toLowerCase().includes(searchTerm) ||
                    route.method.toLowerCase().includes(searchTerm) ||
                    folder.path.toLowerCase().includes(searchTerm)
                ) {
                    results.push({
                        version,
                        resource: folder.path,
                        route,
                    });
                }
            });
        });
    });

    return results;
}

/**
 * Calculates total route count across all versions and folders,
 * useful for statistics and UI indicators.
 */
export function calculateTotalRouteCount(
    routes: { [key: string]: RoutesFolder[] } | null,
): number {
    if (!routes) {
        return 0;
    }

    return Object.values(routes).reduce((total, versionRoutes) => {
        return (
            total +
            flattenFolders(versionRoutes).reduce(
                (versionTotal, folder) => versionTotal + folder.routes.length,
                0,
            )
        );
    }, 0);
}
