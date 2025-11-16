import { AppButton } from '@/components/base/button';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { describe, expect, it } from 'vitest';

describe('AppButton', () => {
    it('applies default sizing and variant styles', () => {
        renderWithProviders(AppButton, { slots: { default: 'Submit' } });

        const button = screen.getByRole('button', { name: 'Submit' });

        expect(button.className).toContain('bg-zinc-900');
        expect(button.className).toContain('h-9');
    });

    it('applies requested variant styles', () => {
        renderWithProviders(AppButton, {
            props: { variant: 'destructive' },
            slots: { default: 'Delete' },
        });

        const button = screen.getByRole('button', { name: 'Delete' });

        expect(button.className).toContain('bg-red-500');
        expect(button.className).toContain('dark:bg-red-900');
    });

    it('merges custom classes with size styles', () => {
        renderWithProviders(AppButton, {
            props: { size: 'lg', class: 'tracking-wide' },
            slots: { default: 'Large' },
        });

        const button = screen.getByRole('button', { name: 'Large' });

        expect(button.className).toContain('h-10');
        expect(button.className).toContain('tracking-wide');
    });
});
