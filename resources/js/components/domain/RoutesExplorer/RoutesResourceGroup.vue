<script setup lang="ts">
/**
 * @component RoutesResourceGroup
 * @description A collapsible sidebar folder for a route resource. Renders
 *              its nested folders recursively and the routes that belong to it.
 */
import { AppCollapsible } from '@/components/base/collapsible';
import AppIcon from '@/components/base/icons/AppIcon.vue';
import ExplorerRow from '@/components/domain/RoutesExplorer/ExplorerRow.vue';
import RoutesListItem from '@/components/domain/RoutesExplorer/RoutesList/RoutesListItem.vue';
import { useShowingSearchResults } from '@/components/domain/RoutesExplorer/showingSearchResults';
import { useExplorerVisibility } from '@/composables/ui/useExplorerVisibility';
import type { RouteDefinition, RoutesFolder } from '@/interfaces/routes/routes';
import { useRequestStore } from '@/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RoutesResourceGroupProps {
    folder: RoutesFolder;
}

/*
 * Component Setup.
 */

const props = defineProps<RoutesResourceGroupProps>();

/*
 * Stores & Dependencies.
 */

const requestStore = useRequestStore();
const { isExplorerDrawerOpen } = useExplorerVisibility();

/*
 * State.
 */

const isOpen = useStorage(
    singletonPersistenceKey(`routes-explorer-resource-${props.folder.path}-expanded`),
    false,
);

const showingSearchResults = useShowingSearchResults();

/*
 * Computed & Methods.
 */

// Search results force every folder open.
const isExpanded = computed(() => showingSearchResults.value || isOpen.value);

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
    const availableRoutesForEndpoint = props.folder.routes.filter(
        (routeInGroup: RouteDefinition) => routeInGroup.endpoint === route.endpoint,
    );

    requestStore.initializeRequest(route, availableRoutesForEndpoint);
    isExplorerDrawerOpen.value = false;
};
</script>

<template>
    <li>
        <AppCollapsible
            :default-open="isOpen"
            :open="isExpanded"
            @update:open="isOpen = $event"
        >
            <ExplorerRow>
                <AppIcon
                    name="i-hugeicons-arrow-right-01"
                    class="-mx-tight shrink-0 transition-transform"
                    :class="{ 'rotate-90': isExpanded }"
                />
                <AppIcon name="i-hugeicons-folder-01" class="shrink-0" />
                <span class="max-w-45 truncate sm:max-w-55 md:max-w-65 lg:max-w-75">
                    {{ folder.name }}
                </span>
            </ExplorerRow>
            <template #content>
                <ul
                    class="border-default ms-loose gap-tight py-tight ps-tight flex min-w-0 flex-col border-s"
                >
                    <!-- Nested child folders (recursive). -->
                    <RoutesResourceGroup
                        v-for="child in folder.children"
                        :key="child.path"
                        :folder="child"
                    />
                    <!-- Routes directly in this folder. -->
                    <RoutesListItem
                        v-for="route in folder.routes"
                        :key="route.endpoint + '-' + route.method"
                        :route="route"
                        :resource="folder.path"
                        :on-click="() => setPendingRequest(route)"
                        :is-active="isRouteActive(route)"
                    />
                </ul>
            </template>
        </AppCollapsible>
    </li>
</template>
