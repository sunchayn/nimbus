/**
 * Shows a route without its resource prefix, such as csv for content/csv.
 */
export const shortTitle = (title: string): string => {
    // A title with a leading slash is already shortened.
    if (title.startsWith('/')) {
        return title;
    }

    const segments = title.split('/').filter(Boolean);

    return segments.length > 1 ? segments.slice(1).join('/') : title;
};

/**
 * Gives the name a request gets when it has no custom name.
 * An endpoint reads as a path, so the short name starts with a slash.
 */
export const defaultRequestName = (title: string | undefined): string =>
    title ? `/${shortTitle(title).trim().replace(/^\/+/, '')}` : '';
