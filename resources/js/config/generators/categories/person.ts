import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const person = defineCategory({
    id: 'person',
    name: 'Person',
    icon: 'i-hugeicons-user',
    generators: [
        {
            id: 'first-name',
            name: 'First Name',
            icon: 'i-hugeicons-user',
            generate: context => withinLength(context, () => faker.person.firstName()),
            nameRules: [{ pattern: /^(first[-_]?name|firstname|fname)$/i }],
        },
        {
            id: 'last-name',
            name: 'Last Name',
            icon: 'i-hugeicons-user-02',
            generate: context => withinLength(context, () => faker.person.lastName()),
            nameRules: [{ pattern: /^(last[-_]?name|lastname|surname|lname)$/i }],
        },
        {
            id: 'full-name',
            name: 'Full Name',
            icon: 'i-hugeicons-id-card',
            generate: context => withinLength(context, () => faker.person.fullName()),
            nameRules: [{ pattern: /^(full[-_]?name|fullname|name)$/i }],
        },
        {
            id: 'job-title',
            name: 'Job Title',
            icon: 'i-hugeicons-briefcase-dollar',
            generate: context => withinLength(context, () => faker.person.jobTitle()),
            nameRules: [{ pattern: /^(job[-_]?title|title|position|role)$/i }],
        },
        {
            id: 'bio',
            name: 'Bio',
            icon: 'i-hugeicons-note-01',
            generate: context => withinLength(context, () => faker.person.bio()),
            nameRules: [{ pattern: /^(bio|biography|about)$/i }],
        },
    ],
});
