<script setup lang="ts">
/**
 * @component RouteExplorer
 * @description The main sidebar component for exploring and searching available API routes.
 */
import {
    AppSidebar,
    AppSidebarContent,
    AppSidebarGroup,
    AppSidebarGroupContent,
    AppSidebarGroupLabel,
    AppSidebarInput,
    AppSidebarMenu,
    AppSidebarRail,
} from '@/components/base/sidebar';
import ApplicationSwitcher from '@/components/domain/RoutesExplorer/ApplicationSwitcher.vue';
import RouteExplorerHeader from '@/components/domain/RoutesExplorer/RouteExplorerHeader.vue';
import RouteExplorerVersionSelector from '@/components/domain/RoutesExplorer/RouteExplorerVersionSelector.vue';
import RoutesList from '@/components/domain/RoutesExplorer/RoutesList/RoutesList.vue';
import { type RouteDefinition, type RoutesGroup } from '@/interfaces/routes/routes';
import { useConfigStore } from '@/stores';
import { uniquePersistenceKey } from '@/utils/stores';
import { useStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

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

/*
 * State.
 */

const search = useStorage(uniquePersistenceKey('routes-explorer-search-keyword'), '');
const currentVersion = ref('');

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
                const filtered = group.routes.filter((route: RouteDefinition) =>
                    route.endpoint.toLowerCase().includes(keyword),
                );

                return filtered.length > 0 ? { ...group, routes: filtered } : null;
            })
            .filter((group): group is RoutesGroup => group !== null) || []
    );
});

const hasMultipleApplications = computed(
    () => Object.keys(configStore.applications).length > 1,
);
</script>

<template>
    <AppSidebar collapsible="none" class="flex h-full max-h-screen w-full">
        <RouteExplorerHeader />
        <div>
            <AppSidebarInput
                v-model="search"
                placeholder="Type to search..."
                :disabled="routesInVersion.length === 0"
                class="h-[calc(var(--toolbar-height)+1px)] w-full rounded-none border-0 border-b text-xs shadow-none focus:ring-0 focus-visible:ring-0"
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
        <AppSidebarContent>
            <AppSidebarGroup class="p-0">
                <AppSidebarGroupLabel>Routes</AppSidebarGroupLabel>
                <AppSidebarGroupContent>
                    <AppSidebarMenu>
                        <RoutesList
                            v-if="routes !== null"
                            :routes="filteredRoutes"
                            :filtering-enabled="search.trim().length > 0"
                        />
                        <div v-else class="px-2">
                            <p class="mb-2 text-xs">
                                Routes extraction was Interrupted, check the error on the
                                page.
                            </p>
                        </div>
                    </AppSidebarMenu>
                </AppSidebarGroupContent>
            </AppSidebarGroup>
        </AppSidebarContent>
    </AppSidebar>
</template>
