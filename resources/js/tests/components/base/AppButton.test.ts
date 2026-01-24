import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AppButton } from '@/components/base/button';

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
import type { MountingOptions } from '@vue/test-utils';

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount(AppButton, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global ?? {}),
        },
    });
};

describe('AppButton', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders default button correctly', () => {
            // Arrange

            const wrapper = createWrapper({
                slots: {
                    default: 'Click me',
                },
            });

            // Assert

            expect(wrapper.text()).toBe('Click me');
            expect(wrapper.classes()).toContain('bg-primary');
        });

        it('renders different variants', () => {
            // Arrange

            const wrapper = createWrapper({
                props: { variant: 'destructive' },
            });

            // Assert

            expect(wrapper.classes()).toContain('bg-destructive');
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('emits click event', async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act

            await wrapper.trigger('click');

            // Assert

            expect(wrapper.emitted('click')).toHaveLength(1);
        });
    });
});
