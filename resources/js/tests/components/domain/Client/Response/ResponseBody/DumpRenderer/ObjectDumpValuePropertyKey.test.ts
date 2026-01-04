import type { ObjectDumpProperty } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import ObjectDumpValuePropertyKey from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/ObjectDumpValuePropertyKey.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

const createProperty = (
    visibility: 'public' | 'protected' | 'private',
): ObjectDumpProperty => ({
    visibility,
    value: {
        type: DumpValueType.String,
        value: 'test',
    },
});

describe('ObjectDumpValuePropertyKey', () => {
    it('displays public property with + symbol and emerald color', async () => {
        const property = createProperty('public');

        renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'publicProp' },
        });

        await nextTick();

        expect(screen.getByText('+')).toBeInTheDocument();
        expect(screen.getByText('publicProp:')).toBeInTheDocument();

        const span = screen.getByText('+');

        expect(span?.className).toContain('text-emerald-600');
    });

    it('displays protected property with # symbol and amber color', async () => {
        const property = createProperty('protected');

        renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'protectedProp' },
        });

        await nextTick();

        expect(screen.getByText('#')).toBeInTheDocument();
        expect(screen.getByText('protectedProp:')).toBeInTheDocument();

        const span = screen.getByText('#');
        expect(span?.className).toContain('text-amber-600');
    });

    it('displays private property with - symbol and zinc color', async () => {
        const property = createProperty('private');

        renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'privateProp' },
        });

        await nextTick();

        expect(screen.getByText('-')).toBeInTheDocument();
        expect(screen.getByText('privateProp:')).toBeInTheDocument();

        const span = screen.getByText('-');
        expect(span?.className).toContain('text-zinc-500');
    });

    it('displays key name after visibility symbol', async () => {
        const property = createProperty('public');

        renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'myProperty' },
        });

        await nextTick();

        const container = screen.getByText('myProperty:').parentElement;
        expect(container?.textContent).toContain('+');
        expect(container?.textContent).toContain('myProperty:');
    });

    it('applies correct CSS classes for public visibility', async () => {
        const property = createProperty('public');

        const { container } = renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'prop' },
        });

        await nextTick();

        const visibilitySpan = container.querySelector('span > span');
        expect(visibilitySpan?.className).toContain('text-emerald-600');
        expect(visibilitySpan?.className).toContain('dark:text-emerald-500');
    });

    it('applies correct CSS classes for protected visibility', async () => {
        const property = createProperty('protected');

        const { container } = renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'prop' },
        });

        await nextTick();

        const visibilitySpan = container.querySelector('span > span');
        expect(visibilitySpan?.className).toContain('text-amber-600');
        expect(visibilitySpan?.className).toContain('dark:text-amber-500');
    });

    it('applies correct CSS classes for private visibility', async () => {
        const property = createProperty('private');

        const { container } = renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'prop' },
        });

        await nextTick();

        const visibilitySpan = container.querySelector('span > span');
        expect(visibilitySpan?.className).toContain('text-zinc-500');
        expect(visibilitySpan?.className).toContain('dark:text-zinc-400');
    });

    it('handles empty key name', async () => {
        const property = createProperty('public');

        renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: '' },
        });

        await nextTick();

        expect(screen.getByText('+')).toBeInTheDocument();
        expect(screen.getByText(':')).toBeInTheDocument();
    });

    it('handles special characters in key name', async () => {
        const property = createProperty('public');

        renderWithProviders(ObjectDumpValuePropertyKey, {
            props: { property, keyName: 'prop_with_underscore' },
        });

        await nextTick();

        expect(screen.getByText('prop_with_underscore:')).toBeInTheDocument();
    });
});
