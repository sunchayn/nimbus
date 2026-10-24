import type { ValueGenerator } from '@/interfaces/ui';
import { companyGenerators } from './company';
import { contactGenerators } from './contact';
import { dataGenerators } from './data';
import { financeGenerators } from './finance';
import { identifierGenerators } from './identifiers';
import { internetGenerators } from './internet';
import { locationGenerators } from './location';
import { numberGenerators } from './numbers';
import { personGenerators } from './person';
import { systemGenerators } from './system';
import { textGenerators } from './text';
import { timeGenerators } from './time';

export { generatorCategories } from './categories';
export { PROPERTY_NAME_PATTERNS } from './propertyNamePatterns';

/**
 * All available value generators organized by category.
 *
 * This array contains all generators from all categories,
 * providing a single source of truth for available generators.
 */
export const allValueGenerators: ValueGenerator[] = [
    ...textGenerators,
    ...numberGenerators,
    ...timeGenerators,
    ...personGenerators,
    ...contactGenerators,
    ...locationGenerators,
    ...financeGenerators,
    ...internetGenerators,
    ...companyGenerators,
    ...identifierGenerators,
    ...dataGenerators,
    ...systemGenerators,
];
