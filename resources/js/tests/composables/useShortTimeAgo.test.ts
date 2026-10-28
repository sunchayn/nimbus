import { useShortTimeAgo } from '@/composables/ui/useShortTimeAgo';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

const NOW = new Date('2026-01-01T12:00:00Z').getTime();
const MINUTE = 60_000;
const HOUR = 60 * MINUTE;
const DAY = 24 * HOUR;

describe('useShortTimeAgo', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(NOW);
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it.each([
        ['under a minute', 20_000, 'just now'],
        ['one minute', MINUTE, '1m ago'],
        ['four minutes', 4 * MINUTE, '4m ago'],
        ['one hour', HOUR, '1h ago'],
        ['three hours', 3 * HOUR, '3h ago'],
        ['one day', DAY, '1d ago'],
        ['two weeks', 14 * DAY, '2w ago'],
        ['two months', 60 * DAY, '2mo ago'],
        ['two years', 730 * DAY, '2y ago'],
    ])('formats %s in the past', (_label, offset, expected) => {
        expect(useShortTimeAgo(NOW - offset).value).toBe(expected);
    });

    it('formats a future time', () => {
        expect(useShortTimeAgo(NOW + 2 * HOUR).value).toBe('in 2h');
    });

    it('returns an empty string for an invalid date', () => {
        expect(useShortTimeAgo(new Date('invalid')).value).toBe('');
    });

    it('accepts a Date and reacts to ref changes', () => {
        const time = ref(new Date(NOW - MINUTE));
        const timeAgo = useShortTimeAgo(time);

        expect(timeAgo.value).toBe('1m ago');

        time.value = new Date(NOW - 5 * HOUR);

        expect(timeAgo.value).toBe('5h ago');
    });
});
