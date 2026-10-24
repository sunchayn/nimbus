import { getHttpVerbColorClass, getHttpVerbShortLabel } from '@/utils/http';
import { describe, expect, it } from 'vitest';

describe('getHttpVerbShortLabel', () => {
    it('shortens DELETE and keeps other methods unchanged', () => {
        expect(getHttpVerbShortLabel('DELETE')).toBe('DEL');
        expect(getHttpVerbShortLabel('delete')).toBe('DEL');
        expect(getHttpVerbShortLabel('PATCH')).toBe('PATCH');
        expect(getHttpVerbShortLabel('GET')).toBe('GET');
    });
});

describe('getHttpVerbColorClass', () => {
    it('maps write methods to status colors and others to muted', () => {
        expect(getHttpVerbColorClass('POST')).toBe('text-success');
        expect(getHttpVerbColorClass('put')).toBe('text-success');
        expect(getHttpVerbColorClass('DELETE')).toBe('text-error');
        expect(getHttpVerbColorClass('GET')).toBe('text-muted');
    });
});
