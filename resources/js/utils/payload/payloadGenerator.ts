import { formatMatchers, nameMatchers } from '@/config/generators';
import { PAYLOAD_GENERATOR_CONFIG } from '@/config/payload-generator';
import type {
    PayloadObject,
    PayloadObjectValue,
    PayloadPrimitive,
} from '@/interfaces/schema/payload';
import type { GeneratorContext } from '@/interfaces/ui';
import { isComplexType, isPrimitiveType, SchemaType } from '@/types/schema';
import { describeFileProperties, pickObjectFixture } from '@/utils/binaries/objectFile';
import { generateFromPattern } from '@/utils/payload/patternGenerator';
import { faker } from '@faker-js/faker/locale/en';
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
 * A nullable property has a type list such as ["string", "null"], the value follows the other type.
 */
function withoutNullType(schema: JSONSchema7): JSONSchema7 {
    if (!Array.isArray(schema.type)) {
        return schema;
    }

    return { ...schema, type: schema.type.find(type => type !== 'null') };
}

function isNullable(schema: JSONSchema7): boolean {
    return Array.isArray(schema.type) && schema.type.includes('null');
}

/**
 * An optional text field that does not accept an empty value is left out sometimes,
 * because sending it empty would fail the validation.
 */
function shouldDropOptionalField(
    schema: JSONSchema7,
    isRequired: boolean,
    isPlaceholder: boolean,
): boolean {
    const type = withoutNullType(schema).type;

    if (isPlaceholder || isRequired || isNullable(schema) || type !== SchemaType.STRING) {
        return false;
    }

    return (
        schema.format !== 'binary' &&
        Math.random() < PAYLOAD_GENERATOR_CONFIG.EMPTY_FIELD_PROBABILITY
    );
}

/**
 * Generates a value with property context (name and required status).
 */
function generateValue(
    rawSchema: JSONSchema7,
    isPlaceholder: boolean,
    propertyName: string,
    isRequired: boolean,
): PayloadObject | PayloadObjectValue | PayloadObject[] {
    const schema = withoutNullType(rawSchema);

    if (schema.enum && schema.enum.length > 0) {
        return generateEnumValue(schema, isPlaceholder);
    }

    if (schema.type && isPrimitiveType(schema.type)) {
        return generatePrimitiveType(
            schema,
            isPlaceholder,
            propertyName,
            isRequired || !isNullable(rawSchema),
        );
    }

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
        () => generateValue(itemsShape, isPlaceholder, 'item', true),
    ) as PayloadPrimitive[];

    return generatedItems.filter(
        (item: PayloadPrimitive) => item !== null && item.toString().length,
    );
}

/**
 * Picks how many items an array gets, within the schema item limits.
 */
function countArrayItems(schema: JSONSchema7, isPlaceholder: boolean): number {
    const minItems =
        schema.minItems ??
        Math.min(PAYLOAD_GENERATOR_CONFIG.MIN_ARRAY_ITEMS, schema.maxItems ?? Infinity);

    if (isPlaceholder) {
        return minItems;
    }

    const maxItems =
        schema.maxItems ?? minItems + PAYLOAD_GENERATOR_CONFIG.MAX_ARRAY_ITEMS - 1;

    return faker.number.int({ min: minItems, max: Math.max(minItems, maxItems) });
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

    const numberOfArrayItemsToGenerate = countArrayItems(schema, isPlaceholder);

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
        const fixture = isPlaceholder ? undefined : pickObjectFixture(schema);

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

            // Fields the API forbids are left out of generated payloads.
            if ((property as { skipAutofill?: boolean }).skipAutofill === true) {
                continue;
            }

            const isRequired = required.includes(key);

            if (shouldDropOptionalField(property, isRequired, isPlaceholder)) {
                continue;
            }

            payload[key] = generateValue(property, isPlaceholder, key, isRequired);
        }

        // The file decides the related fields, so they always describe the same file.
        if (fixture) {
            Object.assign(payload, describeFileProperties(schema, fixture));
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
): string | File {
    // A file cannot be generated, so an empty one marks the field as an upload.
    if (schema.format === 'binary') {
        return new File([], '');
    }

    if (isPlaceholder) {
        return '<placeholder>';
    }

    // Only a field that accepts an empty value is randomly sent empty.
    if (!isRequired && Math.random() < PAYLOAD_GENERATOR_CONFIG.EMPTY_FIELD_PROBABILITY) {
        return '';
    }

    const generatorContext: GeneratorContext = {};

    if (schema.minLength) {
        generatorContext.minLength = schema.minLength;
    }

    if (schema.maxLength) {
        generatorContext.maxLength = schema.maxLength;
    }

    const { dateFormat } = schema as { dateFormat?: string };

    if (dateFormat) {
        generatorContext.dateFormat = dateFormat;
    }

    const generatedValue = generateFromMatchingGenerator(
        schema,
        generatorContext,
        propertyName,
    );

    if (generatedValue !== null) {
        return generatedValue.toString();
    }

    const length = {
        ...PAYLOAD_GENERATOR_CONFIG.STRING_LENGTH,
        ...generatorContext, // <- Overwrites default boundaries.
    };

    return faker.string.alpha({
        length: {
            min: Math.min(length.minLength, length.maxLength),
            max: length.maxLength,
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

    const schemaRange: GeneratorContext = {};

    if (schema.minimum !== undefined) {
        schemaRange.min = schema.minimum;
    }

    if (schema.maximum !== undefined) {
        schemaRange.max = schema.maximum;
    }

    const generatedValue = generateFromMatchingGenerator(
        schema,
        schemaRange,
        propertyName,
        true,
    );

    if (generatedValue !== null && typeof generatedValue === 'number') {
        return generatedValue;
    }

    return faker.number.int({ ...PAYLOAD_GENERATOR_CONFIG.NUMBER_RANGE, ...schemaRange });
}

/**
 * Gets the appropriate generator for a schema property with context
 */
function generateFromMatchingGenerator(
    schema: JSONSchema7,
    schemaContext: GeneratorContext,
    propertyName: string,
    shouldBeInteger: boolean = false,
): string | number | bigint | null {
    // Base64 content is selected through the byte format, whichever way the schema says it.
    // A date format wins over the schema format, and both date generators follow it.
    const format = schemaContext.dateFormat
        ? 'date-time'
        : schema.contentEncoding === 'base64'
          ? 'byte'
          : schema.format;
    const formatGenerator = format ? formatMatchers.get(format) : undefined;

    if (formatGenerator) {
        return formatGenerator.generate(schemaContext);
    }

    // A pattern is a hard rule, so it wins over the name of the property.
    const fromPattern = schema.pattern
        ? generateFromPattern(
              schema.pattern,
              schemaContext.maxLength,
              schemaContext.minLength,
          )
        : null;

    if (fromPattern !== null) {
        return fromPattern;
    }

    for (const matcher of nameMatchers) {
        if (shouldBeInteger && !matcher.returnsInteger) {
            continue;
        }

        if (!matcher.pattern.test(propertyName)) {
            continue;
        }

        return matcher.generator.generate({
            ...matcher.defaultParameters,
            ...schemaContext, // <- Overwrites default parameters.
        });
    }

    return null;
}
