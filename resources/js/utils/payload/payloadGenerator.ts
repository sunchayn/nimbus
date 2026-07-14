import { allValueGenerators, PROPERTY_NAME_PATTERNS } from '@/config/generators';
import { PAYLOAD_GENERATOR_CONFIG } from '@/config/payload-generator';
import type {
    PayloadObject,
    PayloadObjectValue,
    PayloadPrimitive,
} from '@/interfaces/schema/payload';
import type { ValueGenerator } from '@/interfaces/ui';
import { isComplexType, isPrimitiveType, SchemaType } from '@/types/schema';
import { faker } from '@faker-js/faker';
import type { JSONSchema7 } from 'json-schema';

/**
 * Generates random data based on schema
 */
export function generateRandomPayload(schema: JSONSchema7): PayloadObject {
    return generatePayload(schema, false);
}

/**
 * Generates placeholder data based on schema
 */
export function generatePlaceholderPayload(schema: JSONSchema7): PayloadObject {
    return generatePayload(schema, true);
}

/**
 * Handles enum value generation
 */
function generateEnumValue(
    schema: JSONSchema7,
    isPlaceholder: boolean,
): PayloadObjectValue {
    if (!schema.enum || schema.enum.length === 0) {
        throw new Error('Enum values are required for enum type');
    }

    return isPlaceholder
        ? (schema.enum[0] as PayloadObjectValue)
        : (faker.helpers.arrayElement(schema.enum) as PayloadObjectValue);
}

/**
 * Handles primitive type generation (string, number, integer, boolean)
 */
function generatePrimitiveType(
    schema: JSONSchema7,
    isPlaceholder: boolean,
    propertyName: string,
    isRequired: boolean,
): PayloadObjectValue {
    switch (schema.type) {
        case SchemaType.STRING:
            return generateString(schema, isPlaceholder, propertyName, isRequired);
        case SchemaType.NUMBER:
        case SchemaType.INTEGER:
            return generateInteger(schema, isPlaceholder, propertyName);
        case SchemaType.BOOLEAN:
            return isPlaceholder ? false : faker.datatype.boolean();
        default:
            throw new Error(`Unsupported primitive type: ${schema.type}`);
    }
}

/**
 * Handles complex type generation (array, object)
 */
function generateComplexType(
    schema: JSONSchema7,
    isPlaceholder: boolean,
): PayloadObject | PayloadObject[] | PayloadPrimitive[] {
    switch (schema.type) {
        case SchemaType.ARRAY:
            return generateArray(schema, isPlaceholder);
        case SchemaType.OBJECT:
            return schema.properties ? generatePayload(schema, isPlaceholder) : {};
        default:
            throw new Error(`Unsupported complex type: ${schema.type}`);
    }
}

/**
 * Generates a value with property context (name and required status).
 */
function generateValue(
    schema: JSONSchema7,
    isPlaceholder: boolean,
    propertyName: string,
    isRequired: boolean,
): PayloadObject | PayloadObjectValue | PayloadObject[] {
    if (schema.enum && schema.enum.length > 0) {
        return generateEnumValue(schema, isPlaceholder);
    }

    // Handle primitive types
    if (schema.type && isPrimitiveType(schema.type)) {
        return generatePrimitiveType(schema, isPlaceholder, propertyName, isRequired);
    }

    // Handle complex types
    if (schema.type && isComplexType(schema.type)) {
        return generateComplexType(schema, isPlaceholder);
    }

    return null;
}

function generateArrayOfObjects(
    schema: JSONSchema7,
    isPlaceholder: boolean,
    numberOfArrayItemsToGenerate: number,
): PayloadObject[] {
    return Array.from({ length: numberOfArrayItemsToGenerate }, () =>
        generatePayload(schema, isPlaceholder),
    );
}

function generateArrayOfPrimitives(
    itemsShape: JSONSchema7,
    isPlaceholder: boolean,
    numberOfArrayItemsToGenerate: number,
): PayloadPrimitive[] {
    const generatedItems: PayloadPrimitive[] = Array.from(
        { length: numberOfArrayItemsToGenerate },
        () => generateValue(itemsShape, isPlaceholder, 'item', false),
    ) as PayloadPrimitive[];

    return generatedItems.filter(
        (item: PayloadPrimitive) => item !== null && item.toString().length,
    );
}

/**
 * Generates array values (primitives or objects)
 */
