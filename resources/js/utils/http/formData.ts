/**
 * Converts an object payload to FormData format:
 *
 * - Arrays: Uses indexed notation (key[0], key[1])
 * - Nested objects: Uses bracket notation (key[sub-key])
 * - Blobs: Preserves binary data
 * - Primitives: Converts to strings
 * - Null values: Converts to empty strings
 */
export const convertPayloadToFormData = (payload: object): FormData => {
    const formData = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        appendValueRecursively(formData, key, value);
    });

    return formData;
};

/**
 * Recursively appends values to FormData with proper key formatting.
 */
const appendValueRecursively = (
    formData: FormData,
    key: string,
    value: Blob | object | string | number | boolean | null,
): void => {
    // Handle arrays with indexed notation (e.g. `key[1]`).
    if (Array.isArray(value)) {
        value.forEach((item, index) => {
            appendValueRecursively(formData, `${key}[${index}]`, item);
        });

        return;
    }

    // Handle blobs directly (preserve binary data)
    if (value instanceof Blob) {
        formData.append(key, value);

        return;
    }

    // Handle nested objects with bracket notation (e.g. `key[sub-key]`)
    if (value && typeof value === 'object') {
        const entries =
            value instanceof FormData ? value.entries() : Object.entries(value);

        entries.forEach(([nestedObjectKey, nestedObjectValue]) => {
            appendValueRecursively(
                formData,
                `${key}[${nestedObjectKey}]`,
                nestedObjectValue,
            );
        });

        return;
    }

    // Handle primitives and null (convert to strings)
    formData.append(key, value === null ? '' : String(value));
};
