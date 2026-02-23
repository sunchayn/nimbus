<script setup lang="ts">
/**
 * @component RoutesList
 * @description A list of grouped route resources and their endpoints.
 */
import RoutesListItem from '@/components/domain/RoutesExplorer/RoutesList/RoutesListItem.vue';
import RoutesPrefixGroup from '@/components/domain/RoutesExplorer/RoutesPrefixGroup.vue';
import RoutesResource from '@/components/domain/RoutesExplorer/RoutesResourceGroup.vue';
import { type RouteDefinition, type RoutesGroup } from '@/interfaces/routes/routes';
import { useRequestStore } from '@/stores';
import { computed, inject } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRoutesListProps {
    routes: RoutesGroup[];
}

/*
 * Component Setup.
 */

defineProps<AppRoutesListProps>();

/*
 * Stores & Dependencies.
 */

const requestStore = useRequestStore();

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

const setPendingRequest = (route: RouteDefinition, resourceGroup: RoutesGroup) => {
    if (!route || !resourceGroup) {
        return;
    }

    // Get all routes with the same endpoint (different HTTP methods)
    const availableRoutesForEndpoint = resourceGroup.routes.filter(
        (routeInGroup: RouteDefinition) => routeInGroup.endpoint === route.endpoint,
    );

    requestStore.initializeRequest(route, availableRoutesForEndpoint);
};

const getPrefixRouteCount = (group: RoutesGroup): number => {
    if (!group.children) {
        return 0;
    }

    return group.children.reduce((sum, child) => sum + child.routes.length, 0);
};

const showingSearchResults = inject('showingSearchResults');
</script>

<template>
    <template v-if="routes && routes.length">
        <template v-for="group in routes" :key="group.resource">
            <!-- Prefix group: renders nested collapsible with child resource groups -->
            <RoutesPrefixGroup
                v-if="group.children"
                :prefix="group.prefix ?? group.resource"
                :route-count="getPrefixRouteCount(group)"
            >
                <RoutesResource
                    v-for="childGroup in group.children"
                    :key="childGroup.resource"
                    :resource="childGroup.resource"
                >
                    <RoutesListItem
                        v-for="route in childGroup.routes"
                        :key="route.endpoint + '-' + route.method"
                        :route="route"
                        :resource="childGroup.resource"
                        :on-click="() => setPendingRequest(route, childGroup)"
                        :is-active="isRouteActive(route)"
                    />
                </RoutesResource>
            </RoutesPrefixGroup>

            <!-- Regular resource group: existing flat behavior -->
            <RoutesResource v-else :resource="group.resource">
                <RoutesListItem
                    v-for="route in group.routes"
                    :key="route.endpoint + '-' + route.method"
                    :route="route"
                    :resource="group.resource"
                    :on-click="() => setPendingRequest(route, group)"
                    :is-active="isRouteActive(route)"
                />
            </RoutesResource>
        </template>
    </template>
    <template v-else-if="!showingSearchResults">
        <div class="px-2">
            <p class="mb-2 text-xs">
                No routes have been detected. Make sure to check the Wiki in case of
                doubts.
            </p>
        </div>
    </template>
    <template v-else>
        <p class="mb-2 px-2 text-xs">No routes matching your keywords.</p>
    </template>
</template>
