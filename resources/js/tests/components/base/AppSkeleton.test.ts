import { AppSkeleton } from '@/components/base/skeleton';
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
const createWrapper = (options = {}): VueWrapper => {
    return mount(AppSkeleton, {
        ...options,
        global: {
            plugins: [createPinia()],
        },
    });
};

describe('AppSkeleton', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders correctly', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.exists()).toBe(true);
            expect(wrapper.classes()).toContain('animate-pulse');
        });
    });
});
