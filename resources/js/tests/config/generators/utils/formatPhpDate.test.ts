import { formatPhpDate } from '@/config/generators/utils/formatPhpDate';
import { describe, expect, it } from 'vitest';

describe('formatPhpDate', () => {
    const date = new Date('2025-11-03T15:07:09.036Z');

    it.each([
        ['Y-m-d', '2025-11-03'],
        ['Y-m-d H:i', '2025-11-03 15:07'],
        ['d/m/Y g:i A', '03/11/2025 3:07 PM'],
        ['j M y', '3 Nov 25'],
        ['l, F jS', 'Monday, November 3rd'],
        ['H:i:s.v', '15:07:09.036'],
        ['u', '036000'],
        ['c', '2025-11-03T15:07:09+00:00'],
        ['Y-m-d\\TH:i:s.v\\Z', '2025-11-03T15:07:09.036Z'],
        ['r', 'Mon, 03 Nov 2025 15:07:09 +0000'],
        ['U', '1762182429'],
        ['G:i a', '15:07 pm'],
        ['y/n/j, h:i', '25/11/3, 03:07'],
        ['N w z', '1 1 306'],
        ['W o t L', '45 2025 30 0'],
        ['e T O p', 'UTC UTC +0000 Z'],
        ['\\Y Y', 'Y 2025'],
        ['Y-m-d # @ x', '2025-11-03 # @ x'],
    ])('formats %s', (format, expected) => {
        // Act

        const value = formatPhpDate(date, format);

        // Assert

        expect(value).toBe(expected);
    });

    it.each([
        ['2025-01-01T00:00:00Z', 'g:i A', '12:00 AM'],
        ['2025-01-01T12:30:00Z', 'g:i A', '12:30 PM'],
        ['2025-01-01T00:00:00Z', 'jS', '1st'],
        ['2025-01-02T00:00:00Z', 'jS', '2nd'],
        ['2025-01-03T00:00:00Z', 'jS', '3rd'],
        ['2025-01-04T00:00:00Z', 'jS', '4th'],
        ['2025-01-11T00:00:00Z', 'jS', '11th'],
        ['2025-01-12T00:00:00Z', 'jS', '12th'],
        ['2025-01-13T00:00:00Z', 'jS', '13th'],
        ['2025-01-21T00:00:00Z', 'jS', '21st'],
        ['2024-02-10T00:00:00Z', 'L t', '1 29'],
        ['2025-01-01T00:00:00Z', 'W o', '01 2025'],
        ['2027-01-01T00:00:00Z', 'W o', '53 2026'],
        ['2025-01-05T00:00:00Z', 'N w', '7 0'],
    ])('formats %s with %s', (iso, format, expected) => {
        // Act

        const value = formatPhpDate(new Date(iso), format);

        // Assert

        expect(value).toBe(expected);
    });

    it('drops a trailing backslash', () => {
        // Act

        const value = formatPhpDate(date, 'Y\\');

        // Assert

        expect(value).toBe('2025');
    });

    it('returns an empty string for an empty format', () => {
        // Act

        const value = formatPhpDate(date, '');

        // Assert

        expect(value).toBe('');
    });
});
