import { useAppToast } from '@/components/base/toast';

interface SlimStep {
    key: string;
    // Returns the same text when there is nothing left to remove.
    slim: (raw: string) => string;
}

/**
 * Inactive tabs lose their stored response first.
 */
const dropInactiveResponses = (raw: string): string => {
    const state = JSON.parse(raw);

    state.tabs = state.tabs.map((tab: { id: string }) =>
        tab.id === state.activeTabId ? tab : { ...tab, response: null },
    );

    return JSON.stringify(state);
};

/**
 * History loses its older half, and the latest log always stays.
 */
const dropOldestLogs = (raw: string): string => {
    const state = JSON.parse(raw);

    if (state.logs.length <= 1) {
        return raw;
    }

    state.logs = state.logs.slice(Math.ceil(state.logs.length / 2));

    return JSON.stringify(state);
};

const STEPS: SlimStep[] = [
    { key: 'nimbus:tabs', slim: dropInactiveResponses },
    { key: 'nimbus:requestHistory', slim: dropOldestLogs },
];

let notified = false;

const trySet = (key: string, value: string): boolean => {
    try {
        window.localStorage.setItem(key, value);

        return true;
    } catch {
        return false;
    }
};

/**
 * Slims one stored entry, or the value being written when it is that entry.
 * Returns null when nothing could be removed.
 */
const slimStep = (step: SlimStep, key: string, value: string): string | null => {
    const writingStep = step.key === key;
    const source = writingStep ? value : window.localStorage.getItem(step.key);

    if (!source) {
        return null;
    }

    try {
        const slimmed = step.slim(source);

        if (slimmed === source) {
            return null;
        }

        if (!writingStep) {
            trySet(step.key, slimmed);
        }

        return writingStep ? slimmed : value;
    } catch {
        return null;
    }
};

const notifyOnce = (): void => {
    if (notified) {
        return;
    }

    notified = true;

    useAppToast().warning('Browser storage is full', {
        description: 'Older responses were removed to make room.',
    });
};

/**
 * Local storage wrapper that frees space instead of failing when the quota is hit.
 */
export const quotaSafeStorage = {
    getItem: (key: string): string | null => window.localStorage.getItem(key),

    setItem: (key: string, value: string): void => {
        if (trySet(key, value)) {
            return;
        }

        let current = value;

        for (const step of STEPS) {
            let next = slimStep(step, key, current);

            while (next !== null) {
                current = next;

                if (trySet(key, current)) {
                    notifyOnce();

                    return;
                }

                next = slimStep(step, key, current);
            }
        }

        notifyOnce();
    },
};

export const resetQuotaNotice = (): void => {
    notified = false;
};
