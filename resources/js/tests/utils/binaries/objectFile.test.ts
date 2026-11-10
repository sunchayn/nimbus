import { BINARY_FIXTURES } from '@/config/binaries/fixtures';
import { describeFileProperties, pickObjectFixture } from '@/utils/binaries/objectFile';
import type { JSONSchema7 } from 'json-schema';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const [png] = BINARY_FIXTURES;

const fileSchema: JSONSchema7 = {
    type: 'object',
    properties: {
        name: { type: 'string' },
        content: { type: 'string', contentEncoding: 'base64' },
        mime_type: { type: 'string' },
        note: { type: 'string' },
    },
};

describe('objectFile', () => {
    describe('pickObjectFixture', () => {
        it('picks a file for an object with base64 content', () => {
            // Act

            const fixture = pickObjectFixture(fileSchema);

            // Assert

            expect(BINARY_FIXTURES).toContain(fixture);
        });

        it('picks nothing for an object without base64 content', () => {
            // Act

            const fixture = pickObjectFixture({
                type: 'object',
                properties: { name: { type: 'string' } },
            });

            // Assert

            expect(fixture).toBeUndefined();
        });

        it('picks only a file with a mime type listed in the enum', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    content: { type: 'string', contentEncoding: 'base64' },
                    mime_type: { type: 'string', enum: ['application/pdf'] },
                },
            };

            // Act

            const fixture = pickObjectFixture(schema);

            // Assert

            expect(fixture?.mimeType).toBe('application/pdf');
        });
    });

    describe('describeFileProperties', () => {
        it('makes the name, content and mime type describe the file', () => {
            // Act

            const fields = describeFileProperties(fileSchema, png);

            // Assert

            expect(fields.content).toBe(png.base64);
            expect(fields.mime_type).toBe('image/png');
            expect(fields.name).toMatch(/^[a-z]+\.png$/);
        });

        it('leaves other properties out', () => {
            // Act

            const fields = describeFileProperties(fileSchema, png);

            // Assert

            expect(fields).not.toHaveProperty('note');
        });

        it('recognizes a file name property', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: { file_name: { type: 'string' } },
            };

            // Act

            const fields = describeFileProperties(schema, png);

            // Assert

            expect(fields.file_name).toMatch(/\.png$/);
        });

        it('describes a nullable mime type property', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: {
                    name: { type: 'string' },
                    content: { type: 'string', contentEncoding: 'base64' },
                    mime_type: { type: ['string', 'null'] },
                },
            };

            // Act

            const fields = describeFileProperties(schema, png);

            // Assert

            expect(fields.mime_type).toBe('image/png');
        });

        it('leaves out a value that does not fit the length limit', () => {
            // Arrange

            const schema: JSONSchema7 = {
                type: 'object',
                properties: { mime_type: { type: 'string', maxLength: 3 } },
            };

            // Act

            const fields = describeFileProperties(schema, png);

            // Assert

            expect(fields).toEqual({});
        });
    });
});
