<script setup lang="ts">
/**
 * @component MainPage
 * @description Main workspace page with the routes explorer and the request client.
 */
import { AppSplitter } from '@/components/base/resizable';
import { AppSlideover } from '@/components/base/slideover';
import ClientWorkspace from '@/components/domain/Client/ClientWorkspace.vue';
import RouteExplorer from '@/components/domain/RoutesExplorer/RouteExplorer.vue';
import { useSharedStateRestoration } from '@/composables/request/useSharedStateRestoration';
import { useExplorerVisibility } from '@/composables/ui/useExplorerVisibility';
import { useErrorStore, useRoutesStore } from '@/stores';
import type { TemplateRef } from 'vue';
import { computed, onBeforeMount, useTemplateRef, watch } from 'vue';

defineOptions({
    name: 'MainPage',
});

/*
 * Stores & dependencies.
 */

const routesStore = useRoutesStore();
const errorStore = useErrorStore();

/*
 * Shared state restoration (from shareable links).
 */

useSharedStateRestoration();

/*
 * Lifecycle.
 */

onBeforeMount(() => {
    errorStore.initializeGlobalErrors();
    routesStore.initializeRoutes();
});

const panelsGroupElement: TemplateRef = useTemplateRef('panels-group');

/*
 * Panels.
 */

const { isExplorerOpen, isExplorerDrawerOpen, isCompact } = useExplorerVisibility();

// The handle is the gap between groups, and it shows a thin line while hovered or dragged.
const handleUi = computed(() => ({
    handle: [
        'relative w-group bg-transparent hover:bg-transparent data-[resize-handle-state=hover]:bg-transparent data-[resize-handle-state=drag]:bg-transparent before:absolute before:rounded-full before:bg-transparent hover:before:bg-accented focus-visible:outline-none focus-visible:before:bg-primary data-[resize-handle-state=drag]:before:bg-accented before:inset-y-1 before:left-1/2 before:w-grip before:-translate-x-1/2',
        !isExplorerOpen.value && 'hidden',
    ],
}));

const mainItems = computed(() => [
    {
        minSize: 15,
        defaultSize: 20,
        collapsible: true,
        collapsedSize: 0,
        class: 'block',
    },
    {
        minSize: 60,
        defaultSize: 80,
        class: 'block',
    },
]);

const toggleExplorerPanel = (open: boolean) => {
    const panels = panelsGroupElement.value as {
        expand: (index: number) => void;
        collapse: (index: number) => void;
    } | null;

    if (open) {
        panels?.expand(0);
    } else {
        panels?.collapse(0);
    }
};

watch(isExplorerOpen, toggleExplorerPanel);

// The panels have no layout until the first layout event, so a collapse before it throws.
let isLayoutInitialized = false;

const onLayout = () => {
    if (isLayoutInitialized) {
        return;
    }

    isLayoutInitialized = true;

    if (!isExplorerOpen.value) {
        toggleExplorerPanel(false);
    }
};
</script>

<template>
    <div class="p-base flex h-full overflow-hidden">
        <div v-if="isCompact" class="min-w-0 flex-1">
            <ClientWorkspace />
            <AppSlideover
                v-model:open="isExplorerDrawerOpen"
                side="left"
                title="Routes"
                description="Pick a route to open it in a new request."
                :ui="{
                    content: 'max-w-xs',
                    body: 'flex min-h-0 flex-col overflow-hidden',
                }"
            >
                <template #body>
                    <RouteExplorer class="min-h-0 flex-1" :routes="routesStore.routes" />
                </template>
            </AppSlideover>
        </div>
        <AppSplitter
            v-else
            ref="panels-group"
            auto-save-id="main-splitter-group"
            orientation="horizontal"
            :items="mainItems"
            :ui="handleUi"
            @layout="onLayout"
            @collapse="isExplorerOpen = false"
            @expand="isExplorerOpen = true"
        >
            <template #panel-0>
                <RouteExplorer :routes="routesStore.routes" />
            </template>
            <template #panel-1>
                <ClientWorkspace />
            </template>
        </AppSplitter>
    </div>
</template>
