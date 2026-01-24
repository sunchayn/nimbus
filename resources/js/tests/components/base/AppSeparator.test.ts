import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AppSeparator } from '@/components/base/separator';

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount(AppSeparator, {
        ...options,
        global: {
            plugins: [createPinia()],
        },
    });
};

describe('AppSeparator', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders horizontal orientation by default', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.attributes('data-orientation')).toBe('horizontal');
            expect(wrapper.classes()).toContain('h-px');
        });

        it('renders with label', () => {
            // Arrange

            const wrapper = createWrapper({
                props: { label: 'OR' },
            });

            // Assert

            expect(wrapper.text()).toBe('OR');
        });
    });
});
