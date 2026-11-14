import type { InsightGate } from '@/interfaces/http';

/**
 * Puts denied gates first, so the reason of a 403 is the first thing shown.
 * The order inside each part is kept.
 */
export const deniedFirst = (gates: InsightGate[]): InsightGate[] => [
    ...gates.filter(gate => gate.result === false),
    ...gates.filter(gate => gate.result !== false),
];
