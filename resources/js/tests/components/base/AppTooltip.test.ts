import {
    AppTooltip,
    AppTooltipContent,
    AppTooltipProvider,
    AppTooltipTrigger,
} from '@/components/base/tooltip';
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
            components: {
                AppTooltip,
                AppTooltipTrigger,
                AppTooltipContent,
                AppTooltipProvider,
            },
            template: `
            <AppTooltipProvider>
                <AppTooltip>
                    <AppTooltipTrigger>Hover me</AppTooltipTrigger>
                    <AppTooltipContent>Tooltip content</AppTooltipContent>
                </AppTooltip>
            </AppTooltipProvider>
        `,
            ...options,
        },
        {
            global: {
                plugins: [createPinia()],
            },
        },
    );
};

describe('AppTooltip', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders trigger correctly', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.text()).toContain('Hover me');
        });
    });
});
