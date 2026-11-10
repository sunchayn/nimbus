<script setup lang="ts">
/**
 * @component ResponseViewerResponse
 * @description Renders the successful response details, including body, headers, and cookies.
 */
import { AppBadge } from '@/components/base/badge';
import { AppTabs } from '@/components/base/tabs';
import { AppTooltip } from '@/components/base/tooltip';
import ResponseDownload from '@/components/domain/Client/Response/ResponseBody/ResponseDownload.vue';
import ResponseDumpAndDie from '@/components/domain/Client/Response/ResponseBody/ResponseDumpAndDie.vue';
import ResponseNotice from '@/components/domain/Client/Response/ResponseBody/ResponseNotice.vue';
import ResponseCookies from '@/components/domain/Client/Response/ResponseCookies/ResponseCookies.vue';
import ResponseHeaders from '@/components/domain/Client/Response/ResponseHeaders/ResponseHeaders.vue';
import ResponsePlainView from '@/components/domain/Client/Response/ResponsePlainView/ResponsePlainView.vue';
import ResponseBodyViewer from '@/components/domain/Client/Response/ResponseTools/ResponseBodyViewer.vue';
import type { RequestLog } from '@/interfaces/history/logs';
import { STATUS } from '@/interfaces/http';
import { useRequestStore } from '@/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseViewerResponseProps {
    response?: RequestLog | null;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseViewerResponseProps>();

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * State.
 */

const tab = useStorage(singletonPersistenceKey('response-viewer-tab'), 'response');

// The badge is always shown, so the tab keeps its width when the count changes.
const countBadge = (count: number | undefined) => ({
    label: String(count ?? 0),
    size: 'sm' as const,
    variant: 'subtle' as const,
    color: 'neutral' as const,
    class: 'min-w-5 justify-center font-mono tabular-nums',
});

const tabs = computed(() => [
    { label: 'Response', value: 'response', slot: 'response' },
    {
        label: 'Headers',
        value: 'response-headers',
        slot: 'response-headers',
        badge: countBadge(props.response?.response?.headers.length),
    },
    {
        label: 'Cookies',
        value: 'response-cookies',
        slot: 'response-cookies',
        badge: countBadge(props.response?.response?.cookies.length),
    },
    { label: 'Plain', value: 'response-plain-view', slot: 'response-plain-view' },
]);

/*
 * Computed & Methods.
 */

const lastLog = computed(() => props.response);
const pendingRequestData = computed(() => requestStore.pendingRequestData);
</script>

<template>
    <div class="motion-safe:animate-fade-in-fast relative min-h-0 flex-1">
        <Transition
            leave-active-class="transition-opacity duration-fast motion-reduce:transition-none"
            leave-to-class="opacity-0"
        >
            <div
                v-if="pendingRequestData?.isProcessing"
                class="z-raised absolute top-0 left-0 h-full w-full"
            >
                <div class="bg-default size-full opacity-75 motion-safe:animate-pulse" />
            </div>
        </Transition>
        <AppTabs
            v-model="tab"
            :items="tabs"
            variant="link"
            color="neutral"
            class="mt-0 h-full gap-0 overflow-auto"
            :ui="{
                list: 'h-toolbar overflow-x-auto rounded-none px-0',
                trigger: 'px-panel',
                content:
                    'border-default flex min-h-0 flex-1 flex-col overflow-hidden border-t',
            }"
        >
            <template #list-trailing>
                <div
                    v-if="response?.request.transactionMode"
                    class="px-panel ms-auto flex shrink-0 items-center"
                >
                    <AppTooltip
                        text="Changes were automatically rolled back for this request"
                    >
                        <AppBadge
                            variant="outline"
                            color="neutral"
                            icon="i-hugeicons-database-backup"
                        >
                            Transaction mode
                        </AppBadge>
                    </AppTooltip>
                </div>
            </template>
            <template #response>
                <ResponseNotice
                    v-if="lastLog?.response?.bodyTruncated"
                    label="Stored body truncated"
                    indicator-class="text-warning"
                    class="m-panel"
                >
                    Only the first 256KB of this response was kept. Send the request again
                    to see the full body.
                </ResponseNotice>

                <ResponseDownload
                    v-if="lastLog?.response?.download"
                    :download="lastLog.response.download"
                />

                <ResponseBodyViewer
                    v-else-if="
                        lastLog && lastLog.response?.status !== STATUS.DUMP_AND_DIE
                    "
                    :log="lastLog"
                />

                <ResponseDumpAndDie
                    v-else
                    :raw-content="lastLog?.response?.body ?? '[]'"
                />
            </template>
            <template #response-headers>
                <ResponseHeaders :headers="lastLog?.response?.headers ?? []" />
            </template>
            <template #response-cookies>
                <ResponseCookies :cookies="lastLog?.response?.cookies ?? []" />
            </template>
            <template #response-plain-view>
                <ResponsePlainView
                    v-if="lastLog?.response"
                    :response="lastLog.response"
                    :duration-in-ms="lastLog.durationInMs"
                />
            </template>
        </AppTabs>
    </div>
</template>
