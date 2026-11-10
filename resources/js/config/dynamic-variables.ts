import { generateValue } from '@/utils/value-generator/generateValue';

export interface DynamicVariable {
    name: string;
    description: string;
    // The id of the value generator that produces the value.
    generatorId: string;
}

/**
 * Matches a dynamic variable such as {$uuid}. It has its own syntax,
 * so it never competes with the {{ name }} syntax of environment variables.
 */
export const DYNAMIC_PLACEHOLDER_PATTERN = /(?<!\{)\{\$([A-Za-z][A-Za-z0-9]*)\}/g;

/**
 * Variables written as {$name}. Each one maps to an existing value generator.
 */
export const DYNAMIC_VARIABLES: readonly DynamicVariable[] = [
    { name: 'uuid', description: 'A random UUID', generatorId: 'uuid' },
    { name: 'timestamp', description: 'A Unix timestamp', generatorId: 'timestamp' },
    {
        name: 'isoDate',
        description: 'An ISO 8601 date and time',
        generatorId: 'datetime',
    },
    { name: 'randomInt', description: 'A random integer', generatorId: 'int' },
    { name: 'email', description: 'A random email address', generatorId: 'email' },
];

const findDynamicVariable = (name: string): DynamicVariable | undefined =>
    DYNAMIC_VARIABLES.find(entry => entry.name === name);

/**
 * Generates a value for a name such as uuid, or returns null for an unknown name.
 * With a cache, a name keeps the same value until the cache is dropped.
 */
export const resolveDynamicVariable = (
    name: string,
    cache: Map<string, string> | null = null,
): string | null => {
    const variable = findDynamicVariable(name);

    if (!variable) {
        return null;
    }

    const cached = cache?.get(name);

    if (cached !== undefined) {
        return cached;
    }

    const value = String(generateValue(variable.generatorId));

    cache?.set(name, value);

    return value;
};

/**
 * Replaces every known dynamic variable in a text with a generated value.
 */
export const resolveDynamicPlaceholders = (
    text: string,
    cache: Map<string, string> | null = null,
): string =>
    text.replace(
        DYNAMIC_PLACEHOLDER_PATTERN,
        (match, name: string) => resolveDynamicVariable(name, cache) ?? match,
    );
