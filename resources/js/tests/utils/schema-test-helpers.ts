import type { JSONSchema7 } from 'json-schema';

/**
 * Test utilities for creating JSON Schema objects.
 */

/**
 * Creates a simple string schema for testing.
 */
export function createStringSchema(format?: string): JSONSchema7 {
    const schema: JSONSchema7 = {
        type: 'string',
    };

    if (format) {
        schema.format = format;
    }

    return schema;
}

/**
 * Creates a simple integer schema for testing.
 */
export function createIntegerSchema(): JSONSchema7 {
    return {
        type: 'integer',
    };
}

/**
 * Creates a simple number schema for testing.
 */
export function createNumberSchema(): JSONSchema7 {
    return {
        type: 'number',
    };
}

/**
 * Creates a simple boolean schema for testing.
 */
export function createBooleanSchema(): JSONSchema7 {
    return {
        type: 'boolean',
    };
}

/**
 * Creates an object schema for testing.
 */
export function createObjectSchema(
    properties: Record<string, JSONSchema7>,
    requiredFields: string[] = [],
): JSONSchema7 {
    return {
        type: 'object',
        properties,
        required: requiredFields,
    };
}

/**
 * Creates an array schema for testing.
 */
export function createArraySchema(items: JSONSchema7): JSONSchema7 {
    return {
        type: 'array',
        items,
    };
}
