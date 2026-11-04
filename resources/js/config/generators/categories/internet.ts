import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const internet = defineCategory({
    id: 'internet',
    name: 'Internet',
    icon: 'i-hugeicons-globe-02',
    generators: [
        {
            id: 'url',
            name: 'URL',
            icon: 'i-hugeicons-link-01',
            generate: context => withinLength(context, () => faker.internet.url()),
            nameRules: [
                // The separator or the capital letter stops names like "curl" from matching.
                { pattern: /^(.+[-_])?(url|website|link)$/i },
                { pattern: /^.+(Url|Website|Link)$/ },
            ],
            typeRules: [{ format: 'url' }, { format: 'uri' }],
        },
        {
            id: 'domain',
            name: 'Domain',
            description: 'Generate a random domain name',
            icon: 'i-hugeicons-web-design-01',
            generate: context => withinLength(context, () => faker.internet.domainName()),
            nameRules: [{ pattern: /^(domain|domain[-_]?name)$/i }],
        },
        {
            id: 'username',
            name: 'Username',
            icon: 'i-hugeicons-user-circle',
            generate: context => withinLength(context, () => faker.internet.userName()),
            nameRules: [{ pattern: /^(username|user[-_]?name|login)$/i }],
        },
        {
            id: 'password',
            name: 'Password',
            icon: 'i-hugeicons-lock-password',
            generate: context => {
                if (context?.maxLength === undefined) {
                    return faker.internet.password();
                }

                const min = Math.min(
                    context.minLength ?? context.maxLength,
                    context.maxLength,
                );

                return faker.internet.password({
                    length: faker.number.int({ min, max: context.maxLength }),
                });
            },
            nameRules: [{ pattern: /^(password|pass|pwd)$/i }],
        },
        {
            id: 'ipv4',
            name: 'IP V4',
            description: 'Generate a random IP v4',
            icon: 'i-hugeicons-router-01',
            generate: context => withinLength(context, () => faker.internet.ipv4()),
            nameRules: [{ pattern: /^(ip|ip[-_]?v4)$/i }],
        },
        {
            id: 'ipv6',
            name: 'IP V6',
            description: 'Generate a random IP v6',
            icon: 'i-hugeicons-network',
            generate: context => withinLength(context, () => faker.internet.ipv6()),
            nameRules: [{ pattern: /^(ip[-_]?v6)$/i }],
        },
    ],
});
