import type { GeneratorCategory, ValueGenerator } from '@/interfaces/ui';
import { company } from './categories/company';
import { contact } from './categories/contact';
import { data } from './categories/data';
import { finance } from './categories/finance';
import { identifiers } from './categories/identifiers';
import { internet } from './categories/internet';
import { location } from './categories/location';
import { numbers } from './categories/numbers';
import { person } from './categories/person';
import { system } from './categories/system';
import { text } from './categories/text';
import { time } from './categories/time';

/**
 * Categories in display order. Each one owns its generators.
 */
const categoryGroups = [
    text,
    numbers,
    time,
    person,
    contact,
    location,
    finance,
    internet,
    company,
    identifiers,
    data,
    system,
];

export const getGeneratorCategories: readonly GeneratorCategory[] = Object.freeze(
    categoryGroups.map(group => group.category),
);

export const getAllGenerators: readonly ValueGenerator[] = Object.freeze(
    categoryGroups.flatMap(group => group.generators),
);

export const getGeneratorById: ReadonlyMap<string, ValueGenerator> = new Map(
    getAllGenerators.map(generator => [generator.id, generator]),
);
