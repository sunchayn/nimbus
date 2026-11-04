import { loremWithin, sentenceWithin } from '@/config/generators/utils/loremWithin';
import { describe, expect, it } from 'vitest';

const RUNS = 30;

describe('loremWithin', () => {
    it('builds the requested number of words when nothing constrains it', () => {
        // Act

        const text = loremWithin(undefined, 3);

        // Assert

        expect(text.split(' ')).toHaveLength(3);
    });

    it('joins the words with the given separator', () => {
        // Act

        const text = loremWithin(undefined, 3, '-');

        // Assert

        expect(text).toMatch(/^[a-z]+(-[a-z]+){2}$/);
    });

    it('removes words to respect the maximum length', () => {
        for (let run = 0; run < RUNS; run++) {
            // Act

            const text = loremWithin({ maxLength: 12 }, 6);

            // Assert

            expect(text.length).toBeLessThanOrEqual(12);
        }
    });

    it('adds words to reach the minimum length', () => {
        for (let run = 0; run < RUNS; run++) {
            // Act

            const text = loremWithin({ minLength: 60 }, 2);

            // Assert

            expect(text.length).toBeGreaterThanOrEqual(60);
        }
    });

    it('respects both lengths when the window is tight', () => {
        for (let run = 0; run < 200; run++) {
            // Act

            const text = loremWithin({ minLength: 30, maxLength: 31 }, 3);

            // Assert

            expect(text.length).toBeGreaterThanOrEqual(30);
            expect(text.length).toBeLessThanOrEqual(31);
        }
    });

    it('falls back to letters when a single word is too long', () => {
        // Act

        const text = loremWithin({ maxLength: 1 }, 3);

        // Assert

        expect(text).toMatch(/^[a-zA-Z]$/);
    });
});

describe('sentenceWithin', () => {
    it('capitalizes the sentence and ends it with a period', () => {
        // Act

        const sentence = sentenceWithin(undefined, 6);

        // Assert

        expect(sentence).toMatch(/^[A-Z][a-z ]*\.$/);
    });

    it('counts the period in the maximum length', () => {
        for (let run = 0; run < RUNS; run++) {
            // Act

            const sentence = sentenceWithin({ maxLength: 20 }, 6);

            // Assert

            expect(sentence.length).toBeLessThanOrEqual(20);
        }
    });

    it('counts the period in the minimum length', () => {
        for (let run = 0; run < RUNS; run++) {
            // Act

            const sentence = sentenceWithin({ minLength: 30 }, 3);

            // Assert

            expect(sentence.length).toBeGreaterThanOrEqual(30);
        }
    });
});
