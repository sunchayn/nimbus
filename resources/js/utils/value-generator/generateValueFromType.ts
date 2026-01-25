import { GeneratorType } from '@/interfaces/http';
import type { useValueGeneratorStore } from '@/stores';

/**
 * Generates a value based on the specified generator type.
 *
 * Maps GeneratorType enum values to their corresponding value generator commands.
 *
 * @param generatorType - The type of value to generate (Uuid, Email, String, etc.)
 * @param valueGeneratorStore - The value generator store instance
 * @returns The generated string value
 */
export function generateValueFromType(
    generatorType: GeneratorType,
    valueGeneratorStore: ReturnType<typeof useValueGeneratorStore>,
): string {
    switch (generatorType) {
        case GeneratorType.Uuid:
            return valueGeneratorStore.generateValue('uuid') as string;
        case GeneratorType.Email:
            return valueGeneratorStore.generateValue('email') as string;
        case GeneratorType.String:
            return valueGeneratorStore.generateValue('word') as string;
        default:
            return valueGeneratorStore.generateValue('word') as string;
    }
}
