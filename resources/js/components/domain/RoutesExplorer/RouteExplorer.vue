<script setup lang="ts">
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
import RouteExplorerHeader from '@/components/domain/RoutesExplorer/RouteExplorerHeader.vue';
import RouteExplorerVersionSelector from '@/components/domain/RoutesExplorer/RouteExplorerVersionSelector.vue';
import RoutesList from '@/components/domain/RoutesExplorer/RoutesList/RoutesList.vue';
import { RouteDefinition, RoutesGroup } from '@/interfaces/routes/routes';
import { useConfigStore } from '@/stores';
import { uniquePersistenceKey } from '@/utils/stores';
import { useStorage } from '@vueuse/core';
import { computed } from 'vue';

/*
 * Props.
 */

const props = defineProps<{
    routes: { [_key in string]?: RoutesGroup[] } | null;
}>();

/*
 * State.
 */

const search = useStorage(uniquePersistenceKey('routes-explorer-search-keyword'), '');

const versions = computed(() => Object.keys(props.routes || []));

const currentVersion = computed(() => versions.value[0]);

/*
 * Computed.
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

/*
 * Stores.
 */

const configStore = useConfigStore();
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
            <RouteExplorerVersionSelector
                v-if="configStore.isVersioned && versions.length"
                v-model="currentVersion"
                :versions="versions"
            />
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
        <AppSidebarRail />
    </AppSidebar>
</template>
