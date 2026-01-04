import type { StringDump } from '@/components/domain/Client/Response/ResponseBody/DumpRenderer';
import StringDumpRenderer from '@/components/domain/Client/Response/ResponseBody/DumpRenderer/StringDumpRenderer.vue';
import { DumpValueType } from '@/interfaces/generated/dump-value-types';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { describe, expect, it } from 'vitest';
import { nextTick } from 'vue';

describe('StringDumpRenderer', () => {
    it('renders string value in quotes', async () => {
        const dump: StringDump = {
            type: DumpValueType.String,
            value: 'test-string',
        };

        renderWithProviders(StringDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        const element = screen.getByText(/"test-string"/);
        expect(element).toBeInTheDocument();
    });

    it('displays string length in parentheses', async () => {
        const dump: StringDump = {
            type: DumpValueType.String,
            value: 'hello',
        };

        renderWithProviders(StringDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText(/\(5\)/)).toBeInTheDocument();
    });

    it('applies correct CSS classes', async () => {
        const dump: StringDump = {
            type: DumpValueType.String,
            value: 'test',
        };

        const { container } = renderWithProviders(StringDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        const span = container.querySelector('span');
        expect(span?.className).toContain('text-xs');
        expect(span?.className).toContain('font-mono');
    });

    it('handles empty strings', async () => {
        const dump: StringDump = {
            type: DumpValueType.String,
            value: '',
        };

        renderWithProviders(StringDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText(/""/)).toBeInTheDocument();
        expect(screen.getByText(/\(0\)/)).toBeInTheDocument();
    });

    it('handles long strings', async () => {
        const longString = 'a'.repeat(1000);
        const dump: StringDump = {
            type: DumpValueType.String,
            value: longString,
        };

        renderWithProviders(StringDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText(new RegExp(`"${longString}"`))).toBeInTheDocument();
        expect(screen.getByText(/\(1000\)/)).toBeInTheDocument();
    });

    it('handles unicode characters', async () => {
        const dump: StringDump = {
            type: DumpValueType.String,
            value: '测试 🎉',
        };

        renderWithProviders(StringDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText(/"测试 🎉"/)).toBeInTheDocument();
        expect(screen.getByText(/\(5\)/)).toBeInTheDocument();
    });

    it('handles strings with quotes', async () => {
        const dump: StringDump = {
            type: DumpValueType.String,
            value: 'string with "quotes"',
        };

        renderWithProviders(StringDumpRenderer, {
            props: { dump },
        });

        await nextTick();

        expect(screen.getByText(/"string with "quotes""/)).toBeInTheDocument();
    });
});
