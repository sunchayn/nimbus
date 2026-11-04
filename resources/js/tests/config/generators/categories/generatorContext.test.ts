import { getAllGenerators, getGeneratorById } from '@/config/generators';
import type { GeneratorContext } from '@/interfaces/ui';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const RUNS = 30;

// These generators return numbers, so a length does not apply to them.
const NUMERIC_GENERATORS = ['int', 'float', 'bigint', 'amount', 'timestamp'];

const textGenerators = getAllGenerators.filter(
    generator => !NUMERIC_GENERATORS.includes(generator.id),
);

const generateWith = (id: string, context?: GeneratorContext) => {
    const generator = getGeneratorById.get(id);

    if (!generator) {
        throw new Error(`The generator ${id} does not exist.`);
    }

    return generator.generate(context);
};

describe('generators reading the context', () => {
    describe('Length', () => {
        it('builds an alphanumeric string within the length', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = String(
                    generateWith('alphanumeric', { minLength: 5, maxLength: 8 }),
                );

                // Assert

                expect(value.length).toBeGreaterThanOrEqual(5);
                expect(value.length).toBeLessThanOrEqual(8);
            }
        });

        it('builds an alphanumeric string of 10 characters by default', () => {
            // Act

            const value = String(generateWith('alphanumeric'));

            // Assert

            expect(value).toHaveLength(10);
        });

        it('builds a nanoid within the max length', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = String(generateWith('nanoid', { maxLength: 6 }));

                // Assert

                expect(value.length).toBeLessThanOrEqual(6);
            }
        });

        it('builds a password within the length', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = String(
                    generateWith('password', { minLength: 8, maxLength: 12 }),
                );

                // Assert

                expect(value.length).toBeGreaterThanOrEqual(8);
                expect(value.length).toBeLessThanOrEqual(12);
            }
        });

        it('builds a slug within the max length', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = String(generateWith('slug', { maxLength: 10 }));

                // Assert

                expect(value.length).toBeLessThanOrEqual(10);
            }
        });
    });

    describe('Range', () => {
        it('builds an integer within the range', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = Number(generateWith('int', { min: 5, max: 7 }));

                // Assert

                expect(value).toBeGreaterThanOrEqual(5);
                expect(value).toBeLessThanOrEqual(7);
            }
        });

        it('builds a float within the range', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = Number(
                    generateWith('float', { min: 1, max: 2, fractionDigits: 1 }),
                );

                // Assert

                expect(value).toBeGreaterThanOrEqual(1);
                expect(value).toBeLessThanOrEqual(2);
                expect(Math.round(value * 10)).toBeCloseTo(value * 10);
            }
        });

        it('builds a big integer within the range', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = generateWith('bigint', { min: 10, max: 12 });

                // Assert

                expect(typeof value).toBe('bigint');
                expect(value).toBeGreaterThanOrEqual(10n);
                expect(value).toBeLessThanOrEqual(12n);
            }
        });

        it('builds an amount within the range', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = Number(generateWith('amount', { min: 10, max: 20 }));

                // Assert

                expect(value).toBeGreaterThanOrEqual(10);
                expect(value).toBeLessThanOrEqual(20);
            }
        });
    });

    describe('Every text generator', () => {
        it.each(textGenerators.map(generator => [generator.id]))(
            '%s fits a length window',
            id => {
                for (let run = 0; run < RUNS; run++) {
                    // Act

                    const value = String(
                        generateWith(id, { minLength: 3, maxLength: 12 }),
                    );

                    // Assert

                    expect(value.length).toBeGreaterThanOrEqual(3);
                    expect(value.length).toBeLessThanOrEqual(12);
                }
            },
        );

        it.each(textGenerators.map(generator => [generator.id]))(
            '%s fits an exact length of 2',
            id => {
                for (let run = 0; run < RUNS; run++) {
                    // Act

                    const value = String(
                        generateWith(id, { minLength: 2, maxLength: 2 }),
                    );

                    // Assert

                    expect(value).toHaveLength(2);
                }
            },
        );
    });

    describe('Short forms', () => {
        it('builds a two letters country code when the length is 2', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = generateWith('country', { minLength: 2, maxLength: 2 });

                // Assert

                expect(value).toMatch(/^[A-Z]{2}$/);
            }
        });

        it('builds a three letters country code when the length is 3', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = generateWith('country', { minLength: 3, maxLength: 3 });

                // Assert

                expect(value).toMatch(/^[A-Z]{3}$/);
            }
        });

        it('builds a full country name when the length is not constrained', () => {
            // Act

            const value = String(generateWith('country'));

            // Assert

            expect(value.length).toBeGreaterThan(3);
        });

        it('builds an abbreviated state when the length is 2', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = generateWith('state', { minLength: 2, maxLength: 2 });

                // Assert

                expect(value).toMatch(/^[A-Z]{2}$/);
            }
        });

        it('builds a three letters code for a geocode of length 3', () => {
            // Act

            const value = generateWith('country-geocode', {
                minLength: 3,
                maxLength: 3,
            });

            // Assert

            expect(value).toMatch(/^[A-Z]{3}$/);
        });
    });

    describe('Truncation', () => {
        it.each([
            ['credit-card-number', /^[0-9-]+$/],
            ['iban', /^[A-Z0-9]+$/],
            ['uuid', /^[0-9a-f-]+$/],
            ['zip-code', /^[0-9-]+$/],
            ['phone', /^[0-9()\-.\s+x]+$/],
        ])('cuts %s to the max length and keeps its characters', (id, shape) => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = String(generateWith(id, { maxLength: 4 }));

                // Assert

                expect(value.length).toBeLessThanOrEqual(4);
                expect(value).toMatch(shape);
            }
        });
    });

    describe('Timestamp', () => {
        it('builds a timestamp within the range', () => {
            for (let run = 0; run < RUNS; run++) {
                // Act

                const value = Number(generateWith('timestamp', { min: 1000, max: 2000 }));

                // Assert

                expect(value).toBeGreaterThanOrEqual(1000);
                expect(value).toBeLessThanOrEqual(2000);
            }
        });
    });
});
