import type { GeneratorCategory, ValueGenerator } from '@/interfaces/ui';

/**
 * A generator as written in a category file.
 *
 * The category, the default description and the default icon are filled in
 * by `defineCategory`.
 */
export type GeneratorDefinition = Omit<ValueGenerator, 'category' | 'description'> & {
    description?: string;
};

interface CategoryDefinition {
    id: string;
    name: string;
    icon: string;
    generators: GeneratorDefinition[];
}

export interface GeneratorCategoryGroup {
    category: GeneratorCategory;
    generators: ValueGenerator[];
}

/**
 * Defines a category once and attaches it to each of its generators.
 */
export function defineCategory(definition: CategoryDefinition): GeneratorCategoryGroup {
    const category: GeneratorCategory = {
        id: definition.id,
        name: definition.name,
        icon: definition.icon,
    };

    const generators = definition.generators.map(
        (generator): ValueGenerator => ({
            ...generator,
            description:
                generator.description ??
                `Generate a random ${generator.name.toLowerCase()}`,
            icon: generator.icon ?? category.icon,
            category,
        }),
    );

    return { category, generators };
}
