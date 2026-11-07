import type { RequestLog } from '@/interfaces/history/logs';
import { useSettingsStore } from '@/stores';
import { formDataSerializer } from '@/utils/stores/formDataSerializer';
import { defineStore } from 'pinia';
import { computed, ref, shallowRef } from 'vue';

// Local storage allows about 5 MB for the whole origin, so history gets a share of it.
export const HISTORY_MAX_CHARS = 2_000_000;

export const useRequestsHistoryStore = defineStore(
    'requestHistory',
    () => {
        /*
         * Stores & dependencies.
         */
        const settingsStore = useSettingsStore();

        // State
        const logs = ref<RequestLog[]>([]);
        const activeLogIndex = shallowRef<number | null>(null);

        // Computed
        const maxLogs = computed(() => settingsStore.preferences.maxHistoryLogs);
        const allLogs = computed(() => logs.value);
        const lastLog = computed(() => {
            if (activeLogIndex.value !== null && logs.value[activeLogIndex.value]) {
                return logs.value[activeLogIndex.value];
            }

            return logs.value[logs.value.length - 1] ?? null;
        });

        // Actions
        // Drops the oldest log that is not pinned, and tells whether one was found.
        const dropOldestUnpinned = (): boolean => {
            const index = logs.value.findIndex(log => !log.pinned);

            if (index === -1) {
                return false;
            }

            logs.value = logs.value.filter((_, position) => position !== index);

            return true;
        };

        const addLog = (log: RequestLog) => {
            logs.value.push(log);

            const isOverBudget = () =>
                logs.value.length > maxLogs.value ||
                (logs.value.length > 1 &&
                    JSON.stringify(logs.value).length > HISTORY_MAX_CHARS);

            while (isOverBudget()) {
                if (!dropOldestUnpinned()) {
                    break;
                }
            }

            activeLogIndex.value = null;
        };

        const togglePin = (log: RequestLog) => {
            log.pinned = !log.pinned;
        };

        const setActiveLog = (index: number | null) => {
            activeLogIndex.value = index;
        };

        const clearLogs = () => {
            logs.value = [];
        };

        return {
            // State
            logs,
            maxLogs,
            activeLogIndex,

            // Getters
            allLogs,
            lastLog,

            // Actions
            addLog,
            togglePin,
            clearLogs,
            setActiveLog,
        };
    },
    {
        persist: { serializer: formDataSerializer },
    },
);
