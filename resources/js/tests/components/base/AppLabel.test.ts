import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AppLabel } from '@/components/base/label';

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
    return mount(AppLabel, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global || {}),
        },
    });
};

describe('AppLabel', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders slot content correctly', () => {
            // Arrange

            const wrapper = createWrapper({
                slots: { default: 'Name' },
            });

            // Assert

            expect(wrapper.text()).toBe('Name');
        });

        it('applies custom class', () => {
            // Arrange

            const wrapper = createWrapper({
                props: { class: 'custom-label' },
            });

            // Assert

            expect(wrapper.classes()).toContain('custom-label');
        });
    });
});
