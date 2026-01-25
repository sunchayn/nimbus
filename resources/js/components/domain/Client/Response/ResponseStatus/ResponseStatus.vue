<script setup lang="ts">
/**
 * @component ResponseStatus
 * @description Renders the status bar for a response, showing the status code, duration, size, and history controls.
 */
import { AppButton } from '@/components/base/button';
import RequestHistory from '@/components/domain/Client/Response/ResponseStatus/History/RequestHistory.vue';
import ResponseStatusCode from '@/components/domain/Client/Response/ResponseStatus/ResponseStatusCode.vue';
import { type PendingRequest, STATUS } from '@/interfaces/http';
import { useRequestsHistoryStore, useRequestStore } from '@/stores';
import { cn } from '@/utils/ui';
import { RefreshCwOffIcon } from 'lucide-vue-next';
import prettyBytes from 'pretty-bytes';
import prettyMs from 'pretty-ms';
import { type PrimitiveProps } from 'reka-ui';
import { computed, type ComputedRef, type HTMLAttributes } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppResponseStatusProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

/*
 * Component Setup.
 */

const props = defineProps<AppResponseStatusProps>();

/*
 * Stores.
 */

const requestStore = useRequestStore();
const historyStore = useRequestsHistoryStore();

/*
 * Computed & Methods.
 */

const pendingRequestData: ComputedRef<PendingRequest | null> = computed(
    () => requestStore.pendingRequestData,
);

const lastLog = computed(() => historyStore.lastLog);

const status = computed(() => {
    if (pendingRequestData.value?.isProcessing) {
        return STATUS.PENDING;
    }

    if (!lastLog.value || !lastLog.value.response) {
        return STATUS.EMPTY;
    }

    return lastLog.value.response.status ?? STATUS.EMPTY;
});

const size = computed(() =>
    prettyBytes(
        pendingRequestData.value?.wasExecuted
            ? (lastLog.value?.response?.sizeInBytes ?? 0)
            : 0, // <- When a new endpoint is initialized, we reset the size as well.
        { space: false },
    ),
);

const duration = computed(() => {
    return prettyMs(
        // If there's a pending request that's processing, use its duration
        // Otherwise, use the last completed request's duration
        pendingRequestData.value?.durationInMs ?? lastLog.value?.durationInMs ?? 0,
        {
            verbose: false,
            secondsDecimalDigits: 2,
            keepDecimalsOnWholeSeconds: true,
        },
    );
});

const cancelRequest = () => {
    requestStore.cancelCurrentRequest();
};
</script>

<template>
    <div
        :class="
            cn('h-toolbar relative flex items-center justify-between p-2', props.class)
        "
    >
        <div class="flex w-full items-center justify-between gap-1">
            <div class="flex items-center space-x-2">
                <ResponseStatusCode
                    :status="status"
                    :response="
                        !pendingRequestData?.isProcessing ? lastLog?.response : undefined
                    "
                />
                <div class="w-8 border-b border-zinc-200"></div>
                <span class="text-xs">
                    <span data-testid="response-status-duration">{{ duration }}</span>
                    <template v-if="!pendingRequestData?.isProcessing">
                        <span class="text-subtle-foreground mx-1 text-xs">/</span>
                        <span data-testid="response-status-size">{{ size }}</span>
                    </template>
                </span>
            </div>

            <div v-if="!pendingRequestData?.isProcessing" class="flex items-center">
                <RequestHistory />
            </div>
        </div>

        <div v-if="pendingRequestData?.isProcessing">
            <AppButton variant="outline" size="xs" @click="cancelRequest">
                <RefreshCwOffIcon />
                Cancel
            </AppButton>
        </div>
    </div>
</template>
