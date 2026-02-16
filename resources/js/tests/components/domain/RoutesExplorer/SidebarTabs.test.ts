import { AppSidebarProvider } from '@/components/base/sidebar';
import OpenTabs from '@/components/domain/RoutesExplorer/OpenTabs.vue';
import * as scrollComposable from '@/composables/ui/useTabVerticalScroll';
import { useTabsStore } from '@/stores';
import { type TestingPinia, createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import type { Mock } from 'vitest';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { type VNode, h, nextTick, ref } from 'vue';

vi.mock('vuedraggable', () => ({
    default: {
        props: ['modelValue'],
        setup(
            props: { modelValue: unknown[] },
            { slots }: { slots: Record<string, (args: { element: unknown }) => unknown> },
        ) {
            return () =>
                h(
                    'ul',
                    {},
                    props.modelValue.map((element: unknown) =>
                        slots.item({ element }),
                    ) as unknown as VNode[],
                );
        },
    },
}));

const TestWrapper = {
    components: { OpenTabs: OpenTabs, AppSidebarProvider },
    template: `
        <AppSidebarProvider>
            <OpenTabs v-model:is-open="isOpen" />
        </AppSidebarProvider>
    `,
    setup() {
        const isOpen = ref(false);

        return { isOpen };
    },
};

describe('SidebarTabsFunctionalTest', () => {
    let pinia: TestingPinia;
    let mockScrollTabIntoView: Mock;

    beforeEach(() => {
        mockScrollTabIntoView = vi.fn();
        vi.spyOn(scrollComposable, 'useTabVerticalScroll').mockReturnValue({
            scrollContainer: ref(null),
            showTopMask: ref(false),
            showBottomMask: ref(false),
            updateScrollMasks: vi.fn(),
            scrollTabIntoView: mockScrollTabIntoView,
        });

        pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: {
                tabs: {
                    tabs: [
                        {
                            id: 'tab1',
                            title: 'Route 1',
                            method: 'GET',
                            request: {},
                            response: null,
                        },
                        {
                            id: 'tab2',
                            title: 'Route 2',
                            method: 'POST',
                            request: {},
                            response: null,
                        },
                    ],
                    activeTabId: 'tab1',
                },
            },
        });
    });

    it('renders the "Open Tabs" header', () => {
        // Arrange

        const wrapper = mount(TestWrapper, {
            global: {
                plugins: [pinia],
            },
        });

        // Assert

        expect(wrapper.text()).toContain('Open Tabs');
    });

    it('renders all open tabs with HTTP verbs', async () => {
        // Arrange

        const wrapper = mount(TestWrapper, {
            global: {
                plugins: [pinia],
            },
        });

        // Act - Open the section
        const trigger = wrapper
            .findAll('button')
            .find(b => b.text().includes('Open Tabs'));
        await trigger?.trigger('click');
        await nextTick();

        // Assert

        expect(wrapper.text()).toContain('Route 1');
        expect(wrapper.text()).toContain('Route 2');
        expect(wrapper.findComponent({ name: 'HttpVerbLabel' }).exists()).toBe(true);
    });

    it('highlights the active tab', async () => {
        // Arrange

        const wrapper = mount(TestWrapper, {
            global: {
                plugins: [pinia],
            },
        });

        // Act - Open the section
        const trigger = wrapper
            .findAll('button')
            .find(b => b.text().includes('Open Tabs'));
        await trigger?.trigger('click');
        await nextTick();

        // Assert

        const activeItem = wrapper.find('[data-active="true"]');
        expect(activeItem.exists()).toBe(true);
        expect(activeItem.text()).toContain('Route 1');
    });

    it('calls setActiveTab when a tab is clicked', async () => {
        // Arrange

        const wrapper = mount(TestWrapper, {
            global: {
                plugins: [pinia],
            },
        });
        const tabsStore = useTabsStore();

        // Act - Open the section
        const trigger = wrapper
            .findAll('button')
            .find(b => b.text().includes('Open Tabs'));
        await trigger?.trigger('click');
        await nextTick();

        const tabButtons = wrapper.findAllComponents({ name: 'AppSidebarMenuButton' });
        // Index 0 is the collapsible trigger. Index 1 is Route 1. Index 2 is Route 2.
        await tabButtons[1].trigger('click');

        // Assert

        expect(tabsStore.setActiveTab).toHaveBeenCalledWith('tab1');
    });

    it('calls closeTab when close button is clicked', async () => {
        // Arrange

        const wrapper = mount(TestWrapper, {
            global: {
                plugins: [pinia],
            },
        });
        const tabsStore = useTabsStore();

        // Act - Open the section
        const trigger = wrapper
            .findAll('button')
            .find(b => b.text().includes('Open Tabs'));
        await trigger?.trigger('click');
        await nextTick();

        // Act - Click close button of the first tab
        const closeButton = wrapper.find('button[class*="group-hover:opacity-100"]');
        await closeButton.trigger('click');

        // Assert

        expect(tabsStore.closeTab).toHaveBeenCalledWith('tab1');
    });

    it('scrolls the active tab into view when activeTabId changes', async () => {
        // Arrange

        const wrapper = mount(TestWrapper, {
            global: {
                plugins: [pinia],
            },
        });
        const tabsStore = useTabsStore();

        // Act - Open the section
        const trigger = wrapper
            .findAll('button')
            .find(b => b.text().includes('Open Tabs'));
        await trigger?.trigger('click');
        await nextTick();

        // Act - Change active tab ID in store
        tabsStore.activeTabId = 'tab2';
        await nextTick();
        await nextTick(); // Second nextTick to ensure watcher and internal nextTick complete

        // Assert

        expect(mockScrollTabIntoView).toHaveBeenCalled();
    });
});
