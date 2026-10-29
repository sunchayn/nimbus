import type { RouteExtractorException } from '@/interfaces';
import type { RouteDefinition, RoutesFolder } from '@/interfaces/routes/routes';
import { useErrorStore } from '@/stores/core/useErrorStore';
import {
    calculateTotalRouteCount,
    processRoutesData,
    searchRoutes,
} from '@/utils/routes';
import { defineStore } from 'pinia';
import { computed, ref, shallowRef } from 'vue';

export const useRoutesStore = defineStore('routes', () => {
    /*
     * State.
     */

    const routes = ref<{ [key: string]: RoutesFolder[] } | null>(null);
    const routeExtractorException = ref<RouteExtractorException | null>(null);
    // The routes load in a separate request, so the store starts out loading.
    const isLoading = shallowRef(true);
    const error = shallowRef<string | null>(null);

    /*
     * Actions.
     */

    /**
     * Fetches the routes of the active application.
     * Extraction errors from the backend land in the error stores, other failures in `error`.
     */
    const initializeRoutes = async () => {
        isLoading.value = true;
        error.value = null;
        routeExtractorException.value = null;

        try {
            const response = await fetch(`${window.Nimbus?.basePath ?? ''}/api/routes`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const body = await response.json();

            if (response.ok) {
                routes.value = await processRoutesData(body);

                return;
            }

            routes.value = null;

            if (body.routeExtractorException) {
                routeExtractorException.value = body.routeExtractorException;
            } else if (body.globalException) {
                useErrorStore().globalError = body.globalException;
            } else {
                error.value = `Failed to load routes (${response.status})`;
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to load routes';

            routes.value = null;
        } finally {
            isLoading.value = false;
        }
    };

    const isMissingImplementation = (route: RouteDefinition) => {
        return route.metadata?.isMissingImplementation ?? false;
    };

    const isUndocumented = (route: RouteDefinition) => {
        return route.metadata?.isUndocumented ?? false;
    };

    const resetRoutesState = () => {
        routes.value = null;
        routeExtractorException.value = null;
        error.value = null;
    };

    /*
     * Computed properties.
     */

    const hasRoutes = computed(
        () => routes.value !== null && Object.keys(routes.value).length > 0,
    );

    const hasExtractionError = computed(() => routeExtractorException.value !== null);

    const hasAnyError = computed(() => hasExtractionError.value);

    const routeVersions = computed(() => (routes.value ? Object.keys(routes.value) : []));

    const totalRouteCount = computed(() => {
        return calculateTotalRouteCount(routes.value);
    });

    const getRoutesByVersion = computed(() => (version: string) => {
        return routes.value?.[version] || [];
    });

    const searchRoutesComputed = computed(() => (query: string) => {
        return searchRoutes(routes.value, query);
    });

    return {
        // State
        routes,
        routeExtractorException,
        isLoading,
        error,

        // Actions
        initializeRoutes,
        resetRoutesState,
        isMissingImplementation,
        isUndocumented,

        // Computed
        hasRoutes,
        hasExtractionError,
        hasAnyError,
        routeVersions,
        totalRouteCount,
        getRoutesByVersion,
        searchRoutes: searchRoutesComputed,
    };
});
