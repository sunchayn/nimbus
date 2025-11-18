import { RouteExtractorException } from '@/interfaces';
import { RoutesGroup } from '@/interfaces/routes/routes';
import {
    calculateTotalRouteCount,
    parseRouteExtractionException,
    processRoutesData,
    searchRoutes,
} from '@/utils/routes';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';

export const useRoutesStore = defineStore('routes', () => {
    /*
     * State.
     */

    const routes = ref<{ [key: string]: RoutesGroup[] } | null>(null);
    const routeExtractorException = ref<RouteExtractorException | null>(null);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    /*
     * Actions.
     */

    const fetchAvailableRoutes = async () => {
        isLoading.value = true;
        error.value = null;

        try {
            const source = window.Nimbus?.routes ?? '[]';

            if (typeof source !== 'string') {
                routes.value = null;

                return;
            }

            const sourceRoutes = JSON.parse(source);

            routes.value = await processRoutesData(sourceRoutes);
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to load routes';

            routes.value = null;
        } finally {
            isLoading.value = false;
        }
    };

    const initializeRoutes = async () => {
        routeExtractorException.value = parseRouteExtractionException(
            window.Nimbus?.routeExtractorException as string ?? null,
        );

        await fetchAvailableRoutes();
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
        fetchAvailableRoutes,
        initializeRoutes,
        resetRoutesState,

        // Computed
        hasRoutes,
        hasExtractionError,
        routeVersions,
        totalRouteCount,
        getRoutesByVersion,
        searchRoutes: searchRoutesComputed,
    };
});
