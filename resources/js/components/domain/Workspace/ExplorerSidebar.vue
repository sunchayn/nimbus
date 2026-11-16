<script setup lang="ts">
/**
 * @component ExplorerSidebar
 * @description The sidebar content, with one search and filter bar above the Routes and Workspace lists.
 */
import { AppSegmentedTabs } from '@/components/base/tabs';
import ExplorerToolbar from '@/components/domain/Explorer/ExplorerToolbar.vue';
import RouteExplorer, {
    type RouteExplorerProps,
} from '@/components/domain/RoutesExplorer/RouteExplorer.vue';
import WorkspaceExplorer from '@/components/domain/Workspace/Explorer/WorkspaceExplorer.vue';
import { useConfigStore } from '@/stores/core/useConfigStore';
import { useWorkspaceStore } from '@/stores/workspace/useWorkspaceStore';
import {
    collectMethods,
    collectMiddleware,
    collectRoutes,
    emptyRouteFilters,
} from '@/utils/routes';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

const props = defineProps<
    Omit<RouteExplorerProps, 'search' | 'filters'> & {
        // Unix seconds of the routes snapshot, the refresh button is hidden without it.
        snapshotAt?: number | null;
    }
>();

defineEmits<{
    retry: [];
    refresh: [];
}>();

const configStore = useConfigStore();
const workspaceStore = useWorkspaceStore();

const tab = useStorage(singletonPersistenceKey('explorer-sidebar-tab'), 'routes');
const search = useStorage(singletonPersistenceKey('routes-explorer-search-keyword'), '');
const filters = ref(emptyRouteFilters());
const currentVersion = ref('');

const isWorkspaceTab = computed(
    () => configStore.workspaceEnabled && tab.value === 'workspace',
);

const versions = computed(() => Object.keys(props.routes || {}));

// Initialize or update the current version when the versions change, such as after a project switch.
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

const allRoutes = computed(() =>
    collectRoutes(props.routes?.[currentVersion.value] ?? []),
);

const isWorkspaceEmpty = computed(
    () =>
        !workspaceStore.listing?.requests.length &&
        !workspaceStore.listing?.folders.length,
);

const isSearchDisabled = computed(() =>
    isWorkspaceTab.value ? isWorkspaceEmpty.value : allRoutes.value.length === 0,
);

// The hidden list stays rendered.
// With display none the open folders replay their open animation when shown again.
const hiddenClass = 'pointer-events-none invisible absolute inset-0';

const items = [
    { label: 'Routes', value: 'routes' },
    { label: 'Workspace', value: 'workspace' },
];
</script>

<template>
    <div class="relative flex h-full min-h-0 flex-col">
        <ExplorerToolbar
            v-model:search="search"
            v-model:filters="filters"
            :routes="allRoutes"
            :methods="collectMethods(allRoutes)"
            :middleware="collectMiddleware(allRoutes)"
            :disabled="isSearchDisabled"
            :loading="loading"
            :snapshot-at="snapshotAt"
            class="mb-base"
            @refresh="$emit('refresh')"
        />

        <div
            v-if="configStore.workspaceEnabled"
            class="p-hair h-pane-header mb-tight border-default bg-elevated flex items-center rounded-sm border"
        >
            <AppSegmentedTabs
                v-model="tab"
                :items="items"
                :content="false"
                outlined
                :ui="{ list: 'h-full! border-0 bg-transaprent' }"
                class="h-full shrink-0"
                data-testid="explorer-sidebar-tabs"
            />
        </div>
        <div class="relative min-h-0 flex-1">
            <RouteExplorer
                v-model:version="currentVersion"
                :routes="routes"
                :loading="loading"
                :error="error"
                :search="search"
                :filters="filters"
                class="h-full min-h-0"
                :class="{ [hiddenClass]: isWorkspaceTab }"
                @retry="$emit('retry')"
            />
            <WorkspaceExplorer
                v-if="configStore.workspaceEnabled"
                :search="search"
                :filters="filters"
                :routes="allRoutes"
                class="h-full min-h-0"
                :class="{ [hiddenClass]: !isWorkspaceTab }"
            />
        </div>
    </div>
</template>
