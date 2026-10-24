import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Internet generators for creating various internet-related values.
 */
export const internetGenerators: ValueGenerator[] = [
    {
        id: 'url',
        name: 'URL',
        description: 'Generate a random URL',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.url(),
        icon: 'i-hugeicons-globe-02',
    },
    {
        id: 'domain',
        name: 'Domain',
        description: 'Generate a random domain name',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.domainName(),
        icon: 'i-hugeicons-globe-02',
    },
    {
        id: 'username',
        name: 'Username',
        description: 'Generate a random username',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.userName(),
        icon: 'i-hugeicons-globe-02',
    },
    {
        id: 'password',
        name: 'Password',
        description: 'Generate a random password',
        category: { id: 'internet', name: 'Internet' },
        generate: config => faker.internet.password(config ?? {}),
        icon: 'i-hugeicons-globe-02',
    },
    {
        id: 'ipv4',
        name: 'IP V4',
        description: 'Generate a random IP v4',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.ipv4(),
        icon: 'i-hugeicons-hierarchy',
    },
    {
        id: 'ipv6',
        name: 'IP V6',
        description: 'Generate a random IP v6',
        category: { id: 'internet', name: 'Internet' },
        generate: () => faker.internet.ipv6(),
        icon: 'i-hugeicons-hierarchy',
    },
];
