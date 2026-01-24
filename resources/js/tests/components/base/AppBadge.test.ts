import { AppBadge } from '@/components/base/badge';
import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options = {}): VueWrapper => {
    return mount(AppBadge, {
        ...options,
        global: {
            plugins: [createPinia()],
            // @ts-expect-error .global not found in object.
            ...(options.global ?? {}),
        },
    });
};

describe('AppBadge', () => {
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
                slots: { default: 'Test Badge' },
            });

            // Assert

            expect(wrapper.text()).toBe('Test Badge');
        });

        it('applies default variant classes', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.classes()).toContain('bg-primary');
        });

        it('applies destructive variant classes', () => {
            // Arrange

            const wrapper = createWrapper({
                props: { variant: 'destructive' },
            });

            // Assert

            expect(wrapper.classes()).toContain('bg-destructive');
        });

        it('applies custom class from props', () => {
            // Arrange

            const wrapper = createWrapper({
                props: { class: 'custom-class' },
            });

            // Assert

            expect(wrapper.classes()).toContain('custom-class');
        });
    });
});
