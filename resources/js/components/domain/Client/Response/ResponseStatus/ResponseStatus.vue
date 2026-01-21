<script setup lang="ts">
import { AppButton } from '@/components/base/button';
import RequestHistory from '@/components/domain/Client/Response/ResponseStatus/History/RequestHistory.vue';
import ResponseStatusCode from '@/components/domain/Client/Response/ResponseStatus/ResponseStatusCode.vue';
import { PendingRequest, STATUS } from '@/interfaces/http';
import { useRequestsHistoryStore, useRequestStore } from '@/stores';
import { cn } from '@/utils/ui';
import { Import, RefreshCwOffIcon } from 'lucide-vue-next';
import prettyBytes from 'pretty-bytes';
import prettyMs from 'pretty-ms';
import { PrimitiveProps } from 'reka-ui';
import { computed, ComputedRef, HTMLAttributes } from 'vue';
import AppTooltipWrapper from '../../../../base/tooltip/AppTooltipWrapper.vue';

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
/*
 * Actions.
 */

const cancelRequest = () => {
    requestStore.cancelCurrentRequest();
};

const isImportedFromShare = computed(() => lastLog.value?.importedFromShare === true);
</script>

<template>
    <div :class="cn('h-toolbar flex', props.class)">
        <div class="relative flex h-full flex-1 items-center justify-between p-2">
            <div class="flex w-full items-center justify-between gap-1">
                <div class="flex items-center space-x-2">
                    <ResponseStatusCode
                        :status="status"
                        :response="
                            !pendingRequestData?.isProcessing
                                ? lastLog?.response
                                : undefined
                        "
                    />
                    <div class="w-8 border-b border-zinc-200"></div>
                    <span class="text-xs">
                        <span data-testid="response-status-duration">{{ duration }}</span>
                        <template v-if="!pendingRequestData?.isProcessing">
                            <span class="text-color-muted mx-1 text-xs">/</span>
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
        <AppTooltipWrapper
            v-if="isImportedFromShare"
            value="This response was imported from a shareable link"
        >
            <div
                class="flex h-full items-center gap-1 rounded-none border-indigo-500/50 bg-indigo-500/5 p-3.5 text-xs text-indigo-600 dark:text-indigo-400"
                data-testid="imported-badge"
            >
                <Import class="size-4" />
            </div>
        </AppTooltipWrapper>
    </div>
</template>
