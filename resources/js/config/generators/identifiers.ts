import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Identifier generators for creating various identifier values.
 */
export const identifierGenerators: ValueGenerator[] = [
    {
        id: 'uuid',
        name: 'UUID',
        description: 'Generate a UUID v4',
        category: { id: 'identifiers', name: 'Identifiers' },
        generate: () => faker.string.uuid(),
        icon: 'i-hugeicons-finger-print',
    },
    {
        id: 'nanoid',
        name: 'NanoID',
        description: 'Generate a NanoID',
        category: { id: 'identifiers', name: 'Identifiers' },
        generate: () => faker.string.nanoid(),
        icon: 'i-hugeicons-key-01',
    },
    {
        id: 'alphanumeric',
        name: 'Alphanumeric',
        description: 'Generate random alphanumeric string',
        category: { id: 'identifiers', name: 'Identifiers' },
        generate: () => faker.string.alphanumeric(10),
        icon: 'i-hugeicons-hashtag',
    },
];
