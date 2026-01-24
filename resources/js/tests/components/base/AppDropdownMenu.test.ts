import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AppDropdownMenu, AppDropdownMenuContent, AppDropdownMenuItem, AppDropdownMenuTrigger } from '@/components/base/dropdown-menu';
import { RenderWithProvidersOptions } from "@/tests/_utils/test-utils";

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount({
        components: { AppDropdownMenu, AppDropdownMenuTrigger, AppDropdownMenuContent, AppDropdownMenuItem },
        template: `
            <AppDropdownMenu>
                <AppDropdownMenuTrigger>Actions</AppDropdownMenuTrigger>
                <AppDropdownMenuContent>
                    <AppDropdownMenuItem>Edit</AppDropdownMenuItem>
                </AppDropdownMenuContent>
            </AppDropdownMenu>
        `,
        ...options,
    }, {
        global: {
            plugins: [createPinia()],
        },
        attachTo: document.body,
    });
};

describe('AppDropdownMenu', () => {
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

            expect(wrapper.find('button').text()).toBe('Actions');
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('opens menu items when trigger is clicked', async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act

            await wrapper.find('button').trigger('click');

            // Assert

            expect(document.body.innerHTML).toContain('Edit');
            wrapper.unmount();
        });
    });
});
