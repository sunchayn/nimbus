import {
    applyRelativeTimeTravel,
    formatRelativeTimeTravel,
    isValidAmount,
    parseAbsoluteTimeTravel,
    parseRelativeTimeTravel,
} from '@/utils/request/timeTravel';
import { describe, expect, it } from 'vitest';

describe('timeTravel', () => {
    describe('isValidAmount', () => {
        it.each([
            [1, true],
            [9999, true],
            [0, false],
            [10000, false],
            [1.5, false],
            [Number.NaN, false],
        ])('checks %s', (amount, expected) => {
            expect(isValidAmount(amount)).toBe(expected);
        });
    });

    describe('parseRelativeTimeTravel', () => {
        it.each([
            ['+1s', '+1 second'],
            ['+1h', '+1 hour'],
            ['-2d', '-2 days'],
            ['+31 days', '+31 days'],
            ['-1 Week', '-1 week'],
            ['5min', '+5 minutes'],
            ['+3mo', '+3 months'],
            ['+1y', '+1 year'],
            ['+1d 2h 30m', '+1 day +2 hours +30 minutes'],
            ['-1d 2h', '-1 day -2 hours'],
            ['+1d -2h', '+1 day -2 hours'],
            ['+1d,2h', '+1 day +2 hours'],
            ['+2h30m', '+2 hours +30 minutes'],
        ])('reads %j as %j', (text, expected) => {
            const parts = parseRelativeTimeTravel(text);

            expect(parts).not.toBeNull();
            expect(formatRelativeTimeTravel(parts!)).toBe(expected);
        });

        it.each([
            '',
            '   ',
            '+0d',
            '+10000d',
            '+1.5d',
            '+1',
            '+d',
            '+1 fortnight',
            'tomorrow',
            '+1d later',
            '+1d +',
            '+1 d 2 h 3 m 4 s 5 w 6 y 7 d',
        ])('rejects %j', text => {
            expect(parseRelativeTimeTravel(text)).toBeNull();
        });
    });

    describe('parseAbsoluteTimeTravel', () => {
        it('normalizes a valid date and time', () => {
            expect(parseAbsoluteTimeTravel('2030-01-31T10:00')).toBe('2030-01-31T10:00');
            expect(parseAbsoluteTimeTravel('2030-01-31 10:00:45')).toBe(
                '2030-01-31T10:00',
            );
        });

        it.each(['2030-02-31T10:00', '2030-01-31', '2030-01-31T25:00', 'soon'])(
            'rejects %j',
            value => {
                expect(parseAbsoluteTimeTravel(value)).toBeNull();
            },
        );
    });

    describe('applyRelativeTimeTravel', () => {
        const from = new Date(2030, 0, 31, 10, 0, 0);
        const apply = (text: string) =>
            applyRelativeTimeTravel(parseRelativeTimeTravel(text)!, from);

        it('moves the date by every unit', () => {
            expect(apply('+1s')).toEqual(new Date(2030, 0, 31, 10, 0, 1));
            expect(apply('+30m')).toEqual(new Date(2030, 0, 31, 10, 30));
            expect(apply('+2h')).toEqual(new Date(2030, 0, 31, 12, 0));
            expect(apply('+1d')).toEqual(new Date(2030, 1, 1, 10, 0));
            expect(apply('+1w')).toEqual(new Date(2030, 1, 7, 10, 0));
            expect(apply('+1y')).toEqual(new Date(2031, 0, 31, 10, 0));
        });

        it('moves backwards and combines parts', () => {
            expect(apply('-1d')).toEqual(new Date(2030, 0, 30, 10, 0));
            expect(apply('+1d -2h')).toEqual(new Date(2030, 1, 1, 8, 0));
        });
    });
});