function generateArray(
    schema: JSONSchema7,
    isPlaceholder: boolean,
): PayloadPrimitive[] | PayloadObject[] {
    const itemsSchema = schema.items;

    // Skip if items is not defined or not a valid schema object
    if (
        itemsSchema === undefined ||
        typeof itemsSchema !== 'object' ||
        itemsSchema === null ||
        Array.isArray(itemsSchema)
    ) {
        return [];
    }

    const numberOfArrayItemsToGenerate = isPlaceholder
        ? PAYLOAD_GENERATOR_CONFIG.MIN_ARRAY_ITEMS
        : PAYLOAD_GENERATOR_CONFIG.MIN_ARRAY_ITEMS +
          Math.floor(Math.random() * PAYLOAD_GENERATOR_CONFIG.MAX_ARRAY_ITEMS);

    if (itemsSchema.properties !== undefined) {
        return generateArrayOfObjects(
            itemsSchema,
            isPlaceholder,
            numberOfArrayItemsToGenerate,
        );
    }

    return generateArrayOfPrimitives(
        itemsSchema,
        isPlaceholder,
        numberOfArrayItemsToGenerate,
    );
}

/**
 * Generates complete payload object from schema
 */
function generatePayload(
    schema: JSONSchema7,
    isPlaceholder: boolean = false,
): PayloadObject {
    const payload: PayloadObject = {};

    if (schema.properties && typeof schema.properties === 'object') {
        const required = schema.required || [];

        for (const key in schema.properties) {
            const property = schema.properties[key];

            // Skip if property is not a valid schema object
            if (
                typeof property !== 'object' ||
                property === null ||
                Array.isArray(property)
            ) {
                continue;
            }

            const isRequired = required.includes(key);

            payload[key] = generateValue(property, isPlaceholder, key, isRequired);
        }
    }

    return payload;
}

/**
 * Generates a string value with property context
 */
function generateString(
    schema: JSONSchema7,
    isPlaceholder: boolean,
    propertyName: string,
    isRequired: boolean,
): string {
    if (isPlaceholder) {
        return '<placeholder>';
    }

    // Randomly return empty value for non-required fields
    if (!isRequired && Math.random() < PAYLOAD_GENERATOR_CONFIG.EMPTY_FIELD_PROBABILITY) {
        return '';
    }

    const range: { minLength: number; maxLength: number } = {
        ...PAYLOAD_GENERATOR_CONFIG.STRING_LENGTH,
    };

    if (schema.minLength) {
        range.minLength = schema.minLength;
    }

    if (schema.maxLength) {
        range.maxLength = schema.maxLength;
    }

    const generatedValue = generateFromMatchingGenerator(schema, range, propertyName);

    if (generatedValue !== null) {
        return !(typeof generatedValue === 'string')
            ? generatedValue.toString()
            : generatedValue;
    }

    // Fallback to random alpha string
    return faker.string.alpha({
        length: {
            min: range.minLength,
            max: range.maxLength,
        },
    });
}

/**
 * Generates an integer value with property context
 */
function generateInteger(
    schema: JSONSchema7,
    isPlaceholder: boolean,
    propertyName: string,
): number {
    if (isPlaceholder) {
        return 0;
    }

    const range: { min: number; max: number } = {
        ...PAYLOAD_GENERATOR_CONFIG.NUMBER_RANGE,
    };

    if (schema.minimum) {
        range.min = schema.minimum;
    }

    if (schema.maximum) {
        range.max = schema.maximum;
    }

    const generatedValue = generateFromMatchingGenerator(
        schema,
        range,
        propertyName,
        true,
    );

    if (generatedValue !== null && typeof generatedValue === 'number') {
        return generatedValue;
    }

    // Fallback to random number
    return faker.number.int(range);
}

/**
 * Format to generator ID mapping.
 */
const FORMAT_TO_GENERATOR: Record<string, string> = {
    uuid: 'uuid',
    email: 'email',
    'date-time': 'datetime',
    url: 'url',
    date: 'date',
    time: 'time',
    uri: 'url',
};

/**
 * Gets the appropriate generator for a schema property with context
 */
function generateFromMatchingGenerator(
    schema: JSONSchema7,
    range: { min?: number; max?: number; minLength?: number; maxLength?: number } = {},
    propertyName: string,
    shouldBeInteger: boolean = false,
): string | number | bigint | null {
    if (schema.format && FORMAT_TO_GENERATOR[schema.format]) {
        const generatorId = FORMAT_TO_GENERATOR[schema.format];

        // Prioritized the defined format if defined
        return (
            allValueGenerators
                .find((generator: ValueGenerator) => generator.id === generatorId)
                ?.generate() ?? null
        );
    }

    // Try to guess a generator based on the property name
    for (const patternSettings of PROPERTY_NAME_PATTERNS) {
        if (shouldBeInteger && !patternSettings.isInteger) {
            continue;
        }

        if (!patternSettings.pattern.test(propertyName)) {
            continue;
        }

        const matchingGenerator = allValueGenerators.find(
            (generator: ValueGenerator) => generator.id === patternSettings.generatorId,
        );

        if (!matchingGenerator) {
            continue;
        }

        return (
            matchingGenerator.generate({
                ...patternSettings.generatorConfig,
                ...range,
            }) ?? null
        );
    }

    return null;
}
