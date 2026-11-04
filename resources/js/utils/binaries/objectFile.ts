import { FILE_NAME_PATTERN, MIME_TYPE_PATTERN } from '@/config/binaries/mimeTypes';
import type { BinaryFixture } from '@/interfaces/ui';
import { pickFixture } from '@/utils/binaries/pickFixture';
import { faker } from '@faker-js/faker/locale/en';
import type { JSONSchema7, JSONSchema7Definition } from 'json-schema';

function asSchemaObject(property: JSONSchema7Definition | undefined): JSONSchema7 | null {
    if (typeof property !== 'object' || property === null || Array.isArray(property)) {
        return null;
    }

    return property;
}

function isBase64Content(schema: JSONSchema7): boolean {
    return schema.contentEncoding === 'base64' || schema.format === 'byte';
}

function fitsLength(schema: JSONSchema7, value: string): boolean {
    return (
        value.length >= (schema.minLength ?? 0) &&
        value.length <= (schema.maxLength ?? Infinity)
    );
}

/**
 * Picks the file that describes an object, or undefined when the object holds no file.
 *
 * An object holds a file when one of its direct properties is base64 content.
 * The mime type property, when it has an enum, limits which files can be picked.
 */
export function pickObjectFixture(schema: JSONSchema7): BinaryFixture | undefined {
    const properties = Object.entries(schema.properties ?? {});
    const contentProperty = properties
        .map(([, property]) => asSchemaObject(property))
        .find(property => property !== null && isBase64Content(property));

    if (!contentProperty) {
        return undefined;
    }

    const mimeTypeProperty = properties.find(([name]) => MIME_TYPE_PATTERN.test(name));
    const mimeTypes = asSchemaObject(mimeTypeProperty?.[1])?.enum;

    return pickFixture({
        mimeTypes: mimeTypes?.filter(value => typeof value === 'string'),
        minLength: contentProperty.minLength,
        maxLength: contentProperty.maxLength,
    });
}

/**
 * Builds the values that make the properties of an object describe one file.
 *
 * @example
 *  input: An object with the properties `name`, `content` and `mime_type`, and a png file (fixture).
 *  output: Object with `name` that ends with .png, content to the base64 of the png, and mime_type to image/png.
 */
export function describeFileProperties(
    schema: JSONSchema7,
    fixture: BinaryFixture,
): Record<string, string> {
    const fields: Record<string, string> = {};

    for (const [name, definition] of Object.entries(schema.properties ?? {})) {
        const property = asSchemaObject(definition);

        if (property?.type !== 'string') {
            continue;
        }

        let value: string | null = null;

        if (isBase64Content(property)) {
            value = fixture.base64;
        } else if (MIME_TYPE_PATTERN.test(name)) {
            value = fixture.mimeType;
        } else if (FILE_NAME_PATTERN.test(name) || name.toLowerCase() === 'name') {
            value = `${faker.lorem.word()}.${fixture.extension}`;
        }

        if (value !== null && fitsLength(property, value)) {
            fields[name] = value;
        }
    }

    return fields;
}
