import { faker } from '@faker-js/faker/locale/en';
import { defineCategory } from '../utils/defineCategory';
import { withinLength } from '../utils/withinLength';

export const system = defineCategory({
    id: 'system',
    name: 'System',
    icon: 'i-hugeicons-shell',
    generators: [
        {
            id: 'semver',
            name: 'Semantic Version',
            description: 'Generate a semantic version',
            icon: 'i-hugeicons-git-branch',
            generate: context => withinLength(context, () => faker.system.semver()),
            nameRules: [{ pattern: /^(version|v|semver)$/i }],
        },
        {
            id: 'extension',
            name: 'File Extension',
            description: 'Generate a file extension',
            icon: 'i-hugeicons-file-cog',
            generate: context => withinLength(context, () => faker.system.fileExt()),
        },
    ],
});
