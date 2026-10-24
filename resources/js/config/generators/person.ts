import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Person generators for creating various person-related values.
 */
export const personGenerators: ValueGenerator[] = [
    {
        id: 'first-name',
        name: 'First Name',
        description: 'Generate a random first name',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.firstName(),
        icon: 'i-hugeicons-user',
    },
    {
        id: 'last-name',
        name: 'Last Name',
        description: 'Generate a random last name',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.lastName(),
        icon: 'i-hugeicons-user',
    },
    {
        id: 'full-name',
        name: 'Full Name',
        description: 'Generate a random full name',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.fullName(),
        icon: 'i-hugeicons-user',
    },
    {
        id: 'job-title',
        name: 'Job Title',
        description: 'Generate a random job title',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.jobTitle(),
        icon: 'i-hugeicons-user',
    },
    {
        id: 'bio',
        name: 'Bio',
        description: 'Generate a random bio',
        category: { id: 'person', name: 'Person' },
        generate: () => faker.person.bio(),
        icon: 'i-hugeicons-user',
    },
];
