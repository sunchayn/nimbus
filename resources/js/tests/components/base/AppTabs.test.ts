import type { VueWrapper } from '@vue/test-utils';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';
import { AppTabs, AppTabsContent, AppTabsList, AppTabsTrigger } from '@/components/base/tabs';
import { RenderWithProvidersOptions } from "@/tests/_utils/test-utils";

/*
 * Fixtures.
 */

/**
 * Factory function to create a mounted wrapper with sensible defaults.
 */
const createWrapper = (options= {}): VueWrapper => {
    return mount({
        components: { AppTabs, AppTabsList, AppTabsTrigger, AppTabsContent },
        setup() {
            const activeTab = ref('tab1');
            return { activeTab };
        },
        template: `
            <AppTabs v-model="activeTab" v-bind="tabsProps">
                <AppTabsList>
                    <AppTabsTrigger value="tab1">Tab 1</AppTabsTrigger>
                    <AppTabsTrigger value="tab2">Tab 2</AppTabsTrigger>
                </AppTabsList>
                <AppTabsContent value="tab1" data-testid="c1">Content 1</AppTabsContent>
                <AppTabsContent value="tab2" data-testid="c2">Content 2</AppTabsContent>
            </AppTabs>
        `,
        data() {
            return {
                // @ts-expect-error .props not found in object.
                tabsProps: options.props || {},
            };
        },
        ...options,
    }, {
        global: {
            plugins: [createPinia()],
        },
    });
};

describe('AppTabs', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    /*
     * Rendering tests.
     */

    describe('Rendering', () => {
        it('renders list items correctly', () => {
            // Arrange

            const wrapper = createWrapper();

            // Assert

            const triggers = wrapper.findAll('[role="tab"]');
            expect(triggers).toHaveLength(2);
            expect(triggers[0].text()).toBe('Tab 1');
        });
    });

    /*
     * State Transition tests.
     */

    describe('Behavior', () => {
        it('switches content when model-value prop is updated', async () => {
            // Arrange

            const wrapper = createWrapper();

            // Act

            (wrapper.vm as any).activeTab = 'tab2';
            await flushPromises();
            await nextTick();
            await nextTick();

            // Assert

            expect(wrapper.find('[data-testid="c2"]').exists()).toBe(true);
            expect(wrapper.find('[data-testid="c2"]').attributes('data-state')).toBe('active');
        });
    });
});
