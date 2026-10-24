import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Data generators for creating various data-related values.
 */
export const dataGenerators: ValueGenerator[] = [
    {
        id: 'json',
        name: 'JSON',
        description: 'Generate a random JSON object',
        category: { id: 'data', name: 'Data' },
        generate: () =>
            JSON.stringify(
                {
                    id: faker.number.int(),
                    name: faker.person.fullName(),
                    email: faker.internet.email().toLowerCase(),
                    createdAt: faker.date.anytime().toISOString(),
                },
                null,
                2,
            ),
        icon: 'i-hugeicons-source-code',
    },
    {
        id: 'base64',
        name: 'Base64',
        description: 'Generate base64 encoded string',
        category: { id: 'data', name: 'Data' },
        generate: () => btoa(faker.string.alphanumeric(20)),
        icon: 'i-hugeicons-database-01',
    },
    {
        id: 'hex',
        name: 'Hex',
        description: 'Generate a random hex string',
        category: { id: 'data', name: 'Data' },
        generate: () => faker.string.hexadecimal(),
        icon: 'i-hugeicons-database-01',
    },
    {
        id: 'dataUri',
        name: 'Data URI',
        description: 'Generate a random data uri',
        category: { id: 'data', name: 'Data' },
        generate: () => btoa(faker.image.dataUri()),
        icon: 'i-hugeicons-image-01',
    },
    {
        id: 'boolean-as-string',
        name: 'Boolean (As string)',
        description: 'Generate a random boolean',
        category: { id: 'data', name: 'Data' },
        generate: () => String(faker.datatype.boolean()),
        icon: 'i-hugeicons-flag-02',
    },
];
