import type { ConstDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import ConstDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/ConstDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

describe('ConstDumpRenderer', () => {
    it('renders true as string', async () => {
        const dump: ConstDump = {
            type: DumpValueType.Constant,
            value: true,
        };

        renderWithProviders(ConstDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('true')).toBeInTheDocument();
    });

    it('renders false as string', async () => {
        const dump: ConstDump = {
            type: DumpValueType.Constant,
            value: false,
        };

        renderWithProviders(ConstDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('false')).toBeInTheDocument();
    });

    it('renders null as string', async () => {
        const dump: ConstDump = {
            type: DumpValueType.Constant,
            value: null,
        };

        renderWithProviders(ConstDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText('null')).toBeInTheDocument();
    });

    it('applies italic styling', async () => {
        const dump: ConstDump = {
            type: DumpValueType.Constant,
            value: true,
        };

        const { container } = renderWithProviders(ConstDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        const span = container.querySelector('span');
        expect(span?.className).toContain('italic');
    });

    it('applies correct CSS classes', async () => {
        const dump: ConstDump = {
            type: DumpValueType.Constant,
            value: true,
        };

        const { container } = renderWithProviders(ConstDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        const span = container.querySelector('span');
        expect(span?.className).toContain('text-xs');
        expect(span?.className).toContain('font-mono');
    });

    it('handles keyName prop when provided', async () => {
        const dump: ConstDump = {
            type: DumpValueType.Constant,
            value: true,
        };

        renderWithProviders(ConstDumpRenderer, {
            props: { dump, keyName: 'myKey' },
        });

        await nextTick();

        expect(screen.getByText('true')).toBeInTheDocument();
    });
});
