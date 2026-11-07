import { time } from '@/config/generators/categories/time';
import { describe, expect, it } from 'vitest';

const generatorById = (id: string) =>
    time.generators.find(generator => generator.id === id)!;

describe('time generators', () => {
    describe.each(['date', 'datetime'])('%s', id => {
        it('follows the date format of the context', () => {
            // Act

            const value = generatorById(id).generate({ dateFormat: 'd/m/Y H:i' });

            // Assert

            expect(value).toMatch(/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/);
        });

        it('produces a date that parses back with the same format', () => {
            // Act

            const value = generatorById(id).generate({
                dateFormat: 'Y-m-d\\TH:i:s.v\\Z',
            });

            // Assert

            expect(new Date(value as string).toISOString()).toBe(value);
        });
    });

    it('generates a plain date without a format', () => {
        // Act

        const value = generatorById('date').generate({});

        // Assert

        expect(value).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    });

    it('generates an ISO 8601 date time without a format', () => {
        // Act

        const value = generatorById('datetime').generate({});

        // Assert

        expect(value).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/);
    });

    it('generates a date time when the context has no value at all', () => {
        // Act

        const value = generatorById('datetime').generate(undefined);

        // Assert

        expect(typeof value).toBe('string');
    });
});
