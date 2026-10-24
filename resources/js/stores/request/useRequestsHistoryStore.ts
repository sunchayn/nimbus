import type { RequestLog } from '@/interfaces/history/logs';
import { useSettingsStore } from '@/stores';
import { defineStore } from 'pinia';
import { computed, ref, shallowRef } from 'vue';

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
        const addLog = (log: RequestLog) => {
            logs.value.push(log);

            if (logs.value.length > maxLogs.value) {
                logs.value = logs.value.slice(-maxLogs.value);
            }

            activeLogIndex.value = null;
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
            clearLogs,
            setActiveLog,
        };
    },
    {
        persist: true,
    },
);
