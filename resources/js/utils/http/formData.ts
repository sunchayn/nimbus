/**
 * Converts an object payload to FormData format.
 * Arrays use indexed notation such as key[0] and key[1].
 * Nested objects use bracket notation such as key[sub-key].
 * Blobs keep their binary data, primitives become strings, and null becomes an empty string.
 * Empty files are placeholders for a file that was not picked yet, so they are dropped unless asked to be kept.
 */
export const convertPayloadToFormData = (
    payload: object,
    keepPlaceholderFiles = false,
): FormData => {
    const formData = new FormData();

    Object.entries(payload).forEach(([key, value]) => {
        appendValueRecursively(formData, key, value, keepPlaceholderFiles);
    });

    return formData;
};

/**
 * Nests a child key under its parent.
 * A child key with brackets such as photos[] keeps them after the name, so it becomes parent[photos][].
 */
const nestKey = (parent: string, child: string): string => {
    const bracketIndex = child.indexOf('[');

    return bracketIndex > 0
        ? `${parent}[${child.slice(0, bracketIndex)}]${child.slice(bracketIndex)}`
        : `${parent}[${child}]`;
};

/**
 * Recursively appends values to FormData with proper key formatting.
 */
const appendValueRecursively = (
    formData: FormData,
    key: string,
    value: Blob | object | string | number | boolean | null,
    keepPlaceholderFiles: boolean,
): void => {
    if (Array.isArray(value)) {
        value.forEach((item, index) => {
            appendValueRecursively(
                formData,
                `${key}[${index}]`,
                item,
                keepPlaceholderFiles,
            );
        });

        return;
    }

    if (value instanceof Blob) {
        if (value instanceof File && value.size === 0 && !keepPlaceholderFiles) {
            return;
        }

        formData.append(key, value);

        return;
    }

    if (value && typeof value === 'object') {
        const entries =
            value instanceof FormData
                ? Array.from(value.entries())
                : Object.entries(value);

        entries.forEach(([nestedObjectKey, nestedObjectValue]) => {
            appendValueRecursively(
                formData,
                nestKey(key, nestedObjectKey),
                nestedObjectValue,
                keepPlaceholderFiles,
            );
        });

        return;
    }

    formData.append(key, value === null ? '' : String(value));
};
