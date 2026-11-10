<script setup lang="ts">
/**
 * @component RequestHistory
 * @description A popover listing the requests sent so far, with search and a clear action.
 */
import { AppButton } from '@/components/base/button';
import { AppIcon } from '@/components/base/icons';
import { AppPopover } from '@/components/base/popover';
import GeneratorInput from '@/components/common/GeneratorInput.vue';
import HistoryItem from '@/components/domain/Client/Response/ResponseStatus/History/HistoryItem.vue';
import { useConfirmationAction } from '@/composables/ui/useConfirmationAction';
import { useShortTimeAgo } from '@/composables/ui/useShortTimeAgo';
import { type RequestLog } from '@/interfaces/history/logs';
import { type Response } from '@/interfaces/http';
import { useRequestsHistoryStore, useRequestStore } from '@/stores';
import {
    logMatchesQuery,
    logMatchesStatusClasses,
    STATUS_CLASSES,
    type StatusClass,
} from '@/utils/history/historyFilter';
import { computed, nextTick, shallowRef, useTemplateRef, watch } from 'vue';

/*
 * Stores.
 */

const requestStore = useRequestStore();
const historyStore = useRequestsHistoryStore();

/*
 * State.
 */

const isOpen = shallowRef(false);
const searchQuery = shallowRef('');
const statusClasses = shallowRef<StatusClass[]>([]);
const searchInputRef =
    useTemplateRef<InstanceType<typeof GeneratorInput>>('searchInputRef');
const {
    isConfirming: isConfirmingClear,
    trigger: triggerClear,
    cancel: cancelClearConfirmation,
} = useConfirmationAction({ duration: 1000 });

/*
 * Computed & Methods.
 */

type LogWithResponse = RequestLog & { response: Response };

const lastTimestamp = computed(() => {
    const timestamp = historyStore.lastLog?.response?.timestamp;

    return timestamp === undefined ? null : new Date(timestamp * 1000);
});

const timeAgo = useShortTimeAgo(computed(() => lastTimestamp.value ?? new Date()));

const absoluteTime = computed(() =>
    lastTimestamp.value?.toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }),
);

const logs = computed(() =>
    [...historyStore.allLogs]
        .filter((log): log is LogWithResponse => log.response !== undefined)
        .reverse(),
);

// Pinned logs come first, and each group keeps the newest log on top.
const filteredLogs = computed(() => {
    const matching = logs.value.filter(
        log =>
            logMatchesQuery(log, searchQuery.value) &&
            logMatchesStatusClasses(log, statusClasses.value),
    );

    return [
        ...matching.filter(log => log.pinned),
        ...matching.filter(log => !log.pinned),
    ];
});

const toggleStatusClass = (statusClass: StatusClass) => {
    statusClasses.value = statusClasses.value.includes(statusClass)
        ? statusClasses.value.filter(item => item !== statusClass)
        : [...statusClasses.value, statusClass];
};

const selectLog = (log: LogWithResponse) => {
    historyStore.setActiveLog(historyStore.allLogs.indexOf(log));
    requestStore.restoreFromHistory(log);
    isOpen.value = false;
};

const isActive = (log: LogWithResponse) => historyStore.lastLog === log;

const handleClearHistory = () => {
    triggerClear(() => {
        historyStore.clearLogs();
        isOpen.value = false;
    });
};

/*
 * Watchers.
 */

watch(isOpen, async open => {
    if (open) {
        await nextTick();
        searchInputRef.value?.inputRef?.focus();
    } else {
        searchQuery.value = '';
        statusClasses.value = [];
        cancelClearConfirmation();
    }
});
</script>

<template>
    <AppPopover
        v-if="logs.length"
        v-model:open="isOpen"
        :content="{ align: 'end', sideOffset: 12, collisionPadding: 8 }"
        :ui="{
            content:
                'flex w-screen max-h-(--reka-popover-content-available-height) max-w-popover flex-col p-0',
        }"
    >
        <AppButton
            variant="ghost"
            color="neutral"
            size="sm"
            icon="i-hugeicons-history"
            aria-label="Request history"
            trailing
            data-testid="response-history-trigger"
        >
            <small class="text-muted text-xs @max-md:hidden" :title="absoluteTime">
                {{ timeAgo }}
            </small>
        </AppButton>

        <template #content>
            <div class="border-default gap-tight p-tight flex items-center border-b">
                <GeneratorInput
                    ref="searchInputRef"
                    v-model="searchQuery"
                    variant="none"
                    size="sm"
                    class="min-w-0 flex-1"
                    leading-icon="i-hugeicons-search-01"
                    placeholder="Search path, status or body..."
                    data-testid="history-search-input"
                />
                <AppButton
                    variant="ghost"
                    size="sm"
                    :color="isConfirmingClear() ? 'error' : 'neutral'"
                    icon="i-hugeicons-delete-02"
                    :label="isConfirmingClear() ? 'Confirm' : 'Clear'"
                    data-testid="clear-history-button"
                    @click="handleClearHistory"
                />
            </div>

            <div
                class="border-default gap-tight p-tight flex items-center border-b text-xs"
                data-testid="history-status-filters"
            >
                <button
                    v-for="statusClass in STATUS_CLASSES"
                    :key="statusClass"
                    type="button"
                    class="border-default px-tight py-tight rounded-sm border"
                    :class="{
                        'bg-elevated text-highlighted':
                            statusClasses.includes(statusClass),
                    }"
                    :aria-pressed="statusClasses.includes(statusClass)"
                    :data-testid="`history-filter-${statusClass}`"
                    @click="toggleStatusClass(statusClass)"
                >
                    {{ statusClass }}
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                <ul v-if="filteredLogs.length" class="divide-default divide-y">
                    <li
                        v-for="log in filteredLogs"
                        :key="log.response.timestamp + log.request.endpoint"
                        class="group relative"
                    >
                        <HistoryItem
                            :log="log"
                            :active="isActive(log)"
                            data-testid="history-item"
                            :data-endpoint="log.request.endpoint"
                            :data-method="log.request.method"
                            @click="selectLog(log)"
                        />
                        <button
                            type="button"
                            class="end-base text-muted hover:text-highlighted bottom-base absolute focus-visible:opacity-100"
                            :class="
                                log.pinned
                                    ? 'text-highlighted'
                                    : 'opacity-0 group-hover:opacity-100'
                            "
                            :aria-label="log.pinned ? 'Unpin request' : 'Pin request'"
                            :aria-pressed="!!log.pinned"
                            data-testid="history-pin-toggle"
                            @click.stop="historyStore.togglePin(log)"
                        >
                            <AppIcon name="i-hugeicons-pin" class="size-icon-xs" />
                        </button>
                    </li>
                </ul>
                <div
                    v-else
                    class="text-muted gap-base flex flex-col items-center p-6 text-center text-sm"
                    data-testid="history-empty-state"
                >
                    <AppIcon name="i-hugeicons-search-01" class="size-8 opacity-20" />
                    <p>No results found matching your keyword</p>
                </div>
            </div>
        </template>
    </AppPopover>
</template>
