import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Time generators for creating various time-based values.
 */
export const timeGenerators: ValueGenerator[] = [
    {
        id: 'date',
        name: 'Date',
        description: 'Generate a random date',
        category: { id: 'time', name: 'Time' },
        generate: () => faker.date.anytime().toISOString().split('T')[0],
        icon: 'i-hugeicons-calendar-03',
    },
    {
        id: 'datetime',
        name: 'DateTime',
        description: 'Generate a random date and time',
        category: { id: 'time', name: 'Time' },
        generate: () => faker.date.anytime().toISOString(),
        icon: 'i-hugeicons-calendar-days',
    },
    {
        id: 'timestamp',
        name: 'Timestamp',
        description: 'Generate a Unix timestamp',
        category: { id: 'time', name: 'Time' },
        generate: () => Math.floor(faker.date.anytime().getTime() / 1000),
        icon: 'i-hugeicons-clock-01',
    },
    {
        id: 'time',
        name: 'Time',
        description: 'Generate a random time',
        category: { id: 'time', name: 'Time' },
        generate: () => faker.date.anytime().toLocaleTimeString(),
        icon: 'i-hugeicons-clock-01',
    },
];
