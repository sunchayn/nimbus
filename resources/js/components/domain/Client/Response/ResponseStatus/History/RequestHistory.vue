<script setup lang="ts">
/**
 * @component RequestHistory
 * @description A dropdown menu showing the history of requests made to the current endpoint.
 */
import { AppButton } from '@/components/base/button';
import {
    AppDropdownMenu,
    AppDropdownMenuContent,
    AppDropdownMenuSeparator,
    AppDropdownMenuTrigger,
} from '@/components/base/dropdown-menu';
import {
    AppInputGroup,
    AppInputGroupAddon,
    AppInputGroupInput,
} from '@/components/base/input-group';
import { AppScrollArea } from '@/components/base/scroll-area';
import HistoryItem from '@/components/domain/Client/Response/ResponseStatus/History/HistoryItem.vue';
import { type RequestLog } from '@/interfaces/history/logs';
import { type Response } from '@/interfaces/http';
import { useRequestsHistoryStore, useRequestStore } from '@/stores';
import { cn } from '@/utils/ui';
import { useTimeAgo } from '@vueuse/core';
import { HistoryIcon, Search, Trash2Icon } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

/*
 * Types & Interfaces.
 */

export interface AppRequestHistoryProps {}

/*
 * Component Setup.
 */

defineProps<AppRequestHistoryProps>();

/*
 * Stores.
 */

const requestStore = useRequestStore();
const historyStore = useRequestsHistoryStore();

/*
 * State.
 */

const isOpen = ref(false);
const searchQuery = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);
const isConfirmingClear = ref(false);
const clearHistoryTimeoutId = ref<number | null>(null);

/*
 * Computed & Methods.
 */

const lastLog = computed(() => historyStore.lastLog);

const timeToTimeAgo = (timestamp: number): string => {
    const timeAgo = useTimeAgo(new Date(timestamp * 1000));

    return timeAgo.value;
};

const readableTime = computed(() => {
    if (lastLog.value?.response === undefined) {
        return '';
    }

    return timeToTimeAgo(lastLog.value.response.timestamp);
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

const selectHistoryItem = (index: number) => {
    const log = historyStore.allLogs[index];

    if (!log) {
        return;
    }

    historyStore.setActiveLog(index);

    requestStore.restoreFromHistory(log);
};

const reversedLogs = computed(() => {
    return [...historyStore.allLogs]
        .filter(log => log.response !== undefined)
        .reverse() as (RequestLog & { response: Response })[];
});

const filteredLogs = computed(() => {
    if (!searchQuery.value.trim()) {
        return reversedLogs.value;
    }

    const query = searchQuery.value.toLowerCase();

    return reversedLogs.value.filter(log =>
        log.request.endpoint.toLowerCase().includes(query),
    );
});

const getOriginalIndex = (reversedIndex: number) => {
    const logsWithResponse = historyStore.allLogs.filter(
        log => log.response !== undefined,
    );
    const originalLog = logsWithResponse[logsWithResponse.length - 1 - reversedIndex];

    return historyStore.allLogs.indexOf(originalLog);
};

const resetClearConfirmation = () => {
    isConfirmingClear.value = false;
    if (clearHistoryTimeoutId.value) {
        window.clearTimeout(clearHistoryTimeoutId.value);
        clearHistoryTimeoutId.value = null;
    }
};

const handleClearHistory = () => {
    if (isConfirmingClear.value) {
        historyStore.clearLogs();
        resetClearConfirmation();
        isOpen.value = false;

        return;
    }

    isConfirmingClear.value = true;

    if (clearHistoryTimeoutId.value) {
        window.clearTimeout(clearHistoryTimeoutId.value);
    }

    clearHistoryTimeoutId.value = window.setTimeout(() => {
        resetClearConfirmation();
    }, 1000);
};

/*
 * Watchers.
 */

// Auto-focus search input when dropdown opens
watch(isOpen, async newValue => {
    if (newValue) {
        await nextTick();
        searchInputRef.value?.focus();
    } else {
        // Clear search when dropdown closes
        searchQuery.value = '';
    }
});
</script>

<template>
    <AppDropdownMenu v-if="reversedLogs.length" v-model:open="isOpen">
        <AppDropdownMenuTrigger as-child>
            <AppButton
                variant="ghost"
                size="xs"
                class="rounded px-1 transition-colors hover:bg-zinc-100 focus:outline-none focus-visible:ring-0 dark:hover:bg-zinc-800 dark:focus-visible:ring-0"
                data-testid="response-history-trigger"
            >
                <small class="text-subtle-foreground text-xs" :title="absoluteTime">
                    {{ readableTime }}
                </small>
                <HistoryIcon class="size-3" />
            </AppButton>
        </AppDropdownMenuTrigger>
        <AppDropdownMenuContent align="end" class="w-sm p-0">
            <AppScrollArea class="max-h-96 overflow-y-auto">
                <div class="px-panel my-2 flex gap-2">
                    <AppButton
                        variant="outline"
                        size="xs"
                        class="h-sub-toolbar justify-start shadow-none transition-colors"
                        :class="
                            cn(
                                isConfirmingClear &&
                                    'text-rose-500 hover:text-rose-600 dark:text-rose-400 dark:hover:text-rose-300',
                            )
                        "
                        data-testid="clear-history-button"
                        @click="handleClearHistory"
                    >
                        <Trash2Icon class="size-3" />
                        Clear History
                    </AppButton>
                    <AppInputGroup class="h-sub-toolbar">
                        <AppInputGroupInput
                            ref="searchInputRef"
                            v-model="searchQuery"
                            placeholder="Type to search"
                            data-testid="history-search-input"
                        />
                        <AppInputGroupAddon>
                            <Search class="size-3" />
                        </AppInputGroupAddon>
                    </AppInputGroup>
                </div>

                <AppDropdownMenuSeparator />

                <template v-if="filteredLogs.length">
                    <template
                        v-for="(log, index) in filteredLogs"
                        :key="log.request.endpoint + log.response.timestamp"
                    >
                        <HistoryItem
                            :log="log"
                            :index="getOriginalIndex(reversedLogs.indexOf(log))"
                            data-testid="history-item"
                            :data-endpoint="log.request.endpoint"
                            :data-method="log.request.method"
                            @select="selectHistoryItem"
                        />

                        <AppDropdownMenuSeparator
                            v-if="index < filteredLogs.length - 1"
                        />
                    </template>
                </template>

                <div
                    v-else
                    class="text-subtle-foreground flex flex-col items-center justify-center gap-2 py-8 text-center text-sm"
                    data-testid="history-empty-state"
                >
                    <Search class="size-8 opacity-20" />
                    <p>No results found matching your keyword</p>
                </div>
            </AppScrollArea>
        </AppDropdownMenuContent>
    </AppDropdownMenu>
</template>
