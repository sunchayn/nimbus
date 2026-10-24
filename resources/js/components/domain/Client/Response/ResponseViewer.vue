<script setup lang="ts">
/**
 * @component ResponseViewer
 * @description The main container for displaying the response of an API request.
 */
import ResponseDownloadDropdown from '@/components/domain/Client/Response/ResponseBody/ResponseDownloadDropdown.vue';
import ResponseStatus from '@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue';
import ResponseViewerEmptyState from '@/components/domain/Client/Response/ResponseViewerEmptyState.vue';
import ResponseViewerInternalError from '@/components/domain/Client/Response/ResponseViewerErrorState.vue';
import ResponseViewerResponse from '@/components/domain/Client/Response/ResponseViewerResponse.vue';
import { useTabsStore } from '@/stores';
import { cn } from '@/utils/ui';
import { computed, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseViewerProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseViewerProps>();

/*
 * Stores.
 */

const tabsStore = useTabsStore();

/*
 * Computed & Methods.
 */

const lastLog = computed(() => tabsStore.activeResponse);
</script>

<template>
    <div
        :class="cn('gap-pane flex h-full flex-col', props.class)"
        data-testid="response-content"
    >
        <div class="gap-pane flex shrink-0">
            <ResponseStatus class="min-w-0 flex-1" :response="lastLog" />
            <div
                v-if="lastLog?.response"
                class="pane-surface flex size-[calc(var(--toolbar-height)+2*var(--border-w))] shrink-0 items-center justify-center"
            >
                <ResponseDownloadDropdown :response="lastLog" />
            </div>
        </div>
        <div class="pane-surface flex min-h-0 flex-1 flex-col">
            <ResponseViewerEmptyState v-if="!lastLog" data-testid="response-empty" />
            <ResponseViewerInternalError
                v-else-if="lastLog.error"
                data-testid="response-error"
                :error="lastLog.error"
            />
            <ResponseViewerResponse v-else :response="lastLog" />
        </div>
    </div>
</template>
