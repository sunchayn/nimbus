import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import AppInput from '@/components/base/input/AppInput.vue';
import { ValueGeneratorCommandOpenMethod } from '@/interfaces/ui';

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

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount(AppInput, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('AppInput', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        openCommand.mockClear();
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('syncs model value updates', async () => {
            // Arrange

            const wrapper = createWrapper({
                props: {
                    modelValue: "initial",
                    "onUpdate:modelValue": async (event: string | number) => {
                        await (wrapper as VueWrapper).setProps({
                            modelValue: event,
                        });
                    },
                },
            });
            const input = wrapper.find("input");

            // Act

            await input.setValue("updated");

            // Assert

            // @ts-expect-error cannot figure out the argument.
            expect(wrapper.props("modelValue")).toBe("updated");
        });

        it('triggers generator command on double shift', async () => {
            // Arrange

            vi.useFakeTimers();
            const wrapper = createWrapper();
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
