import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Company generators for creating various company-related values.
 */
export const companyGenerators: ValueGenerator[] = [
    {
        id: 'company-name',
        name: 'Company Name',
        description: 'Generate a random company name',
        category: { id: 'company', name: 'Company' },
        generate: () => faker.company.name(),
        icon: 'i-hugeicons-building-03',
    },
    {
        id: 'catch-phrase',
        name: 'Catch Phrase',
        description: 'Generate a random company catch phrase',
        category: { id: 'company', name: 'Company' },
        generate: () => faker.company.catchPhrase(),
        icon: 'i-hugeicons-building-03',
    },
    {
        id: 'bs',
        name: 'Buzzword',
        description: 'Generate random business buzzwords',
        category: { id: 'company', name: 'Company' },
        generate: () => faker.company.buzzPhrase(),
        icon: 'i-hugeicons-building-03',
    },
];
