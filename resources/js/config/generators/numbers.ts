import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Number generators for creating various numeric values.
 */
export const numberGenerators: ValueGenerator[] = [
    {
        id: 'int',
        name: 'Integer',
        description: 'Generate a random integer',
        category: { id: 'numbers', name: 'Numbers' },
        generate: config => faker.number.int(config ?? { min: 1, max: 1000 }),
        icon: 'i-hugeicons-hashtag',
    },
    {
        id: 'float',
        name: 'Float',
        description: 'Generate a random decimal number',
        category: { id: 'numbers', name: 'Numbers' },
        generate: config =>
            faker.number.float(config ?? { min: 0, max: 100, fractionDigits: 2 }),
        icon: 'i-hugeicons-percent',
    },
    {
        id: 'bigint',
        name: 'BigInt',
        description: 'Generate a random big integer',
        category: { id: 'numbers', name: 'Numbers' },
        generate: config => faker.number.bigInt(config ?? {}),
        icon: 'i-hugeicons-hashtag',
    },
];
