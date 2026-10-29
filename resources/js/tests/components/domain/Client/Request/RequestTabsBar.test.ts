import RequestTabsBar from '@/components/domain/Client/Request/RequestTabsBar.vue';
import { useTabsStore } from '@/stores';
import { type TestingPinia, createTestingPinia } from '@pinia/testing';
import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { type VNode, h, nextTick } from 'vue';

vi.mock('vuedraggable', () => ({
    default: {
        props: ['modelValue'],
        setup(
            props: { modelValue: unknown[] },
            {
                slots,
            }: {
                slots: Record<
                    string,
                    (args: { element: unknown; index: number }) => unknown
                >;
            },
        ) {
            return () =>
                h(
                    'ul',
                    {},
                    props.modelValue.map((element: unknown, index: number) =>
                        slots.item({ element, index }),
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

    afterEach(() => {
        document.body.innerHTML = '';
    });

    const createWrapper = () =>
        mount(RequestTabsBar, { global: { plugins: [pinia] }, attachTo: document.body });

    const openMenu = async (wrapper: ReturnType<typeof createWrapper>, index: number) => {
        await wrapper
            .findAll('[data-testid="sidebar-tab"]')
            [index].trigger('contextmenu');
        await nextTick();
        await nextTick();
    };

    const menuItem = (label: string) =>
        [...document.body.querySelectorAll<HTMLElement>('[role="menuitem"]')].find(
            item => item.textContent?.trim() === label,
        );

    const menuLabels = () =>
        [...document.body.querySelectorAll('[role="menuitem"]')].map(item =>
            item.textContent?.trim(),
        );

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

    describe('Context menu', () => {
        it('shows the right side options for the first tab', async () => {
            const wrapper = createWrapper();

            await openMenu(wrapper, 0);

            expect(menuLabels()).toEqual([
                'Close tab',
                'Close other tabs',
                'Close tabs to the right',
                'Rename tab',
                'Pin tab',
                'Share',
            ]);
        });

        it('shows the left side options for the last tab', async () => {
            const wrapper = createWrapper();

            await openMenu(wrapper, 1);

            expect(menuLabels()).toContain('Close tabs to the left');
            expect(menuLabels()).not.toContain('Close tabs to the right');
        });

        it('hides the directional options when only pinned tabs are on that side', async () => {
            pinia = createTestingPinia({
                createSpy: vi.fn,
                initialState: {
                    tabs: {
                        tabs: [
                            { id: 'tab1', title: 'Route 1', method: 'GET', pinned: true },
                            { id: 'tab2', title: 'Route 2', method: 'POST' },
                        ],
                        activeTabId: 'tab1',
                    },
                },
            });
            const wrapper = createWrapper();

            await openMenu(wrapper, 1);

            expect(menuLabels()).not.toContain('Close other tabs');
            expect(menuLabels()).not.toContain('Close tabs to the left');
            expect(menuLabels()).toContain('Pin tab');
        });

        it('hides the bulk close options for a single tab', async () => {
            pinia = createTestingPinia({
                createSpy: vi.fn,
                initialState: {
                    tabs: {
                        tabs: [{ id: 'tab1', title: 'Route 1', method: 'GET' }],
                        activeTabId: 'tab1',
                    },
                },
            });
            const wrapper = createWrapper();

            await openMenu(wrapper, 0);

            expect(menuLabels()).toEqual(['Close tab', 'Rename tab', 'Pin tab', 'Share']);
        });

        it('runs the bulk close actions on the tab store', async () => {
            const tabsStore = useTabsStore();
            const wrapper = createWrapper();

            await openMenu(wrapper, 0);
            menuItem('Close other tabs')?.click();

            expect(tabsStore.closeOtherTabs).toHaveBeenCalledWith('tab1');
        });

        it('pins a tab', async () => {
            const tabsStore = useTabsStore();
            const wrapper = createWrapper();

            await openMenu(wrapper, 0);
            menuItem('Pin tab')?.click();

            expect(tabsStore.togglePinTab).toHaveBeenCalledWith('tab1');
        });

        it('activates the tab before opening the share dialog', async () => {
            const tabsStore = useTabsStore();
            const wrapper = createWrapper();

            await openMenu(wrapper, 1);
            menuItem('Share')?.click();

            expect(tabsStore.setActiveTab).toHaveBeenCalledWith('tab2');
        });
    });

    describe('Rename', () => {
        const openRename = async (wrapper: ReturnType<typeof createWrapper>) => {
            await wrapper.findAll('[data-testid="sidebar-tab"]')[0].trigger('dblclick');
            await nextTick();
            await nextTick();
        };

        it('opens a labelled input on double click', async () => {
            const wrapper = createWrapper();

            await openRename(wrapper);

            const input = document.body.querySelector<HTMLInputElement>(
                '[data-testid="tab-rename-input"]',
            );

            expect(input?.value).toBe('Route 1');
            expect(document.body.textContent).toContain('Label');
            expect(
                document.body.querySelector('[data-testid="tab-rename-save"]'),
            ).toBeNull();
        });

        it('saves the name when enter is pressed', async () => {
            const tabsStore = useTabsStore();
            const wrapper = createWrapper();

            await openRename(wrapper);

            const input = document.body.querySelector<HTMLInputElement>(
                '[data-testid="tab-rename-input"]',
            ) as HTMLInputElement;

            input.value = 'Renamed';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            await nextTick();

            document.body
                .querySelector<HTMLFormElement>('[data-testid="tab-rename-form"]')
                ?.dispatchEvent(new Event('submit', { cancelable: true }));
            await nextTick();

            expect(tabsStore.renameTab).toHaveBeenCalledWith('tab1', 'Renamed');
        });
    });

    describe('Tab display', () => {
        it('shows the custom title and the pin icon', () => {
            pinia = createTestingPinia({
                createSpy: vi.fn,
                initialState: {
                    tabs: {
                        tabs: [
                            {
                                id: 'tab1',
                                title: 'Route 1',
                                customTitle: 'Mine',
                                method: 'GET',
                                pinned: true,
                            },
                            { id: 'tab2', title: 'Route 2', method: 'POST' },
                        ],
                        activeTabId: 'tab1',
                    },
                },
            });
            const wrapper = createWrapper();
            const tabs = wrapper.findAll('[data-testid="sidebar-tab"]');

            expect(tabs[0].text()).toContain('Mine');
            expect(tabs[0].find('[data-testid="sidebar-tab-pinned"]').exists()).toBe(
                true,
            );
            expect(tabs[1].find('[data-testid="sidebar-tab-pinned"]').exists()).toBe(
                false,
            );
        });
    });
});
