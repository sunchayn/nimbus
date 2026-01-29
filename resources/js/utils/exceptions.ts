import type { GlobalException } from '@/interfaces';

/**
 * Parses global exception from window.Nimbus.
 */
export function parseGlobalException(
    exceptionData: string | null,
): GlobalException | null {
    if (typeof exceptionData !== 'string') {
        return null;
    }

    try {
        return JSON.parse(exceptionData);
    } catch {
        return null;
    }
}
