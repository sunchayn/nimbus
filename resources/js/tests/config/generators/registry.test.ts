import { getAllGenerators, getGeneratorCategories } from '@/config/generators';
import { describe, expect, it } from 'vitest';

describe('generator registry', () => {
    it('gives every generator its own icon', () => {
        // Arrange

        const icons = getAllGenerators.map(generator => generator.icon);

        // Assert

        expect(new Set(icons).size).toBe(icons.length);
    });

    it('attaches every generator to a listed category', () => {
        // Arrange

        const categoryIds = getGeneratorCategories.map(category => category.id);

        // Assert

        for (const generator of getAllGenerators) {
            expect(categoryIds).toContain(generator.category.id);
        }
    });

    it('lists only categories that have generators', () => {
        // Assert

        for (const category of getGeneratorCategories) {
            expect(
                getAllGenerators.some(generator => generator.category.id === category.id),
            ).toBe(true);
        }
    });
});
