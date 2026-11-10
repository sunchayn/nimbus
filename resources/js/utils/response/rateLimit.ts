import type { ResponseHeader } from '@/interfaces/http/headers';

export interface RateLimit {
    limit: number;
    remaining: number;
    /** Seconds until the next attempt is allowed, when the server sends it. */
    retryAfter: number | null;
}

const findNumber = (headers: ResponseHeader[], name: string): number | null => {
    const header = headers.find(item => item.key.toLowerCase() === name);
    const value = header === undefined ? Number.NaN : Number(header.value);

    return Number.isFinite(value) ? value : null;
};

/**
 * Reads the X-RateLimit headers of a response, or null when the route is not limited.
 */
export const parseRateLimit = (headers: ResponseHeader[]): RateLimit | null => {
    const limit = findNumber(headers, 'x-ratelimit-limit');
    const remaining = findNumber(headers, 'x-ratelimit-remaining');

    if (limit === null || remaining === null) {
        return null;
    }

    return { limit, remaining, retryAfter: findNumber(headers, 'retry-after') };
};
