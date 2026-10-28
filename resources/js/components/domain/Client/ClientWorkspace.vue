<script setup lang="ts">
/**
 * @component ClientWorkspace
 * @description The request tabs with the request builder and the response viewer, or the global errors.
 */
import { AppSplitter } from '@/components/base/resizable';
import RequestBuilder from '@/components/domain/Client/Request/RequestBuilder/RequestBuilder.vue';
import RequestTabsBar from '@/components/domain/Client/Request/RequestTabsBar.vue';
import ResponseViewer from '@/components/domain/Client/Response/ResponseViewer.vue';
import GlobalErrorRenderer from '@/components/domain/Errors/GlobalErrorRenderer.vue';
import RouteExtractorExceptionRenderer from '@/components/domain/Errors/RouteExtractorExceptionRenderer.vue';
import { useResponsiveResizable } from '@/composables/ui/useResponsiveResizable';
import type { GlobalException, RouteExtractorException } from '@/interfaces';
import { useErrorStore, useRoutesStore } from '@/stores';
import type { TemplateRef } from 'vue';
import { useTemplateRef } from 'vue';

/*
 * Stores & dependencies.
 */

const routesStore = useRoutesStore();
const errorStore = useErrorStore();

const root: TemplateRef = useTemplateRef('root');

const clientDirection = useResponsiveResizable([960], root).thresholds[0];

/*
 * Panels.
 */

// The handle is the gap between panes, and it shows a thin line while hovered or dragged.
const handleUi = (direction: 'horizontal' | 'vertical') => ({
    handle: [
        'relative z-sticky bg-transparent hover:bg-transparent data-[resize-handle-state=hover]:bg-transparent data-[resize-handle-state=drag]:bg-transparent before:absolute before:rounded-full before:bg-transparent hover:before:bg-accented focus-visible:outline-none focus-visible:before:bg-primary data-[resize-handle-state=drag]:before:bg-accented',
        direction === 'horizontal'
            ? 'w-pane after:w-hit-lg before:inset-y-tight before:left-1/2 before:w-grip before:-translate-x-1/2'
            : 'h-pane after:h-hit-lg before:inset-x-tight before:top-1/2 before:h-grip before:-translate-y-1/2',
    ].join(' '),
});

const clientItems = [
    { minSize: 30, defaultSize: 50, class: 'block' },
    { minSize: 30, defaultSize: 50, class: 'block' },
];
</script>

<template>
    <div
        v-if="!routesStore.hasAnyError && !errorStore.hasGlobalError"
        ref="root"
        class="gap-group flex h-full min-w-0 flex-col"
    >
        <RequestTabsBar />
        <AppSplitter
            class="min-h-0 flex-1"
            auto-save-id="client-splitter-group"
            :orientation="clientDirection"
            :items="clientItems"
            :ui="handleUi(clientDirection)"
        >
            <template #panel-0>
                <RequestBuilder />
            </template>
            <template #panel-1>
                <ResponseViewer />
            </template>
        </AppSplitter>
    </div>
    <div v-else class="h-full overflow-auto">
        <GlobalErrorRenderer
            v-if="errorStore.hasGlobalError"
            :error="errorStore.globalError as GlobalException"
        />
        <RouteExtractorExceptionRenderer
            v-else
            :error="routesStore.routeExtractorException as RouteExtractorException"
        />
    </div>
</template>
