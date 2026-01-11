import { RequestLog } from '@/interfaces/history/logs';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { useSettingsStore } from '../core/useSettingsStore';

export const useRequestsHistoryStore = defineStore(
    'requestHistory',
    () => {
        /*
         * Stores & dependencies.
         */
        const settingsStore = useSettingsStore();

        // State
        const logs = ref<RequestLog[]>([]);

        // Computed
        const maxLogs = computed(() => settingsStore.preferences.maxHistoryLogs);

        // Computed
        const allLogs = computed(() => logs.value);
        const lastLog = computed(() => logs.value[logs.value.length - 1] ?? null);
        const totalRequests = computed(() => logs.value.length);

        // Actions
        const addLog = (log: RequestLog) => {
            logs.value.push(log);

            // Maintain max logs limit
            if (logs.value.length > maxLogs.value) {
                logs.value = logs.value.slice(-maxLogs.value);
            }
        };

        const clearLogs = () => {
            logs.value = [];
        };

        return {
            // State
            logs,
            maxLogs,

            // Getters
            allLogs,
            lastLog,
            totalRequests,

            // Actions
            addLog,
            clearLogs,
        };
    },
    {
        persist: true,
    },
);
