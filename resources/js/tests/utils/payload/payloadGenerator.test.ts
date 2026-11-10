import { BINARY_FIXTURES } from '@/config/binaries/fixtures';
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

    describe('Binary content', () => {
        const RUNS = 30;

        const findFixture = (content: unknown) =>
            BINARY_FIXTURES.find(fixture => fixture.base64 === content);

        const createFileSchema = (content: JSONSchema7, mimeType: JSONSchema7 = {}) =>
            ({
                type: 'object',
                required: ['name', 'content', 'mime_type'],
                properties: {
                    name: { type: 'string' },
                    content: { type: 'string', ...content },
                    mime_type: { type: 'string', ...mimeType },
                },
            }) satisfies JSONSchema7;

        it.each([
            ['contentEncoding', { contentEncoding: 'base64' }],
            ['the byte format', { format: 'byte' }],
        ])('describes the same file in every field using %s', (_label, content) => {
            for (let run = 0; run < RUNS; run++) {
                // Arrange

                const schema = createFileSchema(content);

                // Act

                const payload = generateRandomPayload(schema);
                const fixture = findFixture(payload.content);

                // Assert

                expect(fixture).toBeDefined();
                expect(payload.mime_type).toBe(fixture?.mimeType);
                expect(String(payload.name)).toMatch(
                    new RegExp(`^[a-z]+\\.${fixture?.extension}$`),
                );
            }
        });

        it('picks a file for each item of an array of objects', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['files'],
                properties: {
                    files: {
                        type: 'array',
                        items: createFileSchema({ contentEncoding: 'base64' }),
                    },
                },
            };
            let sawDifferentFiles = false;

            for (let run = 0; run < 60; run++) {
                // Act

                const files = generateRandomPayload(schema).files as Record<
                    string,
                    string
                >[];

                // Assert

                for (const file of files) {
                    expect(file.mime_type).toBe(findFixture(file.content)?.mimeType);
                }

                sawDifferentFiles ||= new Set(files.map(file => file.content)).size > 1;
            }

            expect(sawDifferentFiles).toBe(true);
        });

        it('limits the files to the mime types the schema allows', () => {
            for (let run = 0; run < RUNS; run++) {
                // Arrange

                const schema = createFileSchema(
                    { contentEncoding: 'base64' },
                    { enum: ['image/png', 'image/gif'] },
                );

                // Act

                const payload = generateRandomPayload(schema);

                // Assert

                expect(['image/png', 'image/gif']).toContain(payload.mime_type);
                expect(findFixture(payload.content)?.mimeType).toBe(payload.mime_type);
            }
        });

        it('limits the files to the encoded length the schema allows', () => {
            for (let run = 0; run < RUNS; run++) {
                // Arrange

                const schema = createFileSchema({
                    contentEncoding: 'base64',
                    maxLength: 40,
                });

                // Act

                const payload = generateRandomPayload(schema);

                // Assert

                expect(String(payload.content).length).toBeLessThanOrEqual(40);
                expect(findFixture(payload.content)?.mimeType).toBe(payload.mime_type);
            }
        });

        it('still fills the fields when no file fits', () => {
            for (let run = 0; run < RUNS; run++) {
                // Arrange

                const schema = createFileSchema({
                    contentEncoding: 'base64',
                    maxLength: 8,
                });

                // Act

                const payload = generateRandomPayload(schema);

                // Assert

                expect(String(payload.content).length).toBeLessThanOrEqual(8);
                expect(() => atob(String(payload.content))).not.toThrow();
                expect(String(payload.mime_type)).toMatch(/^[a-z]+\/[a-z+.-]+$/);
            }
        });

        it('keeps a plain name as a person name when the object holds no file', () => {
            // Arrange

            const fullName = spyOnGenerator('full-name').mockReturnValue('Jane Doe');
            const fileName = spyOnGenerator('file-name');
            const schema: JSONSchema7 = {
                type: 'object',
                required: ['name', 'mime_type'],
                properties: {
                    name: { type: 'string' },
                    mime_type: { type: 'string' },
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.name).toBe('Jane Doe');
            expect(fullName).toHaveBeenCalled();
            expect(fileName).not.toHaveBeenCalled();
        });

        it('fills a mime type that has no file next to it', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['mime_type'],
                properties: { mime_type: { type: 'string', maxLength: 100 } },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(String(payload.mime_type)).toMatch(/^[a-z]+\/[a-z+.-]+$/);
        });

        it('leaves the placeholder untouched', () => {
            // Act

            const payload = generatePlaceholderPayload(
                createFileSchema({ contentEncoding: 'base64' }),
            );

            // Assert

            expect(payload.content).toBe('<placeholder>');
        });
    });

    describe('Arrays', () => {
        it('keeps the number of items within the schema limits', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    tags: {
                        type: 'array',
                        minItems: 2,
                        maxItems: 3,
                        items: { type: 'string', minLength: 3 },
                    },
                },
            };

            for (let run = 0; run < 30; run++) {
                // Act

                const tags = generateRandomPayload(schema).tags as unknown[];

                // Assert

                expect(tags.length).toBeGreaterThanOrEqual(2);
                expect(tags.length).toBeLessThanOrEqual(3);
            }
        });

        it('keeps a single item when the maximum is one', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    tags: {
                        type: 'array',
                        maxItems: 1,
                        items: { type: 'string', minLength: 3 },
                    },
                },
            };

            for (let run = 0; run < 20; run++) {
                // Act

                const tags = generateRandomPayload(schema).tags as unknown[];

                // Assert

                expect(tags).toHaveLength(1);
            }
        });

        it('starts a placeholder at the minimum number of items', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    tags: { type: 'array', minItems: 2, items: { type: 'string' } },
                },
            };

            // Act

            const tags = generatePlaceholderPayload(schema).tags as unknown[];

            // Assert

            expect(tags).toHaveLength(2);
        });
    });

    describe('Rule coverage', () => {
        it('generates a value for a nullable property', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    age: { type: ['integer', 'null'], minimum: 5, maximum: 5 },
                    nickname: { type: ['string', 'null'] },
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.age).toBe(5);
            expect(typeof payload.nickname).toBe('string');
        });

        it('leaves out a property marked to skip autofill', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    name: { type: 'string' },
                    internal_note: {
                        type: 'string',
                        skipAutofill: true,
                    } as JSONSchema7,
                },
                required: ['name'],
            };

            // Act

            const random = generateRandomPayload(schema);
            const placeholder = generatePlaceholderPayload(schema);

            // Assert

            expect(Object.keys(random)).toEqual(['name']);
            expect(Object.keys(placeholder)).toEqual(['name']);
        });

        it('generates a date that follows the date format of a property', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['starts_at'],
                properties: {
                    starts_at: {
                        type: 'string',
                        format: 'date-time',
                        dateFormat: 'Y-m-d H:i',
                    } as JSONSchema7,
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.starts_at).toMatch(/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/);
        });

        it.each([
            ['Y-m-d', undefined, /^\d{4}-\d{2}-\d{2}$/],
            ['c', 'date-time', /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/],
            [
                'Y-m-d\\TH:i:s.v\\Z',
                'date-time',
                /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/,
            ],
            ['d/m/Y', undefined, /^\d{2}\/\d{2}\/\d{4}$/],
        ])('generates a date for the date format %s', (dateFormat, format, expected) => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['when'],
                properties: {
                    when: { type: 'string', format, dateFormat } as JSONSchema7,
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.when).toMatch(expected);
        });

        it('generates an ISO 8601 value for a date time property without a date format', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['when'],
                properties: { when: { type: 'string', format: 'date-time' } },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.when).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/);
        });

        it('generates values that satisfy the pattern and the format of a property', () => {
            // Arrange

            const namePattern = String.raw`^[\p{L}\p{M}\p{N}_-]+$`;

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['name', 'pin', 'backup_ip'],
                properties: {
                    name: {
                        type: 'string',
                        pattern: namePattern,
                        minLength: 2,
                        maxLength: 40,
                    },
                    pin: { type: 'string', pattern: String.raw`^\d{4}$` },
                    server_ip: { type: ['string', 'null'], format: 'ipv4' },
                    backup_ip: { type: 'string', format: 'ipv6' },
                },
            };

            // Act

            const payloads = Array.from({ length: 30 }, () =>
                generateRandomPayload(schema),
            );

            // Assert

            for (const payload of payloads) {
                expect(payload.name).toMatch(new RegExp(namePattern, 'u'));
                expect(payload.pin).toMatch(/^\d{4}$/);
                expect(payload.backup_ip).toMatch(/^[0-9a-f:]+$/i);

                if (payload.server_ip !== '') {
                    expect(payload.server_ip).toMatch(/^(\d{1,3}\.){3}\d{1,3}$/);
                }
            }
        });

        it('leaves out an optional property that cannot be empty instead of sending it empty', () => {
            // Arrange

            vi.spyOn(Math, 'random').mockReturnValue(0);

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['name'],
                properties: {
                    name: { type: 'string' },
                    website: { type: 'string', format: 'uri' },
                    nickname: { type: ['string', 'null'] },
                },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload).not.toHaveProperty('website');
            expect(payload.nickname).toBe('');
            expect(payload.name).not.toBe('');
        });

        it('always sends a required property', () => {
            // Arrange

            vi.spyOn(Math, 'random').mockReturnValue(0);

            const schema: JSONSchema7 = {
                type: 'object',
                required: ['website'],
                properties: { website: { type: 'string', format: 'uri' } },
            };

            // Act

            const payload = generateRandomPayload(schema);

            // Assert

            expect(payload.website).toMatch(/^https?:\/\//);
        });
    });
});
