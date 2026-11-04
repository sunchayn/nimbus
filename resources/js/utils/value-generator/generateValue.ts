import { getGeneratorById } from '@/config/generators';

/**
 * Generates a value using the generator with the given id.
 *
 * Returns an empty string when no generator has that id.
 */
export function generateValue(generatorId: string): string | number | bigint {
    return getGeneratorById.get(generatorId)?.generate() ?? '';
}
