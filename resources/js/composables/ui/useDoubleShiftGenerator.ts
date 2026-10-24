import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { useValueGeneratorStore } from '@/stores';

const SHIFT_PRESS_THRESHOLD_MS = 500;

/**
 * Returns a keydown handler that opens the value generator on a double Shift press.
 * The getter supplies the input element the generated value is written to.
 */
export const useDoubleShiftGenerator = (
    getInput: () => HTMLElement | null | undefined,
): ((event: KeyboardEvent) => void) => {
    const { openCommand } = useValueGeneratorStore();
    let lastShiftPress = 0;

    return (event: KeyboardEvent): void => {
        if (event.key !== 'Shift') {
            return;
        }

        const now = Date.now();

        if (now - lastShiftPress < SHIFT_PRESS_THRESHOLD_MS) {
            event.preventDefault();
            openCommand(
                getInput() ?? undefined,
                ValueGeneratorCommandOpenMethod.SHIFT_SHIFT,
            );
        }

        lastShiftPress = now;
    };
};
