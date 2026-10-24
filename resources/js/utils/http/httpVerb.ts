const verbColorClasses: Record<string, string> = {
    POST: 'text-success',
    PUT: 'text-success',
    DELETE: 'text-error',
};

/**
 * Returns the text color class for an HTTP method.
 * Unknown and read methods use the muted color.
 */
export const getHttpVerbColorClass = (method: string): string => {
    return verbColorClasses[method.toUpperCase()] ?? 'text-muted';
};

const verbShortLabels: Record<string, string> = {
    DELETE: 'DEL',
};

/**
 * Returns a compact label for an HTTP method.
 * Methods without a short form are returned unchanged.
 */
export const getHttpVerbShortLabel = (method: string): string => {
    return verbShortLabels[method.toUpperCase()] ?? method;
};
