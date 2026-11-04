import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const time = defineCategory({
    id: 'time',
    name: 'Time',
    icon: 'i-hugeicons-clock-01',
    generators: [
        {
            id: 'date',
            name: 'Date',
            icon: 'i-hugeicons-calendar-03',
            generate: context =>
                withinLength(
                    context,
                    () => faker.date.anytime().toISOString().split('T')[0],
                ),
            nameRules: [{ pattern: /^(date)$/i }],
            typeRules: [{ format: 'date' }],
        },
        {
            id: 'datetime',
            name: 'DateTime',
            description: 'Generate a random date and time',
            icon: 'i-hugeicons-calendar-02',
            generate: context =>
                withinLength(context, () => faker.date.anytime().toISOString()),
            nameRules: [{ pattern: /^(datetime|date[-_]?time)$/i }],
            typeRules: [{ format: 'date-time' }],
        },
        {
            id: 'timestamp',
            name: 'Timestamp',
            description: 'Generate a Unix timestamp',
            icon: 'i-hugeicons-timer-01',
            generate: context => {
                if (context?.min === undefined && context?.max === undefined) {
                    return Math.floor(faker.date.anytime().getTime() / 1000);
                }

                return faker.number.int({
                    min: context.min ?? 0,
                    max: context.max ?? Math.max(context.min ?? 0, 4102444800),
                });
            },
            nameRules: [{ pattern: /^(.+[-_])?timestamp$/i, returnsInteger: true }],
        },
        {
            id: 'time',
            name: 'Time',
            icon: 'i-hugeicons-clock-02',
            generate: context =>
                withinLength(context, () => faker.date.anytime().toLocaleTimeString()),
            nameRules: [{ pattern: /^(time)$/i }],
            typeRules: [{ format: 'time' }],
        },
    ],
});
