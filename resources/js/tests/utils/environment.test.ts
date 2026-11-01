import {
    isProductionEnvironment,
    isValidBaseUrl,
    normalizeBaseUrl,
} from '@/utils/environment';
import { describe, expect, it } from 'vitest';

describe('environment utils', () => {
    it.each([
        ['https://example.com', true],
        ['http://localhost:8000/api', true],
        ['ftp://example.com', false],
        ['example.com', false],
        ['/relative', false],
        ['', false],
    ])('validates base URL %s', (value, expected) => {
        expect(isValidBaseUrl(value)).toBe(expected);
    });

    it('removes trailing slashes', () => {
        expect(normalizeBaseUrl(' https://example.com// ')).toBe('https://example.com');
    });

    it('detects production environments', () => {
        expect(isProductionEnvironment({ id: 'production', name: 'Production' })).toBe(
            true,
        );
        expect(isProductionEnvironment({ id: 'x', name: 'Prod EU' })).toBe(true);
        expect(isProductionEnvironment({ id: 'staging', name: 'Staging' })).toBe(false);
        expect(
            isProductionEnvironment({
                id: 'current',
                name: 'Production (current host)',
                detected: 'production',
            }),
        ).toBe(true);
        expect(
            isProductionEnvironment({
                id: 'current',
                name: 'Current host',
                detected: null,
            }),
        ).toBe(false);
    });
});
