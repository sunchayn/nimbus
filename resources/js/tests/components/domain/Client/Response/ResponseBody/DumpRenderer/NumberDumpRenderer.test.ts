import type { NumberDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import NumberDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/NumberDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

describe('NumberDumpRenderer', () => {
    it('renders number value correctly', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: 42,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('42')).toBeInTheDocument();
    });

    it('handles positive numbers', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: 123,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('123')).toBeInTheDocument();
    });

    it('handles negative numbers', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: -42,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('-42')).toBeInTheDocument();
    });

    it('handles zero', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: 0,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('0')).toBeInTheDocument();
    });

    it('handles decimal numbers', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: 3.14159,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('3.14159')).toBeInTheDocument();
    });

    it('handles very large numbers', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: 1e20,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('100000000000000000000')).toBeInTheDocument();
    });

    it('handles very small numbers', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: 1e-10,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('1e-10')).toBeInTheDocument();
    });

    it('applies correct CSS classes', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: 42,
        };

        const { container } = renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        const span = container.querySelector('span');
        expect(span?.className).toContain('text-xs');
        expect(span?.className).toContain('font-mono');
    });

    it('handles Infinity', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: Infinity,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('Infinity')).toBeInTheDocument();
    });

    it('handles -Infinity', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: -Infinity,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('-Infinity')).toBeInTheDocument();
    });

    it('handles NaN', async () => {
        const dump: NumberDump = {
            type: DumpValueType.Number,
            value: NaN,
        };

        renderWithProviders(NumberDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('NaN')).toBeInTheDocument();
    });
});
