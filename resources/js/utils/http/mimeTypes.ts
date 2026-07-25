export interface MimeTypeInfo {
    extension: string;
    mimeType: string;
}

const MIME_MAP: Record<string, MimeTypeInfo> = {
    'application/json': { extension: 'json', mimeType: 'application/json' },
    'application/ld+json': { extension: 'jsonld', mimeType: 'application/ld+json' },
    'text/html': { extension: 'html', mimeType: 'text/html' },
    'text/xml': { extension: 'xml', mimeType: 'text/xml' },
    'application/xml': { extension: 'xml', mimeType: 'application/xml' },
    'text/plain': { extension: 'txt', mimeType: 'text/plain' },
    'text/css': { extension: 'css', mimeType: 'text/css' },
    'text/javascript': { extension: 'js', mimeType: 'text/javascript' },
    'application/javascript': { extension: 'js', mimeType: 'application/javascript' },
    'text/csv': { extension: 'csv', mimeType: 'text/csv' },
    'application/pdf': { extension: 'pdf', mimeType: 'application/pdf' },
    'application/zip': { extension: 'zip', mimeType: 'application/zip' },
    'image/png': { extension: 'png', mimeType: 'image/png' },
    'image/jpeg': { extension: 'jpg', mimeType: 'image/jpeg' },
    'image/gif': { extension: 'gif', mimeType: 'image/gif' },
    'image/svg+xml': { extension: 'svg', mimeType: 'image/svg+xml' },
};

/**
 * Resolves extension and canonical mime-type from a Content-Type header value.
 */
export function getMimeTypeInfo(contentTypeHeaderValue: string): MimeTypeInfo {
    if (!contentTypeHeaderValue) {
        return { extension: 'txt', mimeType: 'text/plain' };
    }

    // Strip parameters like charset=utf-8
    const cleanType = contentTypeHeaderValue.split(';')[0].trim().toLowerCase();

    // Direct match
    if (MIME_MAP[cleanType]) {
        return MIME_MAP[cleanType];
    }

    // Partial/fuzzy match
    for (const [key, info] of Object.entries(MIME_MAP)) {
        if (cleanType.includes(key) || key.includes(cleanType)) {
            return info;
        }
    }

    // Common fallbacks based on substrings
    if (cleanType.includes('json')) {
        return { extension: 'json', mimeType: 'application/json' };
    }

    if (cleanType.includes('xml')) {
        return { extension: 'xml', mimeType: 'application/xml' };
    }

    if (cleanType.includes('html')) {
        return { extension: 'html', mimeType: 'text/html' };
    }

    if (cleanType.includes('text')) {
        return { extension: 'txt', mimeType: 'text/plain' };
    }

    if (cleanType.includes('image')) {
        const subType = cleanType.split('/')[1];
        if (subType) {
            return { extension: subType, mimeType: cleanType };
        }
    }

    return { extension: 'txt', mimeType: 'text/plain' };
}
