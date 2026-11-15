import { defaultRequestName, shortTitle } from '@/utils/request/tabTitle';
import { describe, expect, it } from 'vitest';

describe('shortTitle', () => {
    it('drops the resource prefix of a route', () => {
        expect(shortTitle('content/csv')).toBe('csv');
        expect(shortTitle('users/{id}/posts')).toBe('{id}/posts');
    });

    it('keeps a title that starts with a slash', () => {
        expect(shortTitle('/{id}/posts')).toBe('/{id}/posts');
    });

    it('keeps a single segment', () => {
        expect(shortTitle('users')).toBe('users');
    });
});

describe('defaultRequestName', () => {
    it('starts the short name with one slash', () => {
        expect(defaultRequestName('content/csv')).toBe('/csv');
        expect(defaultRequestName('/users')).toBe('/users');
    });

    it('is empty without a title', () => {
        expect(defaultRequestName(undefined)).toBe('');
    });
});
