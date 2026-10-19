<script setup lang="ts">
/**
 * @component RoutesResourceGroup
 * @description A collapsible sidebar folder for a route resource. Renders
 *              recursively: a node like "creator/auth/password" becomes a
 *              folder tree (creator → auth → password) with routes on the
 *              folder they belong to.
 */
import {
    AppCollapsible,
    AppCollapsibleContent,
    AppCollapsibleTrigger,
} from '@/components/base/collapsible';
import {
    AppSidebarMenuButton,
    AppSidebarMenuItem,
    AppSidebarMenuSub,
} from '@/components/base/sidebar';
import RoutesListItem from '@/components/domain/RoutesExplorer/RoutesList/RoutesListItem.vue';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import { useRequestStore } from '@/stores';
import type { ResourceTreeNode } from '@/utils/routes/resourceTree';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { ChevronRight, Folder } from 'lucide-vue-next';
import { computed, inject } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoutesResourceGroupProps {
    /** The folder node: name, full resource path, routes and children. */
    node: ResourceTreeNode;
}

/*
 * Component Setup.
 */

const props = defineProps<AppRoutesResourceGroupProps>();

/*
 * Stores & Dependencies.
 */

const requestStore = useRequestStore();

/*
 * State.
 */

const isOpen = useStorage(
    singletonPersistenceKey(`routes-explorer-resource-${props.node.fullPath}-expanded`),
    false,
);

const showingSearchResults = inject('showingSearchResults');

/*
 * Computed & Methods.
 */

const pendingRequestData = computed(() => requestStore.pendingRequestData);

const isRouteActive = (route: RouteDefinition) => {
    if (!route) {
        return false;
    }
    if (!pendingRequestData.value) {
        return false;
    }

    return (
        route.endpoint === pendingRequestData.value.endpoint &&
        route.method === pendingRequestData.value.method
    );
};

const setPendingRequest = (route: RouteDefinition) => {
    if (!route) {
        return;
    }

    // Get all routes with the same endpoint (different HTTP methods).
    const availableRoutesForEndpoint = props.node.routes.filter(
        (routeInGroup: RouteDefinition) => routeInGroup.endpoint === route.endpoint,
    );

    requestStore.initializeRequest(route, availableRoutesForEndpoint);
};
</script>

<template>
    <AppSidebarMenuItem>
        <AppCollapsible
            class="group/collapsible [&[data-state=open]>button>svg:first-child]:rotate-90"
            :default-open="isOpen"
            :open="showingSearchResults ? true : isOpen"
            @update:open="isOpen = $event"
        >
            <AppCollapsibleTrigger as-child>
                <AppSidebarMenuButton
                    class="focus-visible:bg-sidebar-accent data-[active=true]:focus-visible:bg-sidebar-accent focus-visible:ring-0"
                >
                    <ChevronRight class="transition-transform" />
                    <Folder />
                    <span
                        class="max-w-[180px] truncate sm:max-w-[220px] md:max-w-[260px] lg:max-w-[300px]"
                    >
                        {{ node.name }}
                    </span>
                </AppSidebarMenuButton>
            </AppCollapsibleTrigger>
            <AppCollapsibleContent class="overflow-hidden">
                <AppSidebarMenuSub>
                    <!-- Nested child folders (recursive). -->
                    <RoutesResourceGroup
                        v-for="child in node.children"
                        :key="child.fullPath"
                        :node="child"
                    />
                    <!-- Routes directly in this folder. -->
                    <RoutesListItem
                        v-for="route in node.routes"
                        :key="route.endpoint + '-' + route.method"
                        :route="route"
                        :resource="node.fullPath"
                        :on-click="() => setPendingRequest(route)"
                        :is-active="isRouteActive(route)"
                    />
                </AppSidebarMenuSub>
            </AppCollapsibleContent>
        </AppCollapsible>
    </AppSidebarMenuItem>
</template>
