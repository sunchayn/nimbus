const SECRET_NAME = /token|secret|password|api[-_]?key|authorization|cookie|csrf|xsrf/i;

const toPlaceholder = (name: string): string =>
    `YOUR_${name
        .replace(/[^a-z0-9]+/gi, '_')
        .replace(/^_|_$/g, '')
        .toUpperCase()}`;

/**
 * Replaces the value with a placeholder when the name looks like a secret.
 */
export const maskValue = (key: string, value: string): string =>
    SECRET_NAME.test(key) ? toPlaceholder(key) : value;

/**
 * Masks the string values of a JSON structure by the name of their property.
 */
export const maskJson = (value: unknown, key = ''): unknown => {
    if (Array.isArray(value)) {
        return value.map(item => maskJson(item, key));
    }

    if (value !== null && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value).map(([name, item]) => [name, maskJson(item, name)]),
        );
    }

    return typeof value === 'string' ? maskValue(key, value) : value;
};
