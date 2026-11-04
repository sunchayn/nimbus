import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const company = defineCategory({
    id: 'company',
    name: 'Company',
    icon: 'i-hugeicons-building-03',
    generators: [
        {
            id: 'company-name',
            name: 'Company Name',
            icon: 'i-hugeicons-briefcase-01',
            generate: context => withinLength(context, () => faker.company.name()),
            nameRules: [{ pattern: /^(company|company[-_]?name|organization|org)$/i }],
        },
        {
            id: 'catch-phrase',
            name: 'Catch Phrase',
            description: 'Generate a random company catch phrase',
            icon: 'i-hugeicons-megaphone-01',
            generate: context => withinLength(context, () => faker.company.catchPhrase()),
            nameRules: [{ pattern: /^(catch[-_]?phrase|slogan|tagline)$/i }],
        },
        {
            id: 'bs',
            name: 'Buzzword',
            description: 'Generate random business buzzwords',
            icon: 'i-hugeicons-idea-01',
            generate: context => withinLength(context, () => faker.company.buzzPhrase()),
            nameRules: [{ pattern: /^(buzzword|buzz[-_]?phrase)$/i }],
        },
    ],
});
