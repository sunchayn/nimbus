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
import { uniquePersistenceKey } from '@/utils/stores';
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

const tab = useStorage(uniquePersistenceKey('request-builder-tab'), 'body');
</script>

<template>
    <div
        class="relative flex h-full max-h-full flex-1 flex-col"
        data-testid="request-builder-root"
    >
        <RequestBuilderEndpoint
            class="h-toolbar border-b"
            data-testid="request-builder-endpoint"
        />
        <AppTabs
            :default-value="tab"
            class="mt-0 flex flex-1 flex-col overflow-hidden"
            data-testid="app-tabs-container"
            @update:model-value="tab = $event as string"
        >
            <div class="bg-subtle border-b">
                <AppTabsList class="h-toolbar px-panel rounded-none">
                    <AppTabsTrigger value="parameters" label="Parameters" />
                    <AppTabsTrigger value="body" label="Body" />
                    <AppTabsTrigger value="authorization" label="Authorization" />
                    <AppTabsTrigger value="headers" label="Headers" />
                </AppTabsList>
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
