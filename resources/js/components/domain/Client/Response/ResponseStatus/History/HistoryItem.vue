<script setup lang="ts">
/**
 * @component HistoryItem
 * @description A single item in the request history dropdown.
 */
import { AppDropdownMenuItem } from '@/components/base/dropdown-menu';
import StatusIndicator from '@/components/domain/Client/Response/ResponseStatus/StatusIndicator.vue';
import HttpVerbLabel from '@/components/domain/HttpVerbLabel/HttpVerbLabel.vue';
import { type RequestLog } from '@/interfaces/history/logs';
import { type Response, STATUS } from '@/interfaces/http';
import { useTimeAgo } from '@vueuse/core';
import prettyBytes from 'pretty-bytes';
import prettyMs from 'pretty-ms';

/*
 * Types & Interfaces.
 */

export interface AppHistoryItemProps {
    log: RequestLog & {
        response: Response;
    };
    index: number;
}

export interface AppHistoryItemEmits {
    (e: 'select', index: number): void;
}

/*
 * Component Setup.
 */

const props = defineProps<AppHistoryItemProps>();

const emit = defineEmits<AppHistoryItemEmits>();

/*
 * Methods.
 */

const timeToTimeAgo = (timestamp: number): string => {
    const timeAgo = useTimeAgo(new Date(timestamp * 1000));

    return timeAgo.value;
};
</script>

<template>
    <AppDropdownMenuItem
        class="p-panel p-panel flex cursor-pointer flex-col items-start transition-colors"
        @select="emit('select', props.index)"
    >
        <div class="flex w-full gap-2">
            <div class="flex flex-1 flex-col gap-2">
                <div
                    class="flex w-full justify-between gap-1 leading-tight"
                    :title="props.log.request.endpoint"
                >
                    <div class="flex-1 truncate">
                        <HttpVerbLabel
                            :method="props.log.request.method"
                            data-testid="history-item-method"
                        />

                        <span class="ml-1 text-xs" data-testid="history-item-endpoint">
                            {{ props.log.request.endpoint }}
                        </span>
                    </div>

                    <StatusIndicator
                        :status="props.log.response.status ?? STATUS.EMPTY"
                        data-testid="history-item-status"
                    />
                </div>
                <div class="text-xxs flex min-w-0 flex-1 items-center gap-2">
                    <span class="text-nowrap" data-testid="response-status-badge">
                        {{ props.log.response.statusCode }}
                        -
                        {{ props.log.response.statusText }}
                    </span>

                    <div class="border-border w-8 border-b"></div>

                    <div class="flex w-full items-center justify-between">
                        <div>
                            <span class="text-subtle whitespace-nowrap">
                                {{
                                    prettyMs(props.log.durationInMs, {
                                        compact: true,
                                    })
                                }}
                            </span>
                            <span
                                v-if="props.log.response"
                                class="text-subtle text-xxs whitespace-nowrap"
                            >
                                &nbsp;/
                                {{ prettyBytes(props.log.response.sizeInBytes) }}
                            </span>
                        </div>

                        <small class="text-subtle text-xxs whitespace-nowrap">
                            {{ timeToTimeAgo(props.log.response.timestamp) }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </AppDropdownMenuItem>
</template>
