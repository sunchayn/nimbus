import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const identifiers = defineCategory({
    id: 'identifiers',
    name: 'Identifiers',
    icon: 'i-hugeicons-credit-card',
    generators: [
        {
            id: 'uuid',
            name: 'UUID',
            description: 'Generate a UUID v4',
            icon: 'i-hugeicons-finger-print',
            generate: context =>
                withinLength(context, () => faker.string.uuid(), { truncate: true }),
            nameRules: [{ pattern: /^(uuid|guid|id)$/i }, { pattern: /^.+[-_]id$/i }],
            typeRules: [{ format: 'uuid' }],
        },
        {
            id: 'nanoid',
            name: 'NanoID',
            description: 'Generate a NanoID',
            icon: 'i-hugeicons-key-01',
            generate: context =>
                faker.string.nanoid(
                    context?.maxLength === undefined
                        ? undefined
                        : { min: context.minLength ?? 1, max: context.maxLength },
                ),
            nameRules: [{ pattern: /^(nanoid|nano[-_]?id)$/i }],
        },
        {
            id: 'alphanumeric',
            name: 'Alphanumeric',
            description: 'Generate random alphanumeric string',
            icon: 'i-hugeicons-shield-key',
            generate: context => {
                const max = context?.maxLength ?? Math.max(context?.minLength ?? 0, 10);
                const min = context?.minLength ?? Math.min(10, max);

                return faker.string.alphanumeric({ length: { min, max } });
            },
            nameRules: [{ pattern: /^(code|token|key)$/i }],
        },
    ],
});
