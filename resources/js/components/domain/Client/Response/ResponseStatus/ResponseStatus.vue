<script setup lang="ts">
/**
 * @component ResponseStatus
 * @description Renders the status bar for a response, showing the status code, duration, size, and history controls.
 */
import { AppBadge } from '@/components/base/badge';
import { AppButton } from '@/components/base/button';
import { AppTooltip } from '@/components/base/tooltip';
import RequestHistory from '@/components/domain/Client/Response/ResponseStatus/History/RequestHistory.vue';
import ResponseStatusCode from '@/components/domain/Client/Response/ResponseStatus/ResponseStatusCode.vue';
import type { RequestLog } from '@/interfaces/history/logs';
import { type PendingRequest, STATUS } from '@/interfaces/http';
import { useRequestStore } from '@/stores';
import { cn } from '@/utils/ui';
import prettyBytes from 'pretty-bytes';
import prettyMs from 'pretty-ms';
import { computed, type ComputedRef, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface ResponseStatusProps {
    class?: HTMLAttributes['class'];
    response?: RequestLog | null;
}

/*
 * Component Setup.
 */

const props = defineProps<ResponseStatusProps>();

/*
 * Stores.
 */

const requestStore = useRequestStore();

/*
 * Computed & Methods.
 */

const pendingRequestData: ComputedRef<PendingRequest | null> = computed(
    () => requestStore.pendingRequestData,
);

const lastLog = computed(() => props.response);

const status = computed(() => {
    if (pendingRequestData.value?.isProcessing) {
        return STATUS.PENDING;
    }

    if (!lastLog.value || !lastLog.value.response) {
        return STATUS.EMPTY;
    }

    return lastLog.value.response.status ?? STATUS.EMPTY;
});

const size = computed(() => prettyBytes(lastLog.value?.response?.sizeInBytes ?? 0));

const duration = computed(() => {
    const text = prettyMs(
        // If there's a pending request that's processing, use its duration
        // Otherwise, use the provided response's duration
        pendingRequestData.value?.isProcessing
            ? (pendingRequestData.value?.durationInMs ?? 0)
            : (lastLog.value?.durationInMs ?? 0),
        {
            verbose: false,
            secondsDecimalDigits: 2,
            keepDecimalsOnWholeSeconds: true,
        },
    );

    // Put a space between the number and its unit.
    return text.replace(/(\d)([a-zµ]+)/gi, '$1 $2');
});

const cancelRequest = () => {
    requestStore.cancelCurrentRequest();
};

const statusAnnouncement = computed(() => {
    if (pendingRequestData.value?.isProcessing) {
        return 'Sending request';
    }

    return lastLog.value ? 'Response received' : '';
});

const isImportedFromShare = computed(() => lastLog.value?.importedFromShare === true);
</script>

<template>
    <div
        :class="
            cn(
                'pane-surface pr-panel @container flex h-[calc(var(--toolbar-height)+2*var(--border-w))] shrink-0 items-center justify-between gap-3',
                props.class,
            )
        "
        data-testid="response-status"
    >
        <span class="sr-only" role="status">{{ statusAnnouncement }}</span>
        <div class="gap-base pl-panel flex min-w-0 items-center overflow-hidden">
            <ResponseStatusCode
                :status="status"
                :response="
                    !pendingRequestData?.isProcessing ? lastLog?.response : undefined
                "
            />
            <div class="border-default w-8 shrink-0 border-b @max-md:hidden"></div>
            <span
                class="gap-base inline-flex items-center text-xs whitespace-nowrap @max-sm:hidden"
            >
                <span class="tabular-nums" data-testid="response-status-duration">
                    {{ duration }}
                </span>
                <template v-if="!pendingRequestData?.isProcessing">
                    <span class="text-muted" aria-hidden="true">•</span>
                    <span data-testid="response-status-size">{{ size }}</span>
                </template>
            </span>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <AppTooltip
                v-if="isImportedFromShare"
                text="This response was imported from a shareable link"
            >
                <AppBadge
                    variant="soft"
                    color="info"
                    icon="i-hugeicons-file-import"
                    data-testid="imported-badge"
                >
                    Imported
                </AppBadge>
            </AppTooltip>

            <template v-if="!pendingRequestData?.isProcessing">
                <RequestHistory />
            </template>

            <AppButton
                v-else
                variant="outline"
                color="neutral"
                size="xs"
                icon="i-hugeicons-refresh-cw-off"
                label="Cancel"
                @click="cancelRequest"
            />
        </div>
    </div>
</template>
