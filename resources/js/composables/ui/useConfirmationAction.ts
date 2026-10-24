import { onScopeDispose, reactive } from 'vue';

export interface UseConfirmationActionOptions {
    duration?: number;
}

export interface UseConfirmationActionResult {
    isConfirming: (id?: string | number) => boolean;
    trigger: (callback: () => void, id?: string | number) => void;
    cancel: (id?: string | number) => void;
    cancelAll: () => void;
}

/**
 * Manages two-step confirmation states for actions.
 *
 * Supports both standalone single-action confirmation and multiple
 * independent confirmations identified by unique keys.
 */
export const useConfirmationAction = (
    options: UseConfirmationActionOptions = {},
): UseConfirmationActionResult => {
    const { duration = 3000 } = options;

    const timers = new Map<string | number, number>();
    const confirmationStates = reactive(new Set<string | number>());

    /**
     * Checks if a specific action (or the default) is in the confirmation state.
     */
    const isConfirming = (id: string | number = 'default') => confirmationStates.has(id);

    /**
     * Cancels the confirmation state for a specific action.
     */
    const cancel = (id: string | number = 'default') => {
        const timeoutId = timers.get(id);

        if (timeoutId) {
            window.clearTimeout(timeoutId);
            timers.delete(id);
        }

        confirmationStates.delete(id);
    };

    /**
     * Triggers the confirmation state or executes the callback if already confirming.
     */
    const trigger = (callback: () => void, id: string | number = 'default') => {
        if (confirmationStates.has(id)) {
            callback();
            cancel(id);

            return;
        }

        confirmationStates.add(id);

        const timeoutId = window.setTimeout(() => {
            cancel(id);
        }, duration);

        timers.set(id, timeoutId);
    };

    /**
     * Cancels every pending confirmation and its timer.
     */
    const cancelAll = () => {
        timers.forEach(timeoutId => window.clearTimeout(timeoutId));
        timers.clear();
        confirmationStates.clear();
    };

    onScopeDispose(cancelAll);

    return {
        isConfirming,
        trigger,
        cancel,
        cancelAll,
    };
};
