import { parseRateLimit } from '@/utils/response/rateLimit';
import { describe, expect, it } from 'vitest';

describe('parseRateLimit', () => {
    it('reads the rate limit headers', () => {
        // Arrange

        const headers = [
            { key: 'X-RateLimit-Limit', value: '60' },
            { key: 'x-ratelimit-remaining', value: '58' },
            { key: 'Retry-After', value: '30' },
        ];

        // Act & Assert

        expect(parseRateLimit(headers)).toEqual({
            limit: 60,
            remaining: 58,
            retryAfter: 30,
        });
    });

    it('returns null without the headers or with unusable values', () => {
        // Arrange & Act & Assert

        expect(parseRateLimit([{ key: 'Content-Type', value: 'text/html' }])).toBeNull();
        expect(
            parseRateLimit([
                { key: 'x-ratelimit-limit', value: 'many' },
                { key: 'x-ratelimit-remaining', value: '1' },
            ]),
        ).toBeNull();
    });
});
