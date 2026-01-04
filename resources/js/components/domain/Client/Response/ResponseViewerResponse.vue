<script setup lang="ts">
import {
    AppTabs,
    AppTabsContent,
    AppTabsList,
    AppTabsTrigger,
} from '@/components/base/tabs';
import ResponseBody from '@/components/domain/Client/Response/ResponseBody/ResponseBody.vue';
import ResponseCookies from '@/components/domain/Client/Response/ResponseCookies/ResponseCookies.vue';
import ResponseHeaders from '@/components/domain/Client/Response/ResponseHeaders/ResponseHeaders.vue';
import { STATUS } from '@/interfaces/http';
import { useRequestsHistoryStore, useRequestStore } from '@/stores';
import { computed } from 'vue';
import ResponseDumpAndDie from './ResponseBody/ResponseDumpAndDie.vue';

const historyStore = useRequestsHistoryStore();
const requestStore = useRequestStore();
const lastLog = computed(() => historyStore.lastLog);
const pendingRequestData = computed(() => requestStore.pendingRequestData);
</script>

<template>
    <div class="relative min-h-0 flex-1">
        <div
            v-if="pendingRequestData?.isProcessing"
            class="bg-background absolute top-0 left-0 z-[100] h-full w-full animate-pulse opacity-75"
        />
        <AppTabs default-value="response" class="mt-0 flex h-full flex-col overflow-auto">
            <div class="bg-subtle-background border-b">
                <AppTabsList class="h-toolbar px-panel rounded-none">
                    <AppTabsTrigger value="response" label="Response" />
                    <AppTabsTrigger value="response-headers" label="Headers" />
                    <AppTabsTrigger value="response-cookies" label="Cookies" />
                </AppTabsList>
            </div>
            <AppTabsContent
                value="response"
                class="mt-0 flex min-h-0 flex-1 flex-col overflow-hidden"
            >
                <ResponseBody
                    v-if="lastLog?.response?.status !== STATUS.DUMP_AND_DIE"
                    class="min-h-0 overflow-auto"
                    :content="lastLog?.response?.body ?? ''"
                />

                <ResponseDumpAndDie
                    v-else
                    :raw-content="lastLog?.response?.body ?? '[]'"
                />
            </AppTabsContent>
            <AppTabsContent
                value="response-headers"
                class="mt-0 flex min-h-0 flex-1 flex-col overflow-hidden"
            >
                <ResponseHeaders :headers="lastLog?.response?.headers ?? []" />
            </AppTabsContent>
            <AppTabsContent
                value="response-cookies"
                class="mt-0 flex min-h-0 flex-1 flex-col overflow-hidden"
            >
                <ResponseCookies :cookies="lastLog?.response?.cookies ?? []" />
            </AppTabsContent>
        </AppTabs>
    </div>
</template>
