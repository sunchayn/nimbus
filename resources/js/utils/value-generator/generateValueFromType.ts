import { GeneratorType } from '@/interfaces/http';

type GenerateValue = (generatorId: string) => string | number | bigint;

/**
 * Generates a value based on the specified generator type.
 *
 * Maps GeneratorType enum values to their corresponding generator ids.
 *
 * @param generatorType - The type of value to generate (Uuid, Email, String, etc.)
 * @param generate - Produces a value for a generator id
 * @returns The generated string value
 */
export function generateValueFromType(
    generatorType: GeneratorType,
    generate: GenerateValue,
): string {
    switch (generatorType) {
        case GeneratorType.Uuid:
            return String(generate('uuid'));
        case GeneratorType.Email:
            return String(generate('email'));
        case GeneratorType.String:
        default:
            return String(generate('word'));
    }
}
