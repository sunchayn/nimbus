<script setup lang="ts">
/**
 * @component ResponseViewerResponse
 * @description Renders the successful response details, including body, headers, and cookies.
 */
import { AppBadge } from '@/components/base/badge';
import {
    AppTabs,
    AppTabsContent,
    AppTabsList,
    AppTabsTrigger,
} from '@/components/base/tabs';
import { AppTooltipWrapper } from '@/components/base/tooltip';
import { AppScrollArea } from '@/components/base/scroll-area';
import ResponseBody from '@/components/domain/Client/Response/ResponseBody/ResponseBody.vue';
import ResponseDumpAndDie from '@/components/domain/Client/Response/ResponseBody/ResponseDumpAndDie.vue';
import ResponseCookies from '@/components/domain/Client/Response/ResponseCookies/ResponseCookies.vue';
import ResponseHeaders from '@/components/domain/Client/Response/ResponseHeaders/ResponseHeaders.vue';
import { useTabHorizontalScroll } from '@/composables/ui/useTabHorizontalScroll';
import type { RequestLog } from '@/interfaces/history/logs';
import { STATUS } from '@/interfaces/http';
import { useRequestStore } from '@/stores';
import { singletonPersistenceKey } from '@/utils/stores/uniquePersistenceKey';
import { useStorage } from '@vueuse/core';
import { DatabaseBackupIcon } from 'lucide-vue-next';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppResponseViewerResponseProps {
    response?: RequestLog | null;
}

/*
 * Component Setup.
 */

const props = defineProps<AppResponseViewerResponseProps>();

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * State.
 */

const tab = useStorage(singletonPersistenceKey('response-viewer-tab'), 'response');

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

const lastLog = computed(() => props.response);
const pendingRequestData = computed(() => requestStore.pendingRequestData);

const showTransactionAlert = computed(() => {
    return lastLog.value?.request.transactionMode;
});

const handleTabClick = (event: Event) => {
    scrollTabIntoView(event.currentTarget as HTMLElement);
};
</script>

<template>
    <div class="relative min-h-0 flex-1">
        <div
            v-if="pendingRequestData?.isProcessing"
            class="bg-background absolute top-0 left-0 z-[100] h-full w-full animate-pulse opacity-75"
        />
        <AppTabs
            :default-value="tab"
            class="mt-0 flex h-full flex-col overflow-auto"
            @update:model-value="tab = $event as string"
        >
            <div class="bg-subtle flex items-center justify-between border-b">
                <div class="relative min-w-0 flex-1">
                    <div
                        ref="scrollContainer"
                        class="scrollbar-hide flex items-center justify-between overflow-x-auto"
                        style="scrollbar-width: none; -ms-overflow-style: none"
                        @scroll="updateScrollMasks"
                    >
                        <AppTabsList class="h-toolbar px-panel rounded-none">
                            <AppTabsTrigger
                                value="response"
                                label="Response"
                                @click="handleTabClick"
                            />
                            <AppTabsTrigger
                                value="response-headers"
                                label="Headers"
                                @click="handleTabClick"
                            />
                            <AppTabsTrigger
                                value="response-cookies"
                                label="Cookies"
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

                <div class="pr-panel">
                    <AppTooltipWrapper
                        v-if="showTransactionAlert"
                        value="Changes were automatically rolled back for this request"
                    >
                        <div class="flex items-center">
                            <AppBadge variant="outline" class="gap-1">
                                <DatabaseBackupIcon class="size-3 min-w-3" />
                                Transaction Mode
                            </AppBadge>
                        </div>
                    </AppTooltipWrapper>
                </div>
            </div>
            <AppTabsContent
                value="response"
                class="mt-0 flex min-h-0 flex-1 flex-col overflow-hidden"
            >
                <AppScrollArea
                    v-if="lastLog?.response?.status !== STATUS.DUMP_AND_DIE"
                    class="min-h-0 flex-1"
                >
                    <ResponseBody
                        class="min-h-0"
                        :content="lastLog?.response?.body ?? ''"
                    />
                </AppScrollArea>

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
