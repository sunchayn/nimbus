<script setup lang="ts">
/**
 * @component RouteExplorer
 * @description The main sidebar component for exploring and searching available API routes.
 */
import { AppButton } from '@/components/base/button';
import { AppScrollArea } from '@/components/base/scroll-area';
import ApplicationSwitcher from '@/components/domain/RoutesExplorer/ApplicationSwitcher.vue';
import RouteExplorerVersionSelector from '@/components/domain/RoutesExplorer/RouteExplorerVersionSelector.vue';
import RoutesList from '@/components/domain/RoutesExplorer/RoutesList/RoutesList.vue';
import { showingSearchResultsKey } from '@/components/domain/RoutesExplorer/showingSearchResults';
import RoutesListSkeleton from '@/components/layout/skeleton/RoutesListSkeleton.vue';
import { useTabVerticalScroll } from '@/composables/ui/useTabVerticalScroll';
import { type RoutesFolder } from '@/interfaces/routes/routes';
import { useConfigStore } from '@/stores/core/useConfigStore';
import {
    emptyRouteFilters,
    filterFolders,
    hasActiveRouteFilters,
    type RouteFilters,
    routeMatchesFilters,
} from '@/utils/routes';
import { computed, provide, useTemplateRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RouteExplorerProps {
    routes: { [_key in string]?: RoutesFolder[] } | null;
    loading?: boolean;
    error?: string | null;
    // The keyword and filters come from the toolbar above the explorer.
    search?: string;
    filters?: RouteFilters;
}

/*
 * Component Setup.
 */

const props = withDefaults(defineProps<RouteExplorerProps>(), {
    loading: false,
    error: null,
    search: '',
    filters: () => emptyRouteFilters(),
});

defineEmits<{
    retry: [];
}>();

const currentVersion = defineModel<string>('version', { default: '' });

const configStore = useConfigStore();

/*
 * State.
 */

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
 * Computed & Methods.
 */

const versions = computed(() => Object.keys(props.routes || {}));

const routesInVersion = computed(() => {
    if (props.routes === null) {
        return [];
    }

    return props.routes[currentVersion.value] ?? [];
});

const filtersAreActive = computed(() => hasActiveRouteFilters(props.filters));

const filteredRoutes = computed(() => {
    if (props.routes === null) {
        return [];
    }

    const searchTerm = props.search.trim().toLowerCase();

    if (!searchTerm && !filtersAreActive.value) {
        return routesInVersion.value;
    }

    return filterFolders(
        routesInVersion.value,
        route =>
            routeMatchesFilters(route, props.filters) &&
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

const showingSearchResults = computed(
    () => props.search.trim().length > 0 || filtersAreActive.value,
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
                        <div class="gap-tight flex w-full min-w-0 flex-col">
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
                        </div>
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
