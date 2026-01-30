<script setup lang="ts">
/**
 * @component RequestBuilder
 * @description The main container for constructing API requests, including tabs for params, body, auth, and headers.
 */
import {
    AppTabs,
    AppTabsContent,
    AppTabsList,
    AppTabsTrigger,
} from '@/components/base/tabs';
import {
    RequestAuthorization,
    RequestBody,
    RequestBuilderEndpoint,
    RequestHeaders,
    RequestParameters,
} from '@/components/domain/Client/Request';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';

/*
 * Types & Interfaces.
 */

export interface AppRequestBuilderProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestBuilderProps>();

/*
 * State.
 */

const tab = useStorage(singletonPersistenceKey('request-builder-tab'), 'body');

const {
    scrollContainer,
    showLeftMask,
    showRightMask,
    updateScrollMasks,
    scrollTabIntoView,
} = useTabHorizontalScroll();

/*
 * Computed & Methods.
 */

const handleTabClick = (event: Event) => {
    scrollTabIntoView(event.currentTarget as HTMLElement);
};
</script>

<template>
    <div
        class="relative flex h-full max-h-full flex-1 flex-col"
        data-testid="request-builder-root"
    >
        <RequestBuilderEndpoint class="h-toolbar border-b" />
        <AppTabs
            :default-value="tab"
            class="mt-0 flex flex-1 flex-col overflow-hidden"
            data-testid="app-tabs-container"
            @update:model-value="tab = $event as string"
        >
            <div class="bg-subtle border-b">
                <div class="relative">
                    <div
                        ref="scrollContainer"
                        class="scrollbar-hide overflow-x-auto"
                        style="scrollbar-width: none; -ms-overflow-style: none"
                        @scroll="updateScrollMasks"
                    >
                        <AppTabsList class="h-toolbar px-panel rounded-none">
                            <AppTabsTrigger
                                value="parameters"
                                label="Parameters"
                                @click="handleTabClick"
                            />
                            <AppTabsTrigger
                                value="body"
                                label="Body"
                                @click="handleTabClick"
                            />
                            <AppTabsTrigger
                                value="authorization"
                                label="Authorization"
                                @click="handleTabClick"
                            />
                            <AppTabsTrigger
                                value="headers"
                                label="Headers"
                                @click="handleTabClick"
                            />
                        </AppTabsList>
                    </div>

                    <!-- Scroll Gradient Masks -->
                    <div
                        v-show="showLeftMask"
                        class="from-subtle via-subtle/80 pointer-events-none absolute top-0 bottom-0 left-0 w-8 bg-gradient-to-r to-transparent transition-opacity duration-200"
                    />
                    <div
                        v-show="showRightMask"
                        class="from-subtle via-subtle/80 pointer-events-none absolute top-0 right-0 bottom-0 w-8 bg-gradient-to-l to-transparent transition-opacity duration-200"
                    />
                </div>
            </div>
            <AppTabsContent
                value="parameters"
                class="mt-0 flex max-h-full min-h-0 flex-1 flex-col"
                data-testid="request-parameters"
            >
                <RequestParameters />
            </AppTabsContent>
            <AppTabsContent
                value="body"
                class="mt-0 flex max-h-full min-h-0 flex-1 flex-col"
                data-testid="request-body"
            >
                <RequestBody />
            </AppTabsContent>
            <AppTabsContent
                value="authorization"
                class="mt-0 flex max-h-full min-h-0 flex-1 flex-col"
                data-testid="request-authorization"
            >
                <RequestAuthorization />
            </AppTabsContent>
            <AppTabsContent
                value="headers"
                class="mt-0 flex max-h-full min-h-0 flex-1 flex-col"
                data-testid="request-headers"
            >
                <RequestHeaders />
            </AppTabsContent>
        </AppTabs>
    </div>
</template>
