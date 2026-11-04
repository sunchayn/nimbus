import { defineCategory } from '../utils/defineCategory';
import { loremWithin, sentenceWithin } from '../utils/loremWithin';

export const text = defineCategory({
    id: 'text',
    name: 'Text',
    icon: 'i-hugeicons-file-02',
    generators: [
        {
            id: 'word',
            name: 'Word',
            icon: 'i-hugeicons-text-font',
            generate: context => loremWithin(context, 1),
            nameRules: [{ pattern: /^(word|tag|keyword)$/i }],
        },
        {
            id: 'words',
            name: 'Words',
            description: 'Generate multiple random words',
            icon: 'i-hugeicons-text-align-left-01',
            generate: context => loremWithin(context, 3),
        },
        {
            id: 'words-separated',
            name: 'Words Comma Separated',
            description: 'Generate multiple random words',
            icon: 'i-hugeicons-label',
            generate: context => loremWithin(context, 3, ','),
            nameRules: [{ pattern: /^(.+[-_])?(words|tags|keywords)$/i }],
        },
        {
            id: 'sentence',
            name: 'Sentence',
            icon: 'i-hugeicons-file-01',
            generate: context => sentenceWithin(context, 6),
            nameRules: [
                { pattern: /^(.+[-_])?(sentence|text|description|note)s?$/i },
                { pattern: /^(.+[-_])?(paragraph|content|body|message)s?$/i },
            ],
        },
        {
            id: 'paragraph',
            name: 'Paragraph',
            icon: 'i-hugeicons-paragraph',
            generate: context => sentenceWithin(context, 20),
        },
        {
            id: 'slug',
            name: 'Slug',
            description: 'Generate a URL-friendly slug',
            icon: 'i-hugeicons-link-02',
            generate: context => loremWithin(context, 3, '-'),
            nameRules: [{ pattern: /^(slug)$/i }],
        },
    ],
});
