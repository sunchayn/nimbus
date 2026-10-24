<script setup lang="ts">
/**
 * @component RouteExplorer
 * @description The main sidebar component for exploring and searching available API routes.
 */
import { AppScrollArea } from '@/components/base/scroll-area';
import GeneratorInput from '@/components/common/GeneratorInput.vue';
import ApplicationSwitcher from '@/components/domain/RoutesExplorer/ApplicationSwitcher.vue';
import RouteExplorerVersionSelector from '@/components/domain/RoutesExplorer/RouteExplorerVersionSelector.vue';
import RoutesList from '@/components/domain/RoutesExplorer/RoutesList/RoutesList.vue';
import { showingSearchResultsKey } from '@/components/domain/RoutesExplorer/showingSearchResults';
import { useTabVerticalScroll } from '@/composables/ui/useTabVerticalScroll';
import { type RouteDefinition, type RoutesGroup } from '@/interfaces/routes/routes';
import { useConfigStore } from '@/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { computed, provide, shallowRef, useTemplateRef, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RouteExplorerProps {
    routes: { [_key in string]?: RoutesGroup[] } | null;
}

/*
 * Component Setup.
 */

const props = defineProps<RouteExplorerProps>();

const configStore = useConfigStore();

/*
 * State.
 */

const search = useStorage(singletonPersistenceKey('routes-explorer-search-keyword'), '');
const currentVersion = shallowRef('');

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

const filteredRoutes = computed(() => {
    if (props.routes === null) {
        return [];
    }

    if (!search.value.trim()) {
        return routesInVersion.value;
    }

    const searchTerm = search.value.toLowerCase();

    return (
        routesInVersion.value
            .map((group: RoutesGroup) => {
                const filtered = group.routes.filter((route: RouteDefinition) => {
                    return route.keywords?.some((keyword: string) =>
                        keyword.toLowerCase().includes(searchTerm),
                    );
                });

                return filtered.length > 0 ? { ...group, routes: filtered } : null;
            })
            .filter((group): group is RoutesGroup => group !== null) || []
    );
});

const hasMultipleApplications = computed(
    () => Object.keys(configStore.applications).length > 1,
);

const showingSearchResults = computed(() => search.value.trim().length > 0);

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
        <div class="pb-tight flex shrink-0 items-center gap-1">
            <GeneratorInput
                v-model="search"
                size="md"
                variant="outline"
                leading-icon="i-hugeicons-search-01"
                placeholder="Type to search..."
                :disabled="routesInVersion.length === 0"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-h-0 flex-1 flex-col gap-2 overflow-hidden">
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
                        class="from-muted z-sticky h-fade pointer-events-none absolute top-0 right-0 left-0 bg-gradient-to-b from-20% to-transparent transition-opacity duration-300"
                    />
                    <AppScrollArea
                        ref="scrollAreaRef"
                        class="h-full"
                        @scroll="updateRoutesScrollMasks"
                    >
                        <ul class="flex w-full min-w-0 flex-col gap-1">
                            <RoutesList v-if="routes !== null" :routes="filteredRoutes" />
                            <div v-else class="px-2">
                                <p class="mb-2 text-xs">
                                    Routes extraction was Interrupted, check the error on
                                    the page.
                                </p>
                            </div>
                        </ul>
                    </AppScrollArea>
                    <div
                        v-show="showRoutesBottomMask"
                        class="from-muted z-sticky h-fade pointer-events-none absolute right-0 bottom-0 left-0 bg-gradient-to-t from-20% to-transparent transition-opacity duration-300"
                    />
                </div>
            </div>
        </div>
        <div
            v-if="hasMultipleApplications || (configStore.isVersioned && versions.length)"
            class="flex shrink-0 items-center gap-1 pt-2"
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
