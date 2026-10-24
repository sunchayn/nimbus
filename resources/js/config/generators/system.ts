import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * System generators for creating various system values.
 */
export const systemGenerators: ValueGenerator[] = [
    {
        id: 'semver',
        name: 'Semantic Version',
        description: 'Generate a semantic version',
        category: { id: 'system', name: 'System' },
        generate: () => faker.system.semver(),
        icon: 'i-hugeicons-finger-print',
    },
    {
        id: 'extension',
        name: 'File Extension',
        description: 'Generate a file extension',
        category: { id: 'system', name: 'System' },
        generate: () => faker.system.fileExt(),
        icon: 'i-hugeicons-file-cog',
    },
];
