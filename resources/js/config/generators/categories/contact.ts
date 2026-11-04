import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const contact = defineCategory({
    id: 'contact',
    name: 'Contact',
    icon: 'i-hugeicons-call',
    generators: [
        {
            id: 'email',
            name: 'Email',
            description: 'Generate a random email address',
            icon: 'i-hugeicons-mail-01',
            generate: context =>
                withinLength(context, () => faker.internet.email().toLowerCase()),
            nameRules: [{ pattern: /^(email|e[-_]?mail)$/i }],
            typeRules: [{ format: 'email' }],
        },
        {
            id: 'phone',
            name: 'Phone',
            description: 'Generate a random phone number',
            icon: 'i-hugeicons-call-02',
            generate: context =>
                withinLength(context, () => faker.phone.number({ style: 'national' }), {
                    truncate: true,
                }),
            nameRules: [{ pattern: /^(phone|telephone|tel)$/i }],
        },
        {
            id: 'mobile',
            name: 'Mobile',
            description: 'Generate a random mobile number',
            icon: 'i-hugeicons-smart-phone-01',
            generate: context =>
                withinLength(context, () => faker.phone.number(), { truncate: true }),
            nameRules: [{ pattern: /^(mobile|cell|cellular)$/i }],
        },
    ],
});
