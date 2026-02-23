/**
 * Generate a UUID v4 string.
 *
 * Uses crypto.randomUUID() when available (secure contexts),
 * falls back to crypto.getRandomValues() otherwise (e.g. HTTP).
 */
export function generateId(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (c) =>
        (+c ^ (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (+c / 4)))).toString(16),
    );
}
