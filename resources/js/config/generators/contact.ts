import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Contact generators for creating various contact-related values.
 */
export const contactGenerators: ValueGenerator[] = [
    {
        id: 'email',
        name: 'Email',
        description: 'Generate a random email address',
        category: { id: 'contact', name: 'Contact' },
        generate: () => faker.internet.email().toLowerCase(),
        icon: 'i-hugeicons-mail-01',
    },
    {
        id: 'phone',
        name: 'Phone',
        description: 'Generate a random phone number',
        category: { id: 'contact', name: 'Contact' },
        generate: () => faker.phone.number({ style: 'national' }),
        icon: 'i-hugeicons-call-ringing-01',
    },
    {
        id: 'mobile',
        name: 'Mobile',
        description: 'Generate a random mobile number',
        category: { id: 'contact', name: 'Contact' },
        generate: () => faker.phone.number(),
        icon: 'i-hugeicons-call',
    },
];
