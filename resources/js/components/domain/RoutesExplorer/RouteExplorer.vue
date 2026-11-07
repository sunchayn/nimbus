<script setup lang="ts">
/**
 * @component RouteExplorer
 * @description The main sidebar component for exploring and searching available API routes.
 */
import { AppButton } from '@/components/base/button';
import { AppScrollArea } from '@/components/base/scroll-area';
import GeneratorInput from '@/components/common/GeneratorInput.vue';
import ApplicationSwitcher from '@/components/domain/RoutesExplorer/ApplicationSwitcher.vue';
import RouteExplorerVersionSelector from '@/components/domain/RoutesExplorer/RouteExplorerVersionSelector.vue';
import RoutesFilterDropdown from '@/components/domain/RoutesExplorer/RoutesFilterDropdown.vue';
import RoutesList from '@/components/domain/RoutesExplorer/RoutesList/RoutesList.vue';
import { showingSearchResultsKey } from '@/components/domain/RoutesExplorer/showingSearchResults';
import RoutesListSkeleton from '@/components/layout/skeleton/RoutesListSkeleton.vue';
import { useTabVerticalScroll } from '@/composables/ui/useTabVerticalScroll';
import { type RoutesFolder } from '@/interfaces/routes/routes';
import { useConfigStore } from '@/stores';
import {
    collectMethods,
    collectMiddleware,
    collectRoutes,
    emptyRouteFilters,
    filterFolders,
    hasActiveRouteFilters,
    routeMatchesFilters,
} from '@/utils/routes';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { computed, provide, ref, shallowRef, useTemplateRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RouteExplorerProps {
    routes: { [_key in string]?: RoutesFolder[] } | null;
    loading?: boolean;
    error?: string | null;
    // Unix seconds of the routes snapshot, the refresh button is hidden without it.
    snapshotAt?: number | null;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<RouteExplorerProps>(), {
    loading: false,
    error: null,
    snapshotAt: null,
});

defineEmits<{
    retry: [];
    refresh: [];
}>();

const configStore = useConfigStore();

/*
 * State.
 */

const search = useStorage(singletonPersistenceKey('routes-explorer-search-keyword'), '');
const currentVersion = shallowRef('');
const filters = ref(emptyRouteFilters());

const scrollAreaRef = useTemplateRef<InstanceType<typeof AppScrollArea>>('scrollAreaRef');

const {
    scrollContainer: routesScrollContainer,
    showTopMask: showRoutesTopMask,
    showBottomMask: showRoutesBottomMask,
    updateScrollMasks: updateRoutesScrollMasks,
} = useTabVerticalScroll({
    MASK_HEIGHT: 24,
});

/*
 * Watchers.
 */

const versions = computed(() => Object.keys(props.routes || {}));

// Initialize or update current version when versions list changes (e.g. after project switch)
watch(
    versions,
    newVersions => {
        if (newVersions.length > 0 && !newVersions.includes(currentVersion.value)) {
            currentVersion.value = newVersions[0];
        } else if (newVersions.length === 0) {
            currentVersion.value = '';
        }
    },
    { immediate: true },
);

/*
 * Computed & Methods.
 */

const routesInVersion = computed(() => {
    if (props.routes === null) {
        return [];
    }

    return props.routes[currentVersion.value] ?? [];
});

const allRoutes = computed(() => collectRoutes(routesInVersion.value));
const availableMethods = computed(() => collectMethods(allRoutes.value));
const availableMiddleware = computed(() => collectMiddleware(allRoutes.value));
const filtersAreActive = computed(() => hasActiveRouteFilters(filters.value));

const filteredRoutes = computed(() => {
    if (props.routes === null) {
        return [];
    }

    const searchTerm = search.value.trim().toLowerCase();

    if (!searchTerm && !filtersAreActive.value) {
        return routesInVersion.value;
    }

    return filterFolders(
        routesInVersion.value,
        route =>
            routeMatchesFilters(route, filters.value) &&
            (!searchTerm ||
                (route.keywords?.some((keyword: string) =>
                    keyword.toLowerCase().includes(searchTerm),
                ) ??
                    false)),
    );
});

const hasMultipleApplications = computed(
    () => Object.keys(configStore.applications).length > 1,
);

const snapshotTitle = computed(() =>
    props.snapshotAt
        ? `Refresh routes. Last updated ${new Date(props.snapshotAt * 1000).toLocaleString()}.`
        : 'Refresh routes',
);

const showingSearchResults = computed(
    () => search.value.trim().length > 0 || filtersAreActive.value,
);

watch(
    () => scrollAreaRef.value?.viewport,
    viewport => {
        if (viewport) {
            routesScrollContainer.value = viewport;
        }
    },
    { immediate: true },
);

provide(showingSearchResultsKey, showingSearchResults);
</script>

<template>
    <div class="text-default flex h-full max-h-screen w-full flex-col">
        <div class="pb-tight gap-tight flex shrink-0 items-center">
            <GeneratorInput
                v-model="search"
                size="md"
                variant="outline"
                leading-icon="i-hugeicons-search-01"
                placeholder="Type to search..."
                :disabled="routesInVersion.length === 0"
                class="min-w-0 flex-1"
            />
            <RoutesFilterDropdown
                v-model="filters"
                :routes="allRoutes"
                :methods="availableMethods"
                :middleware="availableMiddleware"
                :disabled="routesInVersion.length === 0"
            />
            <AppButton
                v-if="snapshotAt"
                icon="i-hugeicons-reload"
                color="neutral"
                variant="outline"
                size="md"
                :loading="loading"
                :title="snapshotTitle"
                aria-label="Refresh routes"
                data-testid="routes-refresh"
                @click="$emit('refresh')"
            />
        </div>
        <div class="gap-base flex min-h-0 flex-1 flex-col overflow-hidden">
            <div
                data-testid="routes-panel"
                class="relative flex h-full w-full min-w-0 flex-col"
            >
                <div
                    class="relative min-h-0 w-full flex-1 overflow-hidden text-sm"
                    :class="{
                        'border-default border-t': showRoutesTopMask,
                    }"
                >
                    <div
                        v-show="showRoutesTopMask"
                        class="from-muted z-sticky h-fade duration-slow pointer-events-none absolute top-0 right-0 left-0 bg-gradient-to-b from-20% to-transparent transition-opacity"
                    />
                    <AppScrollArea
                        ref="scrollAreaRef"
                        class="h-full"
                        @scroll="updateRoutesScrollMasks"
                    >
                        <ul class="gap-tight flex w-full min-w-0 flex-col">
                            <RoutesListSkeleton v-if="loading" />
                            <RoutesList
                                v-else-if="routes !== null"
                                :routes="filteredRoutes"
                            />
                            <div
                                v-else-if="error"
                                class="px-base"
                                data-testid="routes-error"
                            >
                                <p class="mb-base text-xs">
                                    The routes could not be loaded.
                                </p>
                                <AppButton
                                    label="Retry"
                                    color="neutral"
                                    variant="outline"
                                    size="sm"
                                    data-testid="routes-retry"
                                    @click="$emit('retry')"
                                />
                            </div>
                            <div v-else class="px-base">
                                <p class="mb-base text-xs">
                                    Routes extraction was Interrupted, check the error on
                                    the page.
                                </p>
                            </div>
                        </ul>
                    </AppScrollArea>
                    <div
                        v-show="showRoutesBottomMask"
                        class="from-muted z-sticky h-fade duration-slow pointer-events-none absolute right-0 bottom-0 left-0 bg-gradient-to-t from-20% to-transparent transition-opacity"
                    />
                </div>
            </div>
        </div>
        <div
            v-if="hasMultipleApplications || (configStore.isVersioned && versions.length)"
            class="gap-tight pt-base flex shrink-0 items-center"
        >
            <ApplicationSwitcher v-if="hasMultipleApplications" class="min-w-0 flex-1" />
            <div
                v-if="configStore.isVersioned && versions.length"
                class="shrink-0"
                :class="hasMultipleApplications ? 'w-20' : 'min-w-0 flex-1'"
            >
                <RouteExplorerVersionSelector
                    v-model="currentVersion"
                    :versions="versions"
                />
            </div>
        </div>
    </div>
</template>
