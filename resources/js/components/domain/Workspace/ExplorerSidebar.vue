<script setup lang="ts">
/**
 * @component ExplorerSidebar
 * @description The sidebar content, which shows the Workspace tab beside the routes when the workspace is on.
 */
import { AppTabs } from '@/components/base/tabs';
import RouteExplorer, {
    type RouteExplorerProps,
} from '@/components/domain/RoutesExplorer/RouteExplorer.vue';
import WorkspaceExplorer from '@/components/domain/Workspace/Explorer/WorkspaceExplorer.vue';
import { useConfigStore } from '@/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';

defineProps<RouteExplorerProps>();

defineEmits<{
    retry: [];
    refresh: [];
}>();

const configStore = useConfigStore();

const tab = useStorage(singletonPersistenceKey('explorer-sidebar-tab'), 'routes');

// The hidden list stays rendered.
// With display none the open folders replay their open animation when shown again.
const hiddenClass = 'pointer-events-none invisible absolute inset-0';

const items = [
    { label: 'Routes', value: 'routes' },
    { label: 'Workspace', value: 'workspace' },
];
</script>

<template>
    <div class="gap-base relative flex h-full min-h-0 flex-col">
        <AppTabs
            v-if="configStore.workspaceEnabled"
            v-model="tab"
            :items="items"
            :content="false"
            variant="pill"
            color="neutral"
            class="w-full shrink-0"
            :ui="{
                list: 'bg-elevated border-default h-control-md w-full border p-tight',
                trigger:
                    'text-muted data-[state=active]:text-highlighted h-full flex-1 py-0',
                indicator: 'bg-default border-inverted border shadow-none',
            }"
            data-testid="explorer-sidebar-tabs"
        />
        <RouteExplorer
            :routes="routes"
            :loading="loading"
            :error="error"
            :snapshot-at="snapshotAt"
            class="min-h-0 flex-1"
            :class="{
                [hiddenClass]: configStore.workspaceEnabled && tab === 'workspace',
            }"
            @retry="$emit('retry')"
            @refresh="$emit('refresh')"
        />
        <WorkspaceExplorer
            v-if="configStore.workspaceEnabled"
            class="min-h-0 flex-1"
            :class="{ [hiddenClass]: tab !== 'workspace' }"
        />
    </div>
</template>
