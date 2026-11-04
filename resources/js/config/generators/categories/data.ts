import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const data = defineCategory({
    id: 'data',
    name: 'Data',
    icon: 'i-hugeicons-chart-bar-line',
    generators: [
        {
            id: 'json',
            name: 'JSON',
            description: 'Generate a random JSON object',
            icon: 'i-hugeicons-source-code',
            generate: context =>
                withinLength(context, () =>
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
                ),
        },
        {
            id: 'base64',
            name: 'Base64',
            description: 'Generate base64 encoded string',
            icon: 'i-hugeicons-binary-code',
            generate: context => {
                // Every 3 bytes become 4 base64 characters.
                const bytes = Math.min(
                    20,
                    Math.max(3, Math.floor((context?.maxLength ?? 28) / 4) * 3),
                );

                return withinLength(context, () =>
                    btoa(faker.string.alphanumeric(bytes)),
                );
            },
        },
        {
            id: 'hex',
            name: 'Hex',
            description: 'Generate a random hex string',
            icon: 'i-hugeicons-hash',
            generate: context =>
                withinLength(context, () => faker.string.hexadecimal(), {
                    truncate: true,
                }),
        },
        {
            id: 'dataUri',
            name: 'Data URI',
            description: 'Generate a random data uri',
            icon: 'i-hugeicons-image-01',
            generate: context => withinLength(context, () => btoa(faker.image.dataUri())),
            nameRules: [{ pattern: /^(.+[-_])?(image|file|blob)s?$/i }],
        },
        {
            id: 'boolean-as-string',
            name: 'Boolean (As string)',
            description: 'Generate a random boolean',
            icon: 'i-hugeicons-toggle-on',
            generate: context =>
                withinLength(context, () => String(faker.datatype.boolean())),
            // Case sensitive on purpose, so words like "issue" or "hash" do not match.
            nameRules: [{ pattern: /^(is|has|was)([-_].+|[A-Z].*)$/ }],
        },
    ],
});
