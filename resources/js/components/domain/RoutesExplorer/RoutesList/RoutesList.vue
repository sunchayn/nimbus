<script setup lang="ts">
/**
 * @component RoutesList
 * @description A list of route folders and their endpoints, shown as an explorer tree.
 */
import ExplorerTree from '@/components/domain/Explorer/ExplorerTree.vue';
import RoutesListItem from '@/components/domain/RoutesExplorer/RoutesList/RoutesListItem.vue';
import { useShowingSearchResults } from '@/components/domain/RoutesExplorer/showingSearchResults';
import { USER_GUIDE_LINK } from '@/config';
import { useRoutesOpenFolders } from '@/composables/explorer/useRoutesOpenFolders';
import { type RoutesFolder } from '@/interfaces/routes/routes';
import { useRequestStore } from '@/stores/request/useRequestStore';
import { buildRoutesTree, routeKey } from '@/utils/routes';
import { useExplorerVisibility } from '@/composables/ui/useExplorerVisibility';
import { computed } from 'vue';
import type { RouteDefinition } from '@/interfaces/routes/routes';
import type { ExplorerNode } from '@/interfaces/explorer';

/*
 * Types & Interfaces.
 */

export interface RoutesListProps {
    routes: RoutesFolder[];
}

/*
 * Component Setup.
 */

const props = defineProps<RoutesListProps>();

const requestStore = useRequestStore();
const { isExplorerDrawerOpen } = useExplorerVisibility();
const showingSearchResults = useShowingSearchResults();
const openFolders = useRoutesOpenFolders();

/*
 * Computed & Methods.
 */

const tree = computed(() => buildRoutesTree(props.routes));

// Search results force every folder open.
const isOpen = (path: string) => showingSearchResults.value || openFolders.isOpen(path);

const isActive = (route: RouteDefinition) =>
    route.endpoint === requestStore.pendingRequestData?.endpoint &&
    route.method === requestStore.pendingRequestData?.method;

const open = (route: RouteDefinition, folder: ExplorerNode<RouteDefinition>) => {
    // The routes with the same endpoint are the other methods of it.
    const sameEndpoint = folder.items.filter(item => item.endpoint === route.endpoint);

    requestStore.initializeRequest(route, sameEndpoint);
    isExplorerDrawerOpen.value = false;
};
</script>

<template>
    <template v-if="routes && routes.length">
        <ExplorerTree
            :node="tree"
            :item-key="routeKey"
            :is-open="isOpen"
            test-id="route"
            @toggle="openFolders.toggle"
        >
            <template #item="{ item, folder }">
                <RoutesListItem
                    :route="item"
                    :resource="folder.path"
                    :is-active="isActive(item)"
                    @click="open(item, folder)"
                />
            </template>
        </ExplorerTree>
    </template>
    <template v-else-if="!showingSearchResults">
        <div class="px-base">
            <p class="text-sm">No routes have been detected.</p>
            <p class="text-xs">
                Check the
                <a :href="USER_GUIDE_LINK" class="underline" target="_blank">
                    documentation
                </a>
                for more insights.
            </p>
        </div>
    </template>
    <template v-else>
        <p class="px-base text-xs">No routes matching your keywords.</p>
    </template>
</template>
