import {
    AppPopover,
    AppPopoverContent,
    AppPopoverTrigger,
} from '@/components/base/popover';
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
    return mount(
        {
            components: { AppPopover, AppPopoverTrigger, AppPopoverContent },
            template: `
            <AppPopover>
                <AppPopoverTrigger>Open</AppPopoverTrigger>
                <AppPopoverContent>Popover Content</AppPopoverContent>
            </AppPopover>
        `,
            ...options,
        },
        {
            global: {
                plugins: [createPinia()],
            },
            attachTo: document.body,
        },
    );
};

describe('AppPopover', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        document.body.innerHTML = '';
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders trigger correctly', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.find('button').text()).toBe('Open');
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('opens content when trigger is clicked', async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act

            await wrapper.find('button').trigger('click');

            // Assert

            expect(document.body.innerHTML).toContain('Popover Content');
            wrapper.unmount();
        });
    });
});
