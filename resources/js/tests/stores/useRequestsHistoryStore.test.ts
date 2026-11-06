import type { RequestLog } from '@/interfaces/history/logs';
import type { Request } from '@/interfaces/http';
import { useSettingsStore } from '@/stores/core/useSettingsStore';
import {
    HISTORY_MAX_CHARS,
    useRequestsHistoryStore,
} from '@/stores/request/useRequestsHistoryStore';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

describe('useRequestsHistoryStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        const settings = useSettingsStore();
        settings.updatePreference('maxHistoryLogs', 2);
    });

    /*
     * Initialization tests.
     */

    describe('Log Management', () => {
        it('adds logs and enforces max history size', () => {
            // Arrange

            const store = useRequestsHistoryStore();
            const logs: RequestLog[] = [
                {
                    durationInMs: 10,
                    isProcessing: false,
                    request: {} as unknown as Request,
                },
                {
                    durationInMs: 20,
                    isProcessing: false,
                    request: {} as unknown as Request,
                },
                {
                    durationInMs: 30,
                    isProcessing: false,
                    request: {} as unknown as Request,
                },
            ];

            // Act

            logs.forEach(log => store.addLog(log));

            // Assert

            expect(store.allLogs).toHaveLength(2);
            expect(store.lastLog?.durationInMs).toBe(30);
        });

        it('drops the oldest logs when the stored size exceeds the budget', () => {
            // Arrange

            const store = useRequestsHistoryStore();
            useSettingsStore().updatePreference('maxHistoryLogs', 30);
            const big = 'x'.repeat(300_000);

            // Act

            for (let i = 1; i <= 30; i++) {
                store.addLog({
                    durationInMs: i,
                    isProcessing: false,
                    request: { big } as unknown as Request,
                });
            }

            // Assert

            expect(JSON.stringify(store.allLogs).length).toBeLessThanOrEqual(
                HISTORY_MAX_CHARS,
            );
            expect(store.allLogs.length).toBeLessThan(30);
            expect(store.lastLog?.durationInMs).toBe(30);
        });

        it('clears logs when requested', () => {
            // Arrange

            const store = useRequestsHistoryStore();
            store.addLog({
                durationInMs: 10,
                isProcessing: false,
                request: {} as unknown as Request,
            });

            // Act

            store.clearLogs();

            // Assert

            expect(store.allLogs).toEqual([]);
            expect(store.lastLog).toBeNull();
        });
    });

    /*
     * State Transition tests.
     */

    describe('Active Log State', () => {
        it('can set and reset an active log index', () => {
            // Arrange

            const store = useRequestsHistoryStore();
            const logs: RequestLog[] = [
                {
                    durationInMs: 10,
                    isProcessing: false,
                    request: { method: 'GET' } as unknown as Request,
                },
                {
                    durationInMs: 20,
                    isProcessing: false,
                    request: { method: 'POST' } as unknown as Request,
                },
            ];
            logs.forEach(log => store.addLog(log));

            // Act & Assert

            expect(store.lastLog?.durationInMs).toBe(20);

            store.setActiveLog(0);
            expect(store.activeLogIndex).toBe(0);
            expect(store.lastLog?.durationInMs).toBe(10);

            store.setActiveLog(null);
            expect(store.activeLogIndex).toBe(null);
            expect(store.lastLog?.durationInMs).toBe(20);
        });

        it('resets activeLogIndex when a new log is added', () => {
            // Arrange

            const store = useRequestsHistoryStore();
            store.addLog({
                durationInMs: 10,
                isProcessing: false,
                request: {} as unknown as Request,
            });
            store.setActiveLog(0);

            // Act

            store.addLog({
                durationInMs: 20,
                isProcessing: false,
                request: {} as unknown as Request,
            });

            // Assert

            expect(store.activeLogIndex).toBeNull();
            expect(store.lastLog?.durationInMs).toBe(20);
        });
    });
});
