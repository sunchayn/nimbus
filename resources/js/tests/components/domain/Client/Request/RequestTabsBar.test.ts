import RequestTabsBar from '@/components/domain/Client/Request/RequestTabsBar.vue';
import { useTabsStore } from '@/stores';
import { type TestingPinia, createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { type VNode, h, nextTick } from 'vue';

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

describe('RequestTabsBar', () => {
    let pinia: TestingPinia;

    beforeEach(() => {
        Element.prototype.scrollIntoView = vi.fn();

        pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: {
                tabs: {
                    tabs: [
                        { id: 'tab1', title: 'Route 1', method: 'GET' },
                        { id: 'tab2', title: 'Route 2', method: 'POST' },
                    ],
                    activeTabId: 'tab1',
                },
            },
        });
    });

    const createWrapper = () => mount(RequestTabsBar, { global: { plugins: [pinia] } });

    it('renders every open tab with its verb', () => {
        const wrapper = createWrapper();

        expect(wrapper.text()).toContain('Route 1');
        expect(wrapper.text()).toContain('Route 2');
        expect(wrapper.findComponent({ name: 'HttpVerbLabel' }).exists()).toBe(true);
    });

    it('marks the active tab', () => {
        const wrapper = createWrapper();
        const active = wrapper.find('[data-active="true"]');

        expect(active.text()).toContain('Route 1');
        expect(active.find('[aria-current="page"]').exists()).toBe(true);
    });

    it('activates a tab on click', async () => {
        const wrapper = createWrapper();
        const tabsStore = useTabsStore();

        await wrapper.findAll('[data-testid="sidebar-tab"]')[1].trigger('click');

        expect(tabsStore.setActiveTab).toHaveBeenCalledWith('tab2');
    });

    it('closes a tab without activating it', async () => {
        const wrapper = createWrapper();
        const tabsStore = useTabsStore();

        vi.useFakeTimers();

        await wrapper.find('[data-testid="sidebar-tab-close"]').trigger('click');
        await vi.advanceTimersByTimeAsync(150);

        vi.useRealTimers();

        expect(tabsStore.closeTab).toHaveBeenCalledWith('tab1');
        expect(tabsStore.setActiveTab).not.toHaveBeenCalled();
    });

    it('scrolls the active tab into view when it changes', async () => {
        createWrapper();
        const tabsStore = useTabsStore();

        tabsStore.activeTabId = 'tab2';
        await nextTick();
        await nextTick();

        expect(Element.prototype.scrollIntoView).toHaveBeenCalled();
    });
});
