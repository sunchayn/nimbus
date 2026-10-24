import type { ValueGenerator } from '@/interfaces/ui';
import { faker } from '@faker-js/faker';

/**
 * Text generators for creating various text-based values.
 */
export const textGenerators: ValueGenerator[] = [
    {
        id: 'word',
        name: 'Word',
        description: 'Generate a random word',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.word.sample(),
        icon: 'i-hugeicons-book-open-01',
    },
    {
        id: 'words',
        name: 'Words',
        description: 'Generate multiple random words',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.word.words(3),
        icon: 'i-hugeicons-text-font',
    },
    {
        id: 'words-separated',
        name: 'Words Comma Separated',
        description: 'Generate multiple random words',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.word.words(3).replaceAll(' ', ','),
        icon: 'i-hugeicons-text-font',
    },
    {
        id: 'sentence',
        name: 'Sentence',
        description: 'Generate a random sentence',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.lorem.sentence(),
        icon: 'i-hugeicons-file-01',
    },
    {
        id: 'paragraph',
        name: 'Paragraph',
        description: 'Generate a random paragraph',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.lorem.paragraph(),
        icon: 'i-hugeicons-file-02',
    },
    {
        id: 'slug',
        name: 'Slug',
        description: 'Generate a URL-friendly slug',
        category: { id: 'text', name: 'Text' },
        generate: () => faker.lorem.slug(),
        icon: 'i-hugeicons-text-font',
    },
];
