import { getGeneratorById } from '@/config/generators';
import {
    generatePlaceholderPayload,
    generateRandomPayload,
} from '@/utils/payload/payloadGenerator';
import type { JSONSchema7 } from 'json-schema';
import { afterEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

const spyOnGenerator = (id: string) => {
    const generator = getGeneratorById.get(id);

    if (!generator) {
        throw new Error(`The generator ${id} does not exist.`);
    }

    return vi.spyOn(generator, 'generate');
};

describe('payloadGenerator', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    describe('Placeholder payload', () => {
        it('marks a binary property as an empty file', () => {
            // Arrange

            const schema = {
                type: 'object',
                properties: {
                    name: { type: 'string' },
                    avatar: { type: 'string', format: 'binary' },
                },
            } as const;

            // Act

            const payload = generatePlaceholderPayload(schema);

            // Assert

            expect(payload.name).toBe('<placeholder>');
            expect(payload.avatar).toBeInstanceOf(File);
            expect((payload.avatar as File).size).toBe(0);
        });
    });

    describe('String properties', () => {
        it('keeps the matching generator when the schema sets a max length', () => {
            // Arrange

            const fullName = spyOnGenerator('full-name').mockReturnValue('Jane Doe');
            const city = spyOnGenerator('city').mockReturnValue('Paris');
            const schema: JSONSchema7 = {
                type: 'object',
                required: ['name', 'city'],
                properties: {
                    name: { type: 'string', maxLength: 255 },
                    city: { type: 'string', maxLength: 100 },
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.name).toBe('Jane Doe');
            expect(payload.city).toBe('Paris');
            expect(fullName).toHaveBeenCalledWith({ maxLength: 255 });
            expect(city).toHaveBeenCalledWith({ maxLength: 100 });
        });

        it('keeps the matching generator when the schema fixes the length', () => {
            // Arrange

            const geocode = spyOnGenerator('country-geocode').mockReturnValue('FR');
            const card =
                spyOnGenerator('credit-card-number').mockReturnValue('4111111111111111');
            const schema: JSONSchema7 = {
                type: 'object',
                required: ['country_geocode', 'card_number'],
                properties: {
                    country_geocode: { type: 'string', minLength: 2, maxLength: 2 },
                    card_number: { type: 'string', maxLength: 19 },
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.country_geocode).toBe('FR');
            expect(payload.card_number).toBe('4111111111111111');
            expect(geocode).toHaveBeenCalledWith({ minLength: 2, maxLength: 2 });
            expect(card).toHaveBeenCalledWith({ maxLength: 19 });
        });

        it('passes no length when the schema sets none', () => {
            // Arrange

            const fullName = spyOnGenerator('full-name').mockReturnValue('Jane Doe');
            const schema: JSONSchema7 = {
                type: 'object',
                required: ['name'],
                properties: { name: { type: 'string' } },
            };

            // Act

            generateRandomPayload(schema);

            // Assert

            expect(fullName).toHaveBeenCalledWith({});
        });

        it('passes the schema length to the generator matched by format', () => {
            // Arrange

            const email = spyOnGenerator('email').mockReturnValue('a@b.co');
            const schema: JSONSchema7 = {
                type: 'object',
                required: ['contact'],
                properties: {
                    contact: { type: 'string', format: 'email', maxLength: 10 },
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.contact).toBe('a@b.co');
            expect(email).toHaveBeenCalledWith({ maxLength: 10 });
        });

        it('falls back to a plain string within the schema length', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['unknown_field'],
                properties: {
                    unknown_field: { type: 'string', minLength: 3, maxLength: 4 },
                },
            };

            // Act

            const value = String(generateRandomPayload(schema).unknown_field);

            // Assert

            expect(value.length).toBeGreaterThanOrEqual(3);
            expect(value.length).toBeLessThanOrEqual(4);
        });

        it('builds text that fits the schema length', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['slug', 'notes'],
                properties: {
                    slug: { type: 'string', maxLength: 8 },
                    notes: { type: 'string', minLength: 30, maxLength: 40 },
                },
            };

            for (let run = 0; run < 20; run++) {
                // Act

                const payload = generateRandomPayload(schema);

                // Assert

                expect(String(payload.slug).length).toBeLessThanOrEqual(8);
                expect(String(payload.notes).length).toBeGreaterThanOrEqual(30);
                expect(String(payload.notes).length).toBeLessThanOrEqual(40);
            }
        });
    });

    describe('Integer properties', () => {
        it('lets the schema bounds win over the default parameters', () => {
            // Arrange

            const int = spyOnGenerator('int');
            const schema: JSONSchema7 = {
                type: 'object',
                properties: { age: { type: 'integer', maximum: 50 } },
            };

            // Act

            const age = Number(generateRandomPayload(schema).age);

            // Assert

            expect(int).toHaveBeenCalledWith({ min: 1, max: 50 });
            expect(age).toBeGreaterThanOrEqual(1);
            expect(age).toBeLessThanOrEqual(50);
        });

        it('respects a minimum of zero', () => {
            // Arrange

            const int = spyOnGenerator('int');
            const schema: JSONSchema7 = {
                type: 'object',
                properties: { age: { type: 'integer', minimum: 0 } },
            };

            // Act

            generateRandomPayload(schema);

            // Assert

            expect(int).toHaveBeenCalledWith({ min: 0, max: 100 });
        });

        it('uses the plain range when no rule matches the name', () => {
            // Arrange

            const int = spyOnGenerator('int');
            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    quantity: { type: 'integer', minimum: 1, maximum: 999 },
                },
            };

            // Act

            const quantity = Number(generateRandomPayload(schema).quantity);

            // Assert

            expect(int).not.toHaveBeenCalled();
            expect(quantity).toBeGreaterThanOrEqual(1);
            expect(quantity).toBeLessThanOrEqual(999);
        });
    });
});
