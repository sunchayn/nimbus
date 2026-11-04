import { defineCategory } from '@/config/generators/utils/defineCategory';
import { describe, expect, it } from 'vitest';

/*
 * Fixtures.
 */

const createCategory = () =>
    defineCategory({
        id: 'sample',
        name: 'Sample',
        icon: 'i-hugeicons-user',
        generators: [
            { id: 'plain', name: 'Plain Value', generate: () => 'plain' },
            {
                id: 'custom',
                name: 'Custom Value',
                description: 'A custom description',
                icon: 'i-hugeicons-key-01',
                generate: () => 'custom',
            },
        ],
    });

describe('defineCategory', () => {
    it('exposes the category with its id, name, and icon', () => {
        // Act

        const { category } = createCategory();

        // Assert

        expect(category).toEqual({
            id: 'sample',
            name: 'Sample',
            icon: 'i-hugeicons-user',
        });
    });

    it('attaches the category to every generator', () => {
        // Act

        const { category, generators } = createCategory();

        // Assert

        expect(generators.map(generator => generator.category)).toEqual([
            category,
            category,
        ]);
    });

    it('fills the description and the icon when a generator has none', () => {
        // Act

        const [plain] = createCategory().generators;

        // Assert

        expect(plain.description).toBe('Generate a random plain value');
        expect(plain.icon).toBe('i-hugeicons-user');
    });

    it('keeps the description and the icon a generator sets', () => {
        // Act

        const [, custom] = createCategory().generators;

        // Assert

        expect(custom.description).toBe('A custom description');
        expect(custom.icon).toBe('i-hugeicons-key-01');
    });
});
