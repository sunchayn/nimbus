import { AppSwitch } from '@/components/base/switch';
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
    return mount(AppSwitch, {
        ...options,
        global: {
            plugins: [createPinia()],
        },
    });
};

describe('AppSwitch', () => {
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
            expect(wrapper.find('button').exists()).toBe(true);
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('changes state when clicked', async () => {
            // Arrange

            const wrapper = createWrapper();
            const button = wrapper.find('button');

            // Act

            await button.trigger('click');

            // Assert

            expect(button.attributes('data-state')).toBe('checked');
        });
    });
});
