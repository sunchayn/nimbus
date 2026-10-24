import GeneratorInput from '@/components/common/GeneratorInput.vue';
import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

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

describe('GeneratorInput', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        openCommand.mockClear();
    });

    describe('Behavior', () => {
        it('syncs model value updates', async () => {
            // Arrange

            const onUpdate = vi.fn();
            const wrapper = mount(GeneratorInput, {
                props: { modelValue: 'initial', 'onUpdate:modelValue': onUpdate },
            });

            // Act

            await wrapper.find('input').setValue('updated');

            // Assert

            expect(onUpdate).toHaveBeenCalledWith('updated');
        });

        it('triggers generator command on double shift', async () => {
            // Arrange

            vi.useFakeTimers();
            const wrapper = mount(GeneratorInput);
            const input = wrapper.find('input');

            // Act

            // First press
            await input.trigger('keydown', { key: 'Shift' });

            // Advance by 200ms (threshold is 500ms)
            vi.advanceTimersByTime(200);

            // Second press
            await input.trigger('keydown', { key: 'Shift' });
            await nextTick();

            // Assert

            expect(openCommand).toHaveBeenCalledWith(
                input.element,
                ValueGeneratorCommandOpenMethod.SHIFT_SHIFT,
            );

            vi.useRealTimers();
        });
    });
});
