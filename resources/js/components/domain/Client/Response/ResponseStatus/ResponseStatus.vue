<script setup lang="ts">
import { AppButton } from '@/components/base/button';
import ResponseStatusCode from '@/components/domain/Client/Response/ResponseStatus/ResponseStatusCode.vue';
import { PendingRequest, STATUS } from '@/interfaces/http';
import { useRequestsHistoryStore, useRequestStore } from '@/stores';
import { cn } from '@/utils/ui';
import { useTimeAgo } from '@vueuse/core';
import { RefreshCwOffIcon } from 'lucide-vue-next';
import prettyBytes from 'pretty-bytes';
import prettyMs from 'pretty-ms';
import { PrimitiveProps } from 'reka-ui';
import { computed, ComputedRef, HTMLAttributes } from 'vue';

/*
 * Types & interfaces.
 */

interface ResponseStatusProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

/*
 * Props.
 */

const props = defineProps<ResponseStatusProps>();

/*
 * Stores.
 */

const requestStore = useRequestStore();
const historyStore = useRequestsHistoryStore();

/*
 * Computed.
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

const readableTime = computed(() => {
    if (lastLog.value?.response === undefined) {
        return '';
    }

    const timestamp = new Date(lastLog.value.response.timestamp * 1000);
    const timeAgo = useTimeAgo(timestamp);

    return timeAgo.value;
});

const absoluteTime = computed(() => {
    if (lastLog.value?.response === undefined) {
        return '';
    }

    const timestamp = new Date(lastLog.value.response.timestamp * 1000);

    return timestamp.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

/*
 * Actions.
 */

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
                    <span>{{ duration }}</span>
                    <template v-if="!pendingRequestData?.isProcessing">
                        <span class="text-color-muted mx-1 text-xs">/</span>
                        <span>{{ size }}</span>
                    </template>
                </span>
            </div>

            <div v-if="!pendingRequestData?.isProcessing" class="flex items-center">
                <small class="text-subtle text-xs" :title="absoluteTime">
                    {{ readableTime }}
                </small>
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
