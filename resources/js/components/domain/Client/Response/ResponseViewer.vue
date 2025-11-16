<script setup lang="ts">
import ResponseStatus from '@/components/domain/Client/Response/ResponseStatus/ResponseStatus.vue';
import ResponseViewerEmptyState from '@/components/domain/Client/Response/ResponseViewerEmptyState.vue';
import ResponseViewerInternalError from '@/components/domain/Client/Response/ResponseViewerErrorState.vue';
import ResponseViewerResponse from '@/components/domain/Client/Response/ResponseViewerResponse.vue';
import { useRequestsHistoryStore } from '@/stores';
import { cn } from '@/utils/ui';
import { PrimitiveProps } from 'reka-ui';
import { computed, HTMLAttributes } from 'vue';

interface ResponseViewerProps extends PrimitiveProps {
    class?: HTMLAttributes['class'];
}

const props = defineProps<ResponseViewerProps>();

const historyStore = useRequestsHistoryStore();
const lastLog = computed(() => historyStore.lastLog);
</script>

<template>
    <div :class="cn('bg-background flex h-full flex-col', props.class)">
        <ResponseStatus class="border-b" />
        <ResponseViewerEmptyState v-if="!lastLog" data-testid="response-empty" />
        <ResponseViewerInternalError
            v-else-if="lastLog.error"
            data-testid="response-error"
            :error="lastLog.error"
        />
        <ResponseViewerResponse v-else data-testid="response-content" />
    </div>
</template>
