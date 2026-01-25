<script setup lang="ts">
import {
    AppResizableHandle,
    AppResizablePanel,
    AppResizablePanelGroup,
} from '@/components/base/resizable';
import RequestBuilder from '@/components/domain/Client/Request/RequestBuilder.vue';
import ResponseViewer from '@/components/domain/Client/Response/ResponseViewer.vue';
import RouteExtractorExceptionRenderer from '@/components/domain/Errors/RouteExtractorExceptionRenderer.vue';
import RouteExplorer from '@/components/domain/RoutesExplorer/RouteExplorer.vue';
import { useSharedStateRestoration } from '@/composables/request/useSharedStateRestoration';
import { useResponsiveResizable } from '@/composables/ui/useResponsiveResizable';
import type { RouteExtractorException } from '@/interfaces';
import { useRoutesStore } from '@/stores';
import type { TemplateRef } from 'vue';
import { onBeforeMount, useTemplateRef } from 'vue';

defineOptions({
    name: 'MainPage',
});

/*
 * Stores & dependencies.
 */

const routesStore = useRoutesStore();

/*
 * Shared state restoration (from shareable links).
 */

useSharedStateRestoration();

/*
 * Lifecycle.
 */

onBeforeMount(() => {
    routesStore.initializeRoutes();
});

const panelsGroupElement: TemplateRef = useTemplateRef('panels-group');

const { 0: mainDirection, 1: clientDirection } = useResponsiveResizable(
    [600, 960],
    panelsGroupElement,
).thresholds;
</script>

<template>
    <div class="flex h-screen max-h-screen overflow-hidden">
        <AppResizablePanelGroup
            ref="panels-group"
            auto-save-id="main-splitter-group"
            :direction="mainDirection"
        >
            <AppResizablePanel :min-size="15" :default-size="20">
                <RouteExplorer :routes="routesStore.routes" />
            </AppResizablePanel>
            <AppResizableHandle />
            <template v-if="!routesStore.hasExtractionError">
                <AppResizablePanel :min-size="60" :default-size="80">
                    <AppResizablePanelGroup
                        auto-save-id="client-splitter-group"
                        :direction="clientDirection"
                    >
                        <AppResizablePanel :min-size="30" :default-size="50">
                            <RequestBuilder />
                        </AppResizablePanel>
                        <AppResizableHandle />
                        <AppResizablePanel :min-size="30" :default-size="50">
                            <ResponseViewer />
                        </AppResizablePanel>
                    </AppResizablePanelGroup>
                </AppResizablePanel>
            </template>
            <template v-else>
                <AppResizablePanel :min-size="60" :default-size="85">
                    <RouteExtractorExceptionRenderer
                        :error="
                            routesStore.routeExtractorException as RouteExtractorException
                        "
                    />
                </AppResizablePanel>
            </template>
        </AppResizablePanelGroup>
    </div>
</template>
