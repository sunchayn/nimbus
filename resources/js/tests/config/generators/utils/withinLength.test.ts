import { withinLength } from '@/config/generators/utils/withinLength';
import { describe, expect, it, vi } from 'vitest';

describe('withinLength', () => {
    it('returns the produced value when nothing constrains it', () => {
        // Arrange

        const produce = vi.fn(() => 'any value');

        // Act

        const value = withinLength(undefined, produce);

        // Assert

        expect(value).toBe('any value');
        expect(produce).toHaveBeenCalledTimes(1);
    });

    it('returns the produced value when it fits', () => {
        // Act

        const value = withinLength({ minLength: 2, maxLength: 5 }, () => 'four');

        // Assert

        expect(value).toBe('four');
    });

    it('produces again when the value does not fit', () => {
        // Arrange

        const produce = vi
            .fn<() => string>()
            .mockReturnValueOnce('too long value')
            .mockReturnValueOnce('fits');

        // Act

        const value = withinLength({ maxLength: 5 }, produce);

        // Assert

        expect(value).toBe('fits');
        expect(produce).toHaveBeenCalledTimes(2);
    });

    it('cuts a value that is too long when the generator opts in', () => {
        // Act

        const value = withinLength({ maxLength: 4 }, () => '1234567890', {
            truncate: true,
        });

        // Assert

        expect(value).toBe('1234');
    });

    it('does not cut a value that is too long unless the generator opts in', () => {
        // Act

        const value = withinLength({ maxLength: 4 }, () => '1234567890');

        // Assert

        expect(value).toMatch(/^[a-zA-Z]{1,4}$/);
    });

    it('falls back to letters when a cut value is still too short', () => {
        // Act

        const value = withinLength({ minLength: 6, maxLength: 8 }, () => '123', {
            truncate: true,
        });

        // Assert

        expect(value).toMatch(/^[a-zA-Z]{6,8}$/);
    });

    it('falls back to letters of the required length when nothing fits', () => {
        // Act

        const value = withinLength({ minLength: 2, maxLength: 2 }, () => 'too long');

        // Assert

        expect(value).toMatch(/^[a-zA-Z]{2}$/);
    });

    it('falls back to letters of the minimum length when only a minimum is set', () => {
        // Act

        const value = withinLength({ minLength: 9 }, () => 'short');

        // Assert

        expect(value).toMatch(/^[a-zA-Z]{9}$/);
    });
});
