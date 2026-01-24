import type { RouteDefinition } from '@/interfaces/routes/routes';
import { useRoutesStore } from '@/stores';
import type { JSONSchema7 } from 'json-schema';
import type { ComputedRef } from 'vue';
import { computed } from 'vue';

export interface RouteStatistics {
    total: number;
    withErrors: number;
    withoutErrors: number;
    errorRate: number;
}

export interface RouteWithError {
    endpoint: string;
    method: string;
    resource: string;
    version: string;
    schema: {
        shape: JSONSchema7;
        extractionErrors: string;
    };
}

export function useRouteStatistics(): {
    routeStatistics: ComputedRef<RouteStatistics>;
    displayableRoutesWithErrors: ComputedRef<RouteWithError[]>;
} {
    const routesStore = useRoutesStore();

    /**
     * Creates a RouteWithError object from a route definition
     */
    const createRouteWithError = (
        route: RouteDefinition,
        resource: string,
        version: string,
    ): RouteWithError => ({
        ...route,
        resource,
        version,
        schema: {
            ...route.schema,
            extractionErrors: route.schema.extractionErrors ?? '',
        },
    });

    /**
     * Checks if a route has extraction errors
     */
    const hasExtractionErrors = (route: RouteDefinition): boolean => {
        return Boolean(route.schema.extractionErrors);
    };

    /**
     * Flattens all routes from all versions and groups
     */
    const getAllRoutes = (): RouteDefinition[] => {
        if (!routesStore.routes) {
            return [];
        }

        return Object.values(routesStore.routes)
            .flat()
            .flatMap(group => group.routes);
    };

    /**
     * Calculates route statistics from all routes across all versions.
     */
    const routeStatistics: ComputedRef<RouteStatistics> = computed(
        (): RouteStatistics => {
            const allRoutes = getAllRoutes();

            const total = allRoutes.length;
            const withErrors = allRoutes.filter(hasExtractionErrors).length;
            const withoutErrors = total - withErrors;
            const errorRate = total === 0 ? 0 : Math.round((withErrors / total) * 100);

            return { total, withErrors, withoutErrors, errorRate };
        },
    );

    /**
     * Gets all routes with errors across all versions.
     * Map the list into a displayable entity that can be rendered on the page.
     */
    const displayableRoutesWithErrors: ComputedRef<RouteWithError[]> = computed(
        (): RouteWithError[] => {
            if (!routesStore.routes) {
                return [];
            }

            return Object.entries(routesStore.routes).flatMap(([version, groups]) =>
                groups.flatMap(group =>
                    group.routes
                        .filter(hasExtractionErrors)
                        .map(route =>
                            createRouteWithError(route, group.resource, version),
                        ),
                ),
            );
        },
    );

    return {
        routeStatistics,
        displayableRoutesWithErrors,
    };
}
