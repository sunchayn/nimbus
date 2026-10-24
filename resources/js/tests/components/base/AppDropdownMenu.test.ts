import { AppDropdownMenu } from '@/components/base/dropdown-menu';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/*
 * Fixtures.
 */

const onSelect = vi.fn();

const mountMenu = (props = {}) =>
    mount(
        {
            components: { AppDropdownMenu },
            props: ['open'],
            setup: () => ({
                items: [
                    [{ label: 'Group', type: 'label' }],
                    [
                        { label: 'First', testId: 'first', onSelect },
                        { label: 'Second', disabled: true },
                    ],
                ],
            }),
            template: `
                <AppDropdownMenu :items="items" :open="open">
                    <button data-testid="trigger">Menu</button>
                    <template #item-label="{ item }">
                        <span :data-testid="item.testId">{{ item.label }}</span>
                    </template>
                </AppDropdownMenu>
            `,
        },
        { props, attachTo: document.body },
    );

describe('AppDropdownMenu', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.clearAllMocks();
    });

    it('renders only the trigger when closed', () => {
        // Arrange & Act

        mountMenu();

        // Assert

        expect(document.querySelector('[data-testid="trigger"]')).not.toBeNull();
        expect(document.querySelector('[role="menu"]')).toBeNull();
    });

    it('renders items with labels and slot content when open', async () => {
        // Arrange & Act

        mountMenu({ open: true });
        await nextTick();

        // Assert

        const menu = document.querySelector('[role="menu"]');
        expect(menu?.textContent).toContain('Group');
        expect(menu?.querySelector('[data-testid="first"]')?.textContent).toBe('First');
        expect(menu?.textContent).toContain('Second');
    });

    it('calls onSelect when an item is selected', async () => {
        // Arrange

        mountMenu({ open: true });
        await nextTick();

        // Act

        const item = document.querySelector<HTMLElement>('[data-testid="first"]');
        item?.closest<HTMLElement>('[role="menuitem"]')?.click();
        await nextTick();

        // Assert

        expect(onSelect).toHaveBeenCalledTimes(1);
    });

    it('marks disabled items', async () => {
        // Arrange & Act

        mountMenu({ open: true });
        await nextTick();

        // Assert

        const items = document.querySelectorAll('[role="menuitem"]');
        expect(items[1].getAttribute('aria-disabled')).toBe('true');
    });
});
