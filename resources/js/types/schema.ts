/**
 * JSON Schema primitive types.
 * Used for type-safe schema type checking in payload generation.
 */
export enum SchemaType {
    STRING = 'string',
    INTEGER = 'integer',
    NUMBER = 'number',
    BOOLEAN = 'boolean',
    ARRAY = 'array',
    OBJECT = 'object',
}

/**
 * Type guard to check if a value is a valid primitive schema type.
 */
export function isPrimitiveType(type: unknown): type is SchemaType {
    return (
        type === SchemaType.STRING ||
        type === SchemaType.INTEGER ||
        type === SchemaType.NUMBER ||
        type === SchemaType.BOOLEAN
    );
}

/**
 * Type guard to check if a value is a valid complex schema type.
 */
export function isComplexType(type: unknown): type is SchemaType {
    return type === SchemaType.ARRAY || type === SchemaType.OBJECT;
}
