import type { VueWrapper } from '@vue/test-utils';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { AppCard, AppCardContent, AppCardDescription, AppCardFooter, AppCardHeader, AppCardTitle } from '@/components/base/card';
import { RenderWithProvidersOptions } from "@/tests/_utils/test-utils";

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount({
        components: { AppCard, AppCardHeader, AppCardTitle, AppCardDescription, AppCardContent, AppCardFooter },
        template: `
            <AppCard v-bind="cardProps">
                <AppCardHeader>
                    <AppCardTitle>Title</AppCardTitle>
                    <AppCardDescription>Description</AppCardDescription>
                </AppCardHeader>
                <AppCardContent>Content</AppCardContent>
                <AppCardFooter>Footer</AppCardFooter>
            </AppCard>
        `,
        data() {
            return {
                // @ts-expect-error .props not found in object.
                cardProps: options.props || {},
            };
        },
        ...options,
    }, {
        global: {
            plugins: [createPinia()],
        },
    });
};

describe('AppCard', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders all card sub-components correctly', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            expect(wrapper.findComponent(AppCardTitle).text()).toBe('Title');
            expect(wrapper.findComponent(AppCardDescription).text()).toBe('Description');
            expect(wrapper.findComponent(AppCardContent).text()).toBe('Content');
            expect(wrapper.findComponent(AppCardFooter).text()).toBe('Footer');
        });

        it('applies custom classes to AppCard', () => {
            // Arrange

            const wrapper = createWrapper({
                props: { class: 'custom-card-class' },
            });

            // Assert

            expect(wrapper.classes()).toContain('custom-card-class');
        });
    });
});
