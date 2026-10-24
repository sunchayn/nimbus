import type { ValueGenerator } from '@/interfaces/ui';
import { filterGenerators } from '@/utils/value-generator/filterGenerators';
import { describe, expect, it } from 'vitest';

const generators: ValueGenerator[] = [
    {
        id: 'email',
        name: 'Email Generator',
        description: 'Generates random email addresses',
        category: { id: 'string', name: 'String' },
        generate: () => 'test@example.com',
    },
    {
        id: 'uuid',
        name: 'UUID Generator',
        description: 'Generates UUID v4',
        category: { id: 'string', name: 'String' },
        generate: () => '123e4567-e89b-12d3-a456-426614174000',
    },
    {
        id: 'number',
        name: 'Number Generator',
        description: 'Generates random numbers',
        category: { id: 'number', name: 'Number' },
        generate: () => 42,
    },
];

const ids = (list: ValueGenerator[]): string[] => list.map(generator => generator.id);

describe('filterGenerators', () => {
    it('returns all generators without filters', () => {
        expect(ids(filterGenerators(generators, '', null))).toEqual([
            'email',
            'uuid',
            'number',
        ]);
    });

    it('matches the query on name case-insensitively', () => {
        expect(ids(filterGenerators(generators, 'uUiD', null))).toEqual(['uuid']);
    });

    it('matches the query on description', () => {
        expect(ids(filterGenerators(generators, 'random', null))).toEqual([
            'email',
            'number',
        ]);
    });

    it('filters by category', () => {
        expect(ids(filterGenerators(generators, '', 'string'))).toEqual([
            'email',
            'uuid',
        ]);
    });

    it('combines query and category', () => {
        expect(ids(filterGenerators(generators, 'random', 'string'))).toEqual(['email']);
    });

    it('returns an empty list when nothing matches', () => {
        expect(filterGenerators(generators, 'zzz', null)).toEqual([]);
    });
});
