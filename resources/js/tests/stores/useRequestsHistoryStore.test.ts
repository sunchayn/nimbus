import { RequestLog } from '@/interfaces/history/logs';
import { useSettingsStore } from '@/stores/core/useSettingsStore';
import { useRequestsHistoryStore } from '@/stores/request/useRequestsHistoryStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

describe('useRequestsHistoryStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        const settings = useSettingsStore();
        settings.updatePreference('maxHistoryLogs', 2);
    });

    it('adds logs and enforces max history size', () => {
        const store = useRequestsHistoryStore();

        /* eslint-disable  @typescript-eslint/no-explicit-any */
        const logs: RequestLog[] = [
            { durationInMs: 10, isProcessing: false, request: {} as any },
            { durationInMs: 20, isProcessing: false, request: {} as any },
            { durationInMs: 30, isProcessing: false, request: {} as any },
        ];
        /* eslint-enable  @typescript-eslint/no-explicit-any */

        logs.forEach(log => store.addLog(log));

        expect(store.allLogs).toHaveLength(2);
        expect(store.lastLog?.durationInMs).toBe(30);
        expect(store.totalRequests).toBe(2);
    });

    it('clears logs when requested', () => {
        const store = useRequestsHistoryStore();

        /* eslint-disable  @typescript-eslint/no-explicit-any */
        store.addLog({ durationInMs: 10, isProcessing: false, request: {} as any });
        /* eslint-enable  @typescript-eslint/no-explicit-any */

        store.clearLogs();

        expect(store.allLogs).toEqual([]);
        expect(store.lastLog).toBeNull();
    });
});
