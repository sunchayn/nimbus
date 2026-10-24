<script setup lang="ts">
/**
 * @component HistoryItem
 * @description A single entry in the request history list.
 */
import StatusIndicator from '@/components/domain/Client/Response/ResponseStatus/StatusIndicator.vue';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import { type RequestLog } from '@/interfaces/history/logs';
import { type Response, STATUS } from '@/interfaces/http';
import { useTimeAgo } from '@vueuse/core';
import prettyBytes from 'pretty-bytes';
import prettyMs from 'pretty-ms';
import { computed } from 'vue';

/*
 * Types & Interfaces.
 */

export interface HistoryItemProps {
    log: RequestLog & {
        response: Response;
    };
    active?: boolean;
}

/*
 * Component Setup.
 */

const props = defineProps<HistoryItemProps>();

// Endpoints are stored without the leading slash.
const endpoint = computed(() => `/${props.log.request.endpoint.replace(/^\//, '')}`);

const timeAgo = useTimeAgo(computed(() => new Date(props.log.response.timestamp * 1000)));
</script>

<template>
    <button
        type="button"
        :aria-current="active ? 'true' : undefined"
        class="hover:bg-elevated focus-visible:bg-elevated data-[active=true]:bg-elevated grid w-full min-w-0 grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-2 gap-y-1 px-3 py-2 text-start focus-visible:outline-none"
        :data-active="active"
    >
        <div class="w-verb">
            <HttpVerbLabel
                :method="props.log.request.method"
                data-testid="history-item-method"
            />
        </div>
        <span
            class="text-default truncate font-mono text-xs"
            :title="props.log.request.endpoint"
            data-testid="history-item-endpoint"
        >
            {{ endpoint }}
        </span>
        <small class="text-muted text-xxs whitespace-nowrap">{{ timeAgo }}</small>

        <span
            class="text-muted text-xxs gap-x-tight col-span-2 col-start-1 flex min-w-0 flex-wrap items-center font-mono"
        >
            <StatusIndicator
                :status="props.log.response.status ?? STATUS.EMPTY"
                class="shrink-0"
                data-testid="history-item-status"
            />
            <span data-testid="response-status-badge">
                {{ props.log.response.statusCode }} -
                {{ props.log.response.statusText }}
            </span>
            <span aria-hidden="true">·</span>
            <span>{{ prettyMs(props.log.durationInMs, { compact: true }) }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ prettyBytes(props.log.response.sizeInBytes) }}</span>
        </span>
    </button>
</template>
