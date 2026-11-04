import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';

export const numbers = defineCategory({
    id: 'numbers',
    name: 'Numbers',
    icon: 'i-hugeicons-hashtag',
    generators: [
        {
            id: 'int',
            name: 'Integer',
            icon: 'i-hugeicons-calculator-01',
            generate: context =>
                faker.number.int({ min: context?.min ?? 1, max: context?.max ?? 1000 }),
            nameRules: [
                {
                    pattern: /^(cvv)$/i,
                    returnsInteger: true,
                    defaultParameters: { min: 100, max: 999 },
                },
                {
                    pattern: /^age$/i,
                    returnsInteger: true,
                    defaultParameters: { min: 1, max: 100 },
                },
                {
                    pattern: /^(.+[-_])?year$/i,
                    returnsInteger: true,
                    defaultParameters: { min: 1, max: 3000 },
                },
                {
                    pattern: /^(.+[-_])?month$/i,
                    returnsInteger: true,
                    defaultParameters: { min: 1, max: 12 },
                },
                {
                    pattern: /^(.+[-_])?day$/i,
                    returnsInteger: true,
                    defaultParameters: { min: 1, max: 31 },
                },
                {
                    pattern: /^(page)$/i,
                    returnsInteger: true,
                    defaultParameters: { min: 1, max: 99 },
                },
                {
                    pattern: /^per_page$/i,
                    returnsInteger: true,
                    defaultParameters: { min: 25, max: 100 },
                },
            ],
        },
        {
            id: 'float',
            name: 'Float',
            description: 'Generate a random decimal number',
            icon: 'i-hugeicons-percent',
            generate: context =>
                faker.number.float({
                    min: context?.min ?? 0,
                    max: context?.max ?? 100,
                    fractionDigits: context?.fractionDigits ?? 2,
                }),
        },
        {
            id: 'bigint',
            name: 'BigInt',
            description: 'Generate a random big integer',
            icon: 'i-hugeicons-hashtag',
            generate: context =>
                faker.number.bigInt({ min: context?.min, max: context?.max }),
        },
    ],
});
