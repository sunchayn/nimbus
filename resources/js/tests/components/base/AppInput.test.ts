import AppInput from '@/components/base/input/AppInput.vue';
import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { renderWithProviders, screen } from '@/tests/_utils/test-utils';
import { fireEvent } from '@testing-library/vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const openCommand = vi.fn();

vi.mock('@/stores', async importOriginal => {
    const actual = await importOriginal<object>();

    return {
        ...actual,
        useValueGeneratorStore: () => ({
            openCommand,
        }),
    };
});

describe('AppInput', () => {
    beforeEach(() => {
        openCommand.mockClear();
    });

    it('syncs model value updates', async () => {
        const { user } = renderWithProviders(AppInput, {
            props: { modelValue: 'initial' },
        });

        const input = screen.getByRole('textbox');

        await user.clear(input);
        await user.type(input, 'updated');

        expect((input as HTMLInputElement).value).toBe('updated');
    });

    it('triggers generator command on double shift', async () => {
        renderWithProviders(AppInput);

        const input = screen.getByRole('textbox');

        const nowSpy = vi.spyOn(Date, 'now');
        nowSpy.mockReturnValueOnce(1000).mockReturnValueOnce(1200);

        await fireEvent.keyDown(input, { key: 'Shift' });

        await fireEvent.keyDown(input, { key: 'Shift' });

        expect(openCommand).toHaveBeenCalledWith(
            input,
            ValueGeneratorCommandOpenMethod.SHIFT_SHIFT,
        );
        nowSpy.mockRestore();
    });
});
