<script setup lang="ts">
/**
 * @component RouteExplorer
 * @description The main sidebar component for exploring and searching available API routes.
 */
import {
    AppResizableHandle,
    AppResizablePanel,
    AppResizablePanelGroup,
} from '@/components/base/resizable';
import {
    AppSidebar,
    AppSidebarContent,
    AppSidebarGroup,
    AppSidebarGroupContent,
    AppSidebarGroupLabel,
    AppSidebarInput,
    AppSidebarMenu,
} from '@/components/base/sidebar';
import ApplicationSwitcher from '@/components/domain/RoutesExplorer/ApplicationSwitcher.vue';
import OpenTabs from '@/components/domain/RoutesExplorer/OpenTabs.vue';
import RouteExplorerHeader from '@/components/domain/RoutesExplorer/RouteExplorerHeader.vue';
import RouteExplorerVersionSelector from '@/components/domain/RoutesExplorer/RouteExplorerVersionSelector.vue';
import RoutesList from '@/components/domain/RoutesExplorer/RoutesList/RoutesList.vue';
import { useTabVerticalScroll } from '@/composables/ui/useTabVerticalScroll';
import { type RouteDefinition, type RoutesGroup } from '@/interfaces/routes/routes';
import { useConfigStore, useTabsStore } from '@/stores';
import { uniquePersistenceKey } from '@/utils/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { computed, provide, ref, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRouteExplorerProps {
    routes: { [_key in string]?: RoutesGroup[] } | null;
}

/*
 * Component Setup.
 */

const props = defineProps<AppRouteExplorerProps>();

const configStore = useConfigStore();
const tabsStore = useTabsStore();

/*
 * State.
 */

const search = useStorage(singletonPersistenceKey('routes-explorer-search-keyword'), '');
const currentVersion = ref('');

const openTabsPanel = ref<InstanceType<typeof AppResizablePanel> | null>(null);
const isOpenTabsExpanded = useStorage(
    uniquePersistenceKey(`routes-explorer-open-tabs-expanded`),
    false,
);

const {
    scrollContainer: routesScrollContainer,
    showTopMask: showRoutesTopMask,
    showBottomMask: showRoutesBottomMask,
    updateScrollMasks: updateRoutesScrollMasks,
} = useTabVerticalScroll({
    MASK_HEIGHT: 32,
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

    const keyword = search.value.toLowerCase();

    return (
        routesInVersion.value
            .map((group: RoutesGroup) => {
                const filtered = group.routes.filter(
                    (route: RouteDefinition) =>
                        route.endpoint.toLowerCase().includes(keyword) ||
                        route.shortEndpoint.toLowerCase().includes(keyword),
                );

                return filtered.length > 0 ? { ...group, routes: filtered } : null;
            })
            .filter((group): group is RoutesGroup => group !== null) || []
    );
});

const hasMultipleApplications = computed(
    () => Object.keys(configStore.applications).length > 1,
);

const showingSearchResults = computed(() => search.value.trim().length > 0);

const handlePanelCollapse = () => {
    isOpenTabsExpanded.value = false;
};

const handlePanelExpand = () => {
    isOpenTabsExpanded.value = true;
};

watch(isOpenTabsExpanded, newValue => {
    if (newValue) {
        openTabsPanel.value?.expand();
    } else {
        openTabsPanel.value?.collapse();
    }
});

provide('showingSearchResults', showingSearchResults);
</script>

<template>
    <AppSidebar collapsible="none" class="flex h-full max-h-screen w-full">
        <RouteExplorerHeader />
        <div>
            <AppSidebarInput
                v-model="search"
                variant="toolbar"
                placeholder="Type to search..."
                :disabled="routesInVersion.length === 0"
                class="h-[calc(var(--toolbar-height)+1px)] w-full border-b text-xs"
            />
            <div class="h-sub-toolbar flex items-center overflow-hidden border-b">
                <ApplicationSwitcher v-if="hasMultipleApplications" class="flex-1" />
                <div
                    v-if="configStore.isVersioned && versions.length"
                    class="h-full w-[80px] shrink-0 border-l"
                    :class="{
                        'flex-1': !hasMultipleApplications,
                    }"
                >
                    <RouteExplorerVersionSelector
                        v-model="currentVersion"
                        :versions="versions"
                    />
                </div>
            </div>
        </div>
        <AppSidebarContent class="overflow-hidden">
            <AppResizablePanelGroup
                :key="tabsStore.tabs.length > 0 ? 'with-tabs' : 'no-tabs'"
                auto-save-id="route-explorer"
                direction="vertical"
            >
                <template v-if="tabsStore.tabs.length > 0">
                    <AppResizablePanel
                        ref="openTabsPanel"
                        :order="1"
                        :min-size="20"
                        :default-size="20"
                        :collapsed-size="0"
                        :collapsible="true"
                        :collapsed="!isOpenTabsExpanded"
                        class="min-h-[32px]"
                        @collapse="handlePanelCollapse"
                        @expand="handlePanelExpand"
                    >
                        <OpenTabs v-model:is-open="isOpenTabsExpanded" />
                    </AppResizablePanel>
                    <AppResizableHandle />
                </template>
                <AppResizablePanel :order="2" :min-size="50" :default-size="100">
                    <AppSidebarGroup class="flex h-full flex-col p-0">
                        <AppSidebarGroupLabel>Routes</AppSidebarGroupLabel>
                        <AppSidebarGroupContent
                            class="relative min-h-0 flex-1 overflow-hidden"
                        >
                            <div
                                ref="routesScrollContainer"
                                class="h-full overflow-y-auto"
                                @scroll="updateRoutesScrollMasks"
                            >
                                <div
                                    v-show="showRoutesTopMask"
                                    class="from-sidebar pointer-events-none absolute top-0 right-0 left-0 z-10 h-8 bg-gradient-to-b from-20% to-transparent transition-opacity duration-300"
                                />
                                <AppSidebarMenu>
                                    <RoutesList
                                        v-if="routes !== null"
                                        :routes="filteredRoutes"
                                    />
                                    <div v-else class="px-2">
                                        <p class="mb-2 text-xs">
                                            Routes extraction was Interrupted, check the
                                            error on the page.
                                        </p>
                                    </div>
                                </AppSidebarMenu>
                            </div>

                            <div
                                v-show="showRoutesBottomMask"
                                class="from-sidebar pointer-events-none absolute right-0 bottom-0 left-0 z-10 h-8 bg-gradient-to-t from-20% to-transparent transition-opacity duration-300"
                            />
                        </AppSidebarGroupContent>
                    </AppSidebarGroup>
                </AppResizablePanel>
            </AppResizablePanelGroup>
        </AppSidebarContent>
    </AppSidebar>
</template>
