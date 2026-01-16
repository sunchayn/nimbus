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

    it('can set and reset an active log index', () => {
        const store = useRequestsHistoryStore();

        /* eslint-disable  @typescript-eslint/no-explicit-any */
        const logs: RequestLog[] = [
            { durationInMs: 10, isProcessing: false, request: { method: 'GET' } as any },
            { durationInMs: 20, isProcessing: false, request: { method: 'POST' } as any },
        ];
        /* eslint-enable  @typescript-eslint/no-explicit-any */

        logs.forEach(log => store.addLog(log));

        expect(store.lastLog?.durationInMs).toBe(20);

        store.setActiveLog(0);
        expect(store.activeLogIndex).toBe(0);
        expect(store.lastLog?.durationInMs).toBe(10);

        store.setActiveLog(null);
        expect(store.activeLogIndex).toBe(null);
        expect(store.lastLog?.durationInMs).toBe(20);
    });

    it('resets activeLogIndex when a new log is added', () => {
        const store = useRequestsHistoryStore();

        /* eslint-disable  @typescript-eslint/no-explicit-any */
        store.addLog({ durationInMs: 10, isProcessing: false, request: {} as any });
        store.setActiveLog(0);

        expect(store.activeLogIndex).toBe(0);

        store.addLog({ durationInMs: 20, isProcessing: false, request: {} as any });
        /* eslint-enable  @typescript-eslint/no-explicit-any */

        expect(store.activeLogIndex).toBeNull();
        expect(store.lastLog?.durationInMs).toBe(20);
    });
});
