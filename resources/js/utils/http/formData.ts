/**
 * Converts an object payload to FormData format.
 * Arrays use indexed notation such as key[0] and key[1].
 * Nested objects use bracket notation such as key[sub-key].
 * Blobs keep their binary data, primitives become strings, and null becomes an empty string.
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
    if (Array.isArray(value)) {
        value.forEach((item, index) => {
            appendValueRecursively(formData, `${key}[${index}]`, item);
        });

        return;
    }

    if (value instanceof Blob) {
        formData.append(key, value);

        return;
    }

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

    formData.append(key, value === null ? '' : String(value));
};
