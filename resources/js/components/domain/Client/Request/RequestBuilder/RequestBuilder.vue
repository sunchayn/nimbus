<script setup lang="ts">
/**
 * @component RequestBuilder
 * @description The main container for constructing API requests, including tabs for params, body, auth, and headers.
 */
import { AppTabs } from '@/components/base/tabs';
import {
    RequestAuthorization,
    RequestBody,
    RequestBuilderEndpoint,
    RequestHeaders,
    RequestParameters,
} from '@/components/domain/Client/Request';
import { useRequestStore } from '@/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface RequestBuilderProps {}

/*
 * Component Setup.
 */

defineProps<RequestBuilderProps>();

/*
 * State.
 */

const tab = useStorage(singletonPersistenceKey('request-builder-tab'), 'body');

const requestStore = useRequestStore();

// The badge is always shown, so the tab keeps its width when the count changes.
const countBadge = (count: number | undefined) => ({
    label: String(count ?? 0),
    size: 'sm' as const,
    variant: 'subtle' as const,
    color: 'neutral' as const,
    class: 'min-w-5 justify-center font-mono tabular-nums',
});

const tabs = computed(() => [
    {
        label: 'Parameters',
        value: 'parameters',
        slot: 'parameters',
        badge: countBadge(
            requestStore.pendingRequestData?.queryParameters.filter(p => p.enabled)
                .length,
        ),
    },
    { label: 'Body', value: 'body', slot: 'body' },
    { label: 'Authorization', value: 'authorization', slot: 'authorization' },
    {
        label: 'Headers',
        value: 'headers',
        slot: 'headers',
        badge: countBadge(
            requestStore.pendingRequestData?.headers.filter(h => h.enabled).length,
        ),
    },
]);
</script>

<template>
    <div
        class="gap-pane relative flex h-full max-h-full flex-1 flex-col"
        data-testid="request-builder-root"
    >
        <RequestBuilderEndpoint />
        <AppTabs
            v-model="tab"
            :items="tabs"
            variant="link"
            color="neutral"
            class="pane-surface mt-0 min-h-0 flex-1 gap-0"
            :ui="{
                list: 'h-toolbar overflow-x-auto rounded-none px-0',
                trigger: 'px-panel',
                content:
                    'border-default flex max-h-full min-h-0 flex-1 flex-col border-t',
            }"
            data-testid="app-tabs-container"
        >
            <template #parameters>
                <div
                    class="flex max-h-full min-h-0 flex-1 flex-col"
                    data-testid="request-parameters"
                >
                    <RequestParameters />
                </div>
            </template>
            <template #body>
                <div
                    class="flex max-h-full min-h-0 flex-1 flex-col"
                    data-testid="request-body"
                >
                    <RequestBody />
                </div>
            </template>
            <template #authorization>
                <div
                    class="flex max-h-full min-h-0 flex-1 flex-col"
                    data-testid="request-authorization"
                >
                    <RequestAuthorization />
                </div>
            </template>
            <template #headers>
                <div
                    class="flex max-h-full min-h-0 flex-1 flex-col"
                    data-testid="request-headers"
                >
                    <RequestHeaders />
                </div>
            </template>
        </AppTabs>
    </div>
</template>
