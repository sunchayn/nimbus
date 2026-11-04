import type { GeneratorContext } from '@/interfaces/ui';
import { faker } from '@faker-js/faker/locale/en';

/**
 * Builds lorem words that fit the length constraints of the context.
 */
export function loremWithin(
    context: GeneratorContext | undefined,
    wordCount: number,
    separator: string = ' ',
): string {
    const minLength = context?.minLength ?? 0;
    const maxLength = context?.maxLength ?? Infinity;
    const words = faker.lorem.words(wordCount).split(' ');

    while (words.join(separator).length < minLength) {
        words.push(faker.lorem.word());
    }

    while (words.length > 1 && words.join(separator).length > maxLength) {
        words.pop();
    }

    const text = words.join(separator);

    // Removing words for the maximum can leave the text under the minimum.
    if (text.length < minLength) {
        return text + faker.string.alpha(minLength - text.length);
    }

    if (text.length <= maxLength) {
        return text;
    }

    // A single word can still be too long for a very short maximum.
    return faker.string.alpha({
        length: { min: Math.min(minLength, maxLength) || 1, max: maxLength },
    });
}

/**
 * Builds a capitalized sentence that fits the length constraints of the context.
 *
 * The final period counts toward the length.
 */
export function sentenceWithin(
    context: GeneratorContext | undefined,
    wordCount: number,
): string {
    const text = loremWithin(
        {
            minLength: context?.minLength && Math.max(context.minLength - 1, 0),
            maxLength: context?.maxLength && Math.max(context.maxLength - 1, 1),
        },
        wordCount,
    );

    return `${text.charAt(0).toUpperCase()}${text.slice(1)}.`;
}
